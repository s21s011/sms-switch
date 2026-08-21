package com.grandsms.smsgateway.model;

import java.util.List;

public class GetMessagesResponse {
    public boolean success;
    public Data data;
    public Error error;

    public static class Data {
        public List<Message> messages;
        public String groupId;
    }

    public static class Message {
        public int ID;
        public String number;
        public String message;
        public int simSlot;
        public String attachments;
        public String type;
    }

    public static class Error {
        public int code;
        public String message;
    }
}
