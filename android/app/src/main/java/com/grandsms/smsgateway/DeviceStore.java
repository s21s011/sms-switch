package com.grandsms.smsgateway;

import android.content.Context;
import android.content.SharedPreferences;

import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import com.grandsms.smsgateway.model.DeviceConfig;

import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;

/** Stores multiple device registrations (multi-user / multi-device support). */
public class DeviceStore {
    private static final String PREFS = "smsgw_devices";
    private static final String KEY_LIST = "devices";

    public static List<DeviceConfig> loadAll(Context ctx) {
        SharedPreferences sp = ctx.getSharedPreferences(PREFS, Context.MODE_PRIVATE);
        String json = sp.getString(KEY_LIST, "[]");
        Type type = new TypeToken<List<DeviceConfig>>() {}.getType();
        List<DeviceConfig> list = new Gson().fromJson(json, type);
        return list == null ? new ArrayList<>() : list;
    }

    public static void saveAll(Context ctx, List<DeviceConfig> devices) {
        SharedPreferences sp = ctx.getSharedPreferences(PREFS, Context.MODE_PRIVATE);
        sp.edit().putString(KEY_LIST, new Gson().toJson(devices)).apply();
    }

    public static void addOrUpdate(Context ctx, DeviceConfig device) {
        List<DeviceConfig> devices = loadAll(ctx);
        boolean replaced = false;
        for (int i = 0; i < devices.size(); i++) {
            if (devices.get(i).key().equals(device.key())) {
                devices.set(i, device);
                replaced = true;
                break;
            }
        }
        if (!replaced) devices.add(device);
        saveAll(ctx, devices);
    }

    public static void remove(Context ctx, DeviceConfig device) {
        List<DeviceConfig> devices = loadAll(ctx);
        devices.removeIf(d -> d.key().equals(device.key()));
        saveAll(ctx, devices);
    }
}
