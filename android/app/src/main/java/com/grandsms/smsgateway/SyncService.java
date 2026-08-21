package com.grandsms.smsgateway;

import android.app.AlarmManager;
import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.Context;
import android.content.Intent;
import android.os.Build;
import android.os.IBinder;
import android.os.PowerManager;
import android.os.SystemClock;
import android.util.Log;

import com.grandsms.smsgateway.model.DeviceConfig;

import java.util.List;

/**
 * Foreground polling service. It is started by MainActivity / BootReceiver and
 * keeps a WakeLock for the duration of each poll. It re-schedules itself with
 * AlarmManager.setExactAndAllowWhileIdle so polling continues under Doze, when
 * the screen is off, and when the phone is locked.
 */
public class SyncService extends Service {
    private static final String CHANNEL_ID = "smsgw_sync";
    private static final String TAG = "SyncService";
    public static final String ACTION_POLL = "com.grandsms.smsgateway.POLL";
    public static final long POLL_INTERVAL_MS = 15_000; // 15s; setExactAndAllowWhileIdle keeps it reliable

    private PowerManager.WakeLock wakeLock;

    @Override
    public void onCreate() {
        super.onCreate();
        createNotificationChannel();
        PowerManager pm = (PowerManager) getSystemService(Context.POWER_SERVICE);
        wakeLock = pm.newWakeLock(PowerManager.PARTIAL_WAKE_LOCK, "SMSGateway::SyncWakeLock");
        wakeLock.setReferenceCounted(false);
    }

    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        startForeground(1, buildNotification());
        // Hold the wakelock, run the sync, then schedule the next poll.
        if (!wakeLock.isHeld()) wakeLock.acquire(60_000);
        new Thread(this::runSyncAndReschedule).start();
        return START_STICKY;
    }

    private void runSyncAndReschedule() {
        try {
            List<DeviceConfig> devices = DeviceStore.loadAll(this);
            for (DeviceConfig d : devices) {
                if (d.getToken() == null || d.getToken().isEmpty()) continue;
                SyncWorker.pollOnce(this, d);
            }
        } catch (Throwable t) {
            Log.e(TAG, "sync failed", t);
        } finally {
            if (wakeLock.isHeld()) wakeLock.release();
            scheduleNext(this);
            // Stop the foreground only after we've re-armed the alarm; the alarm
            // will restart us, so we stopSelf to free resources between polls.
            stopSelf();
        }
    }

    /** Re-arm the exact-while-idle alarm so the next poll fires even under Doze. */
    public static void scheduleNext(Context ctx) {
        AlarmManager am = (AlarmManager) ctx.getSystemService(Context.ALARM_SERVICE);
        if (am == null) return;
        Intent i = new Intent(ctx, SyncService.class);
        i.setAction(ACTION_POLL);
        int flags = PendingIntent.FLAG_UPDATE_CURRENT | (Build.VERSION.SDK_INT >= 23 ? PendingIntent.FLAG_IMMUTABLE : 0);
        PendingIntent pi = PendingIntent.getService(ctx, 0, i, flags);
        long trigger = SystemClock.elapsedRealtime() + POLL_INTERVAL_MS;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            try {
                am.setExactAndAllowWhileIdle(AlarmManager.ELAPSED_REALTIME_WAKEUP, trigger, pi);
            } catch (SecurityException e) {
                am.setAndAllowWhileIdle(AlarmManager.ELAPSED_REALTIME_WAKEUP, trigger, pi);
            }
        } else {
            am.set(AlarmManager.ELAPSED_REALTIME_WAKEUP, trigger, pi);
        }
    }

    public static void start(Context ctx) {
        Intent i = new Intent(ctx, SyncService.class);
        i.setAction(ACTION_POLL);
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            ctx.startForegroundService(i);
        } else {
            ctx.startService(i);
        }
    }

    public static void stop(Context ctx) {
        ctx.stopService(new Intent(ctx, SyncService.class));
        AlarmManager am = (AlarmManager) ctx.getSystemService(Context.ALARM_SERVICE);
        if (am != null) {
            Intent i = new Intent(ctx, SyncService.class);
            int flags = PendingIntent.FLAG_UPDATE_CURRENT | (Build.VERSION.SDK_INT >= 23 ? PendingIntent.FLAG_IMMUTABLE : 0);
            PendingIntent pi = PendingIntent.getService(ctx, 0, i, flags);
            am.cancel(pi);
        }
    }

    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationChannel ch = new NotificationChannel(CHANNEL_ID, "SMS Gateway Sync",
                    NotificationManager.IMPORTANCE_LOW);
            ch.setDescription("Keeps the SMS gateway polling for messages");
            getSystemService(NotificationManager.class).createNotificationChannel(ch);
        }
    }

    private Notification buildNotification() {
        Intent stop = new Intent(this, SyncService.class);
        return new Notification.Builder(this, CHANNEL_ID)
                .setContentTitle("SMS Gateway active")
                .setContentText("Polling for messages…")
                .setSmallIcon(android.R.drawable.stat_sys_download)
                .setOngoing(true)
                .build();
    }

    @Override
    public IBinder onBind(Intent intent) { return null; }
}
