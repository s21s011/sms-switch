package com.grandsms.smsgateway;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.os.Build;

/**
 * Re-arms the polling service after boot (and after app update).
 * Without this, the gateway would stop working until the app is manually opened.
 */
public class BootReceiver extends BroadcastReceiver {
    @Override
    public void onReceive(Context context, Intent intent) {
        String action = intent.getAction();
        if (Intent.ACTION_BOOT_COMPLETED.equals(action)
                || Intent.ACTION_MY_PACKAGE_REPLACED.equals(action)) {
            // Only start if there are registered devices.
            if (!DeviceStore.loadAll(context).isEmpty()) {
                SyncService.start(context);
            }
        }
    }
}
