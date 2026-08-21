package com.grandsms.smsgateway.model;

public class DeviceRegistration {
    public boolean success;
    public Data data;
    public Error error;

    public static class Data {
        public String purchaseCode;
        public String token;
        public Device device;
    }

    public static class Error {
        public int code;
        public String message;
    }
}
