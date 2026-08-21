package com.grandsms.smsgateway;

import android.content.Context;
import android.util.Log;

import com.google.gson.Gson;
import com.google.gson.JsonArray;
import com.google.gson.JsonObject;
import com.grandsms.smsgateway.api.GatewayApi;
import com.grandsms.smsgateway.model.DeviceConfig;
import com.grandsms.smsgateway.model.GetMessagesResponse;
import com.grandsms.smsgateway.model.StatusReport;

import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.TimeUnit;

import okhttp3.OkHttpClient;
import retrofit2.Call;
import retrofit2.Response;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;

/** Performs one polling cycle for a single registered device. */
public class SyncWorker {
    private static final String TAG = "SyncWorker";

    private static GatewayApi apiFor(String baseUrl) {
        OkHttpClient client = new OkHttpClient.Builder()
                .connectTimeout(20, TimeUnit.SECONDS)
                .readTimeout(20, TimeUnit.SECONDS)
                .build();
        Retrofit retrofit = new Retrofit.Builder()
                .baseUrl(ensureSlash(baseUrl))
                .client(client)
                .addConverterFactory(GsonConverterFactory.create())
                .build();
        return retrofit.create(GatewayApi.class);
    }

    private static String ensureSlash(String url) {
        if (url == null) return "https://localhost/";
        if (!url.endsWith("/")) url += "/";
        return url;
    }

    public static void pollOnce(Context ctx, DeviceConfig device) {
        try {
            GatewayApi api = apiFor(device.getServerUrl());
            String token = device.getToken();

            // 1) Fetch the next batch of pending messages.
            Call<GetMessagesResponse> call = api.getMessages(token, token, device.getAndroidId(),
                    device.getUserId(), "");
            Response<GetMessagesResponse> resp = call.execute();
            if (!resp.isSuccessful() || resp.body() == null) return;
            GetMessagesResponse body = resp.body();
            if (!body.success || body.data == null || body.data.messages == null) return;
            List<GetMessagesResponse.Message> msgs = body.data.messages;
            if (msgs.isEmpty()) return;

            String groupId = body.data.groupId;

            // 2) Send each message on the chosen SIM, then report status back.
            JsonArray report = new JsonArray();
            for (GetMessagesResponse.Message m : msgs) {
                int result = SmsSender.send(ctx, m.number, m.message, m.simSlot);
                JsonObject o = new JsonObject();
                o.addProperty("ID", m.ID);
                o.addProperty("status", result == 0 ? "Sent" : "Failed");
                o.addProperty("errorCode", result);
                o.addProperty("deliveredDate", new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.US).format(new java.util.Date()));
                report.add(o);
            }

            Call<StatusReport> r = api.reportStatus(token, token, device.getAndroidId(),
                    device.getUserId(), report.toString());
            r.execute();

            Log.d(TAG, "polled device " + device.getUserId() + " group " + groupId + " sent " + report.size());
        } catch (Throwable t) {
            Log.e(TAG, "poll error", t);
        }
    }
}
