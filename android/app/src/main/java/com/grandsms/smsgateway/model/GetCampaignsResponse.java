package com.grandsms.smsgateway.model;

import java.util.List;

/** Response of services/get-campaigns.php (device polling step 1). */
public class GetCampaignsResponse {
    public boolean success;
    public Data data;
    public Error error;

    /** Per-user campaign payload. The device is normally bound to one user. */
    public static class Data {
        // JSON keys are numeric user IDs, so capture them generically.
        public java.util.Map<String, UserCampaigns> extra = new java.util.HashMap<>();

        // Convenience: merged campaigns across users (devices serve one user in practice).
        public List<String> allCampaigns() {
            java.util.List<String> out = new java.util.ArrayList<>();
            if (extra != null) {
                for (UserCampaigns uc : extra.values()) {
                    if (uc == null) continue;
                    if (uc.campaigns != null) out.addAll(uc.campaigns);
                    if (uc.prioritizedCampaigns != null) out.addAll(uc.prioritizedCampaigns);
                }
            }
            return out;
        }
    }

    public static class UserCampaigns {
        public List<String> campaigns;
        public List<String> prioritizedCampaigns;
        public List<Object> ussdRequests;
    }

    public static class Error {
        public int code;
        public String message;
    }
}
