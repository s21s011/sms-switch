package com.grandsms.smsgateway;

import android.annotation.TargetApi;
import android.content.Context;
import android.os.Build;
import android.telephony.SmsManager;
import android.telephony.SubscriptionInfo;
import android.telephony.SubscriptionManager;

import java.util.ArrayList;
import java.util.List;

/**
 * Sends an SMS on a specific SIM, chosen by its slot index (0-based) as
 * configured on the web dashboard. Works on Android 7 (API 24) through 14 (API 34).
 */
public class SmsSender {

    /** Returns the active SIM slot count (API 24+). */
    @TargetApi(Build.VERSION_CODES.LOLLIPOP_MR1)
    public static int getSimSlotCount(Context ctx) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.LOLLIPOP_MR1) return 1;
        SubscriptionManager sm = (SubscriptionManager) ctx.getSystemService(Context.TELEPHONY_SUBSCRIPTION_SERVICE);
        if (sm == null) return 1;
        List<SubscriptionInfo> subs = sm.getActiveSubscriptionInfoList();
        return (subs == null) ? 1 : Math.max(1, subs.size());
    }

    /** Maps a 0-based slot index to a subscription id, or -1 to use the default SIM. */
    private static int subscriptionIdForSlot(Context ctx, int slot) {
        if (slot < 0) return -1;
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP_MR1) {
            SubscriptionManager sm = (SubscriptionManager) ctx.getSystemService(Context.TELEPHONY_SUBSCRIPTION_SERVICE);
            if (sm != null) {
                List<SubscriptionInfo> subs = sm.getActiveSubscriptionInfoList();
                if (subs != null) {
                    for (SubscriptionInfo info : subs) {
                        if (info.getSimSlotIndex() == slot) {
                            return info.getSubscriptionId();
                        }
                    }
                }
            }
        }
        return -1;
    }

    /**
     * @param slot 0-based SIM slot chosen on the dashboard, or -1 for default.
     * @return 0 = delivered to the SMS stack, negative = error code (see SmsManager result codes).
     */
    public static int send(Context ctx, String number, String text, int slot) {
        if (ctx == null || number == null || text == null) return -1;
        try {
            SmsManager sm;
            int subId = subscriptionIdForSlot(ctx, slot);
            if (subId != -1 && Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP_MR1) {
                sm = SmsManager.getSmsManagerForSubscriptionId(subId);
            } else {
                sm = SmsManager.getDefault();
            }

            ArrayList<String> parts = sm.divideMessage(text);
            if (parts.size() > 1) {
                sm.sendMultipartTextMessage(number, null, parts, null, null);
            } else {
                sm.sendTextMessage(number, null, text, null, null);
            }
            return 0;
        } catch (SecurityException e) {
            return -2; // permission missing
        } catch (Exception e) {
            return -3; // generic failure
        }
    }
}
