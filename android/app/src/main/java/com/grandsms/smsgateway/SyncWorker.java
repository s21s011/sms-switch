package com.grandsms.smsgateway;

import android.content.Context;
import android.util.Log;

import com.google.gson.Gson;
import com.google.gson.JsonArray;
import com.google.gson.JsonObject;
import com.grandsms.smsgateway.api.GatewayApi;
import com.grandsms.smsgateway.model.DeviceConfig;
import com.grandsms.smsgateway.model.GetCampaignsResponse;
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

/**
 * Performs one polling cycle for a single registered device.
 *
 * Server protocol (matches the original vendor app):
 *   1) POST services/get-campaigns.php  -> list of pending campaign groupIds
 *   2) POST services/get-messages.php per groupId -> the actual messages
 *   3) send each message, then POST services/report-status.php
 */
public class SyncWorker {
    private static final String TAG = "SyncWorker";
    private static final String VERSION_CODE = "49";

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

            // 1) Discover pending campaigns for this device.
            Call<GetCampaignsResponse> cCall = api.getCampaigns(
                    token, token, device.getAndroidId(), device.getUserId(), VERSION_CODE);
            Response<GetCampaignsResponse> cResp = cCall.execute();
            if (!cResp.isSuccessful() || cResp.body() == null || !cResp.body().success
                    || cResp.body().data == null) {
                return;
            }
            List<String> groupIds = cResp.body().data.allCampaigns();
            if (groupIds.isEmpty()) return;

            int sent = 0;
            // 2) Fetch + send each campaign batch.
            for (String groupId : groupIds) {
                if (groupId == null || groupId.isEmpty()) continue;
                Call<GetMessagesResponse> call = api.getMessages(
                        token, token, device.getAndroidId(), device.getUserId(), groupId);
                Response<GetMessagesResponse> resp = call.execute();
                if (!resp.isSuccessful() || resp.body() == null) continue;
                GetMessagesResponse body = resp.body();
                if (!body.success || body.data == null || body.data.messages == null) continue;
                List<GetMessagesResponse.Message> msgs = body.data.messages;
                if (msgs.isEmpty()) continue;

                // 3) Send on the chosen SIM, then report status back.
                JsonArray report = new JsonArray();
                for (GetMessagesResponse.Message m : msgs) {
                    int result = SmsSender.send(ctx, m.number, m.message, m.simSlot);
                    JsonObject o = new JsonObject();
                    o.addProperty("ID", m.ID);
                    o.addProperty("status", result == 0 ? "Sent" : "Failed");
                    o.addProperty("errorCode", result);
                    o.addProperty("deliveredDate",
                            new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.US)
                                    .format(new java.util.Date()));
                    report.add(o);
                }

                api.reportStatus(token, token, device.getAndroidId(),
                        device.getUserId(), report.toString()).execute();
                sent += report.size();
            }
            Log.d(TAG, "polled device " + device.getUserId() + ", campaigns=" + groupIds.size()
                    + ", sent=" + sent);
        } catch (Throwable t) {
            Log.e(TAG, "poll error", t);
        }
    }
}
