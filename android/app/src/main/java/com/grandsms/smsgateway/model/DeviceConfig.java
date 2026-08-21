package com.grandsms.smsgateway.model;

public class DeviceConfig {
    private String serverUrl;
    private String androidId;
    private String userId;
    private String token;

    public DeviceConfig() {}

    public DeviceConfig(String serverUrl, String androidId, String userId, String token) {
        this.serverUrl = serverUrl;
        this.androidId = androidId;
        this.userId = userId;
        this.token = token;
    }

    public String getServerUrl() { return serverUrl; }
    public void setServerUrl(String v) { serverUrl = v; }
    public String getAndroidId() { return androidId; }
    public void setAndroidId(String v) { androidId = v; }
    public String getUserId() { return userId; }
    public void setUserId(String v) { userId = v; }
    public String getToken() { return token; }
    public void setToken(String v) { token = v; }

    /** Stable key used to store this device in SharedPreferences. */
    public String key() {
        return serverUrl.replaceAll("[^a-zA-Z0-9]", "_") + "_" + userId + "_" + androidId;
    }
}
