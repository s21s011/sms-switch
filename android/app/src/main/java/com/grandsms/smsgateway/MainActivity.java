package com.grandsms.smsgateway;

import android.Manifest;
import android.content.pm.PackageManager;
import android.content.Intent;
import android.os.Build;
import android.os.Bundle;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ListView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;

import com.grandsms.smsgateway.api.GatewayApi;
import com.grandsms.smsgateway.model.DeviceConfig;
import com.grandsms.smsgateway.model.DeviceRegistration;

import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.Executors;

import okhttp3.OkHttpClient;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;

public class MainActivity extends AppCompatActivity {
    private static final int REQ_PERMS = 1001;
    private EditText etServer, etAndroidId, etUserId, etPassword;
    private ListView listView;
    private ArrayAdapter<String> adapter;
    private final List<DeviceConfig> devices = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        etServer = findViewById(R.id.etServer);
        etAndroidId = findViewById(R.id.etAndroidId);
        etUserId = findViewById(R.id.etUserId);
        etPassword = findViewById(R.id.etPassword);
        listView = findViewById(R.id.listDevices);
        Button btnAdd = findViewById(R.id.btnAdd);
        Button btnStart = findViewById(R.id.btnStart);
        Button btnStop = findViewById(R.id.btnStop);

        devices.addAll(DeviceStore.loadAll(this));
        adapter = new ArrayAdapter<>(this, android.R.layout.simple_list_item_1, new ArrayList<>());
        listView.setAdapter(adapter);
        refreshList();

        btnAdd.setOnClickListener(v -> registerDevice());
        btnStart.setOnClickListener(v -> {
            requestPermissions();
            if (!devices.isEmpty()) {
                SyncService.start(this);
                Toast.makeText(this, "Gateway started", Toast.LENGTH_SHORT).show();
            }
        });
        btnStop.setOnClickListener(v -> {
            SyncService.stop(this);
            Toast.makeText(this, "Gateway stopped", Toast.LENGTH_SHORT).show();
        });
        listView.setOnItemLongClickListener((p, v, pos, id) -> {
            DeviceStore.remove(this, devices.get(pos));
            devices.remove(pos);
            refreshList();
            return true;
        });
    }

    private void refreshList() {
        List<String> labels = new ArrayList<>();
        for (DeviceConfig d : devices) labels.add(d.getServerUrl() + " | user " + d.getUserId());
        adapter.clear();
        adapter.addAll(labels);
        adapter.notifyDataSetChanged();
    }

    private void registerDevice() {
        String url = etServer.getText().toString().trim();
        String androidId = etAndroidId.getText().toString().trim();
        String userId = etUserId.getText().toString().trim();
        String pw = etPassword.getText().toString().trim();
        if (url.isEmpty() || androidId.isEmpty() || userId.isEmpty() || pw.isEmpty()) {
            Toast.makeText(this, "All fields are required", Toast.LENGTH_SHORT).show();
            return;
        }
        Executors.newSingleThreadExecutor().execute(() -> {
            try {
                OkHttpClient client = new OkHttpClient.Builder().build();
                Retrofit retrofit = new Retrofit.Builder()
                        .baseUrl(url.endsWith("/") ? url : url + "/")
                        .client(client)
                        .addConverterFactory(GsonConverterFactory.create())
                        .build();
                GatewayApi api = retrofit.create(GatewayApi.class);
                Call<DeviceRegistration> call = api.signIn(androidId, userId, pw);
                Response<DeviceRegistration> resp = call.execute();
                if (resp.isSuccessful() && resp.body() != null && resp.body().success
                        && resp.body().data != null && resp.body().data.token != null) {
                    String token = resp.body().data.token;
                    DeviceConfig cfg = new DeviceConfig(url, androidId, userId, token);
                    DeviceStore.addOrUpdate(this, cfg);
                    runOnUiThread(() -> {
                        devices.clear();
                        devices.addAll(DeviceStore.loadAll(this));
                        refreshList();
                        Toast.makeText(this, "Device registered", Toast.LENGTH_SHORT).show();
                    });
                } else {
                    final String msg = resp.body() != null && resp.body().error != null
                            ? resp.body().error.message : "Registration failed";
                    runOnUiThread(() -> Toast.makeText(this, msg, Toast.LENGTH_LONG).show());
                }
            } catch (Exception e) {
                runOnUiThread(() -> Toast.makeText(this, "Error: " + e.getMessage(), Toast.LENGTH_LONG).show());
            }
        });
    }

    private void requestPermissions() {
        List<String> needed = new ArrayList<>();
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU
                && ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
            needed.add(Manifest.permission.POST_NOTIFICATIONS);
        }
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.SEND_SMS) != PackageManager.PERMISSION_GRANTED) {
            needed.add(Manifest.permission.SEND_SMS);
        }
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.READ_PHONE_STATE) != PackageManager.PERMISSION_GRANTED) {
            needed.add(Manifest.permission.READ_PHONE_STATE);
        }
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.RECEIVE_SMS) != PackageManager.PERMISSION_GRANTED) {
            needed.add(Manifest.permission.RECEIVE_SMS);
        }
        if (!needed.isEmpty()) {
            ActivityCompat.requestPermissions(this, needed.toArray(new String[0]), REQ_PERMS);
        }
        // Ask the user to disable battery optimization so Doze never stops polling.
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            android.provider.Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS.equals("");
            Intent i = new Intent(android.provider.Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS);
            i.setData(android.net.Uri.parse("package:" + getPackageName()));
            if (i.resolveActivity(getPackageManager()) != null) startActivity(i);
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
    }
}
