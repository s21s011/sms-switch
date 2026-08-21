package com.grandsms.smsgateway.api;

import com.grandsms.smsgateway.model.DeviceRegistration;
import com.grandsms.smsgateway.model.GetMessagesResponse;
import com.grandsms.smsgateway.model.StatusReport;

import java.util.List;
import java.util.Map;

import retrofit2.Call;
import retrofit2.http.Field;
import retrofit2.http.FormUrlEncoded;
import retrofit2.http.Header;
import retrofit2.http.POST;

public interface GatewayApi {

    @FormUrlEncoded
    @POST("services/get-messages.php")
    Call<GetMessagesResponse> getMessages(
            @Header("X-Device-Token") String token,
            @Field("token") String tokenBody,
            @Field("androidId") String androidId,
            @Field("userId") String userId,
            @Field("groupId") String groupId);

    @FormUrlEncoded
    @POST("services/report-status.php")
    Call<StatusReport> reportStatus(
            @Header("X-Device-Token") String token,
            @Field("token") String tokenBody,
            @Field("androidId") String androidId,
            @Field("userId") String userId,
            @Field("messages") String messagesJson);

    @FormUrlEncoded
    @POST("services/sign-in.php")
    Call<DeviceRegistration> signIn(
            @Field("androidId") String androidId,
            @Field("userId") String userId,
            @Field("password") String password);

    @FormUrlEncoded
    @POST("services/update-token.php")
    Call<StatusReport> updateToken(
            @Header("X-Device-Token") String token,
            @Field("token") String tokenBody,
            @Field("androidId") String androidId,
            @Field("userId") String userId,
            @Field("token") String fcmToken);

    @FormUrlEncoded
    @POST("services/sign-out.php")
    Call<StatusReport> signOut(
            @Header("X-Device-Token") String token,
            @Field("token") String tokenBody,
            @Field("androidId") String androidId,
            @Field("userId") String userId);

    @FormUrlEncoded
    @POST("services/receive-message.php")
    Call<StatusReport> receiveMessage(
            @Header("X-Device-Token") String token,
            @Field("token") String tokenBody,
            @Field("androidId") String androidId,
            @Field("userId") String userId,
            @Field("messages") String messagesJson);
}
