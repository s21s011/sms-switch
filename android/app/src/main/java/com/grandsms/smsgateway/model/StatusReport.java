package com.grandsms.smsgateway.model;

public class StatusReport {
    public boolean success;
    public Object data;
    public Error error;

    public static class Error {
        public int code;
        public String message;
    }
}
