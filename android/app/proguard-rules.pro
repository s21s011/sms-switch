# Keep model classes used by Gson.
-keepattributes Signature
-keepattributes *Annotation*
-dontwarn com.squareup.retrofit2.**
-dontwarn okio.**
-keep class com.rbsoft.smsgateway.model.** { *; }
