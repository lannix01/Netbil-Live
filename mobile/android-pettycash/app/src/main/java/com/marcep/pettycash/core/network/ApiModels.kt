package com.marcep.pettycash.core.network

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.JsonObject

@Serializable
data class ApiEnvelope<T>(
    val success: Boolean = false,
    val message: String = "",
    val data: T? = null,
    val errors: JsonObject? = null,
    val meta: JsonObject? = null,
)

@Serializable
data class LoginRequest(
    val email: String,
    val password: String,
    @SerialName("device_name") val deviceName: String = "android-mobile",
    @SerialName("device_id") val deviceId: String? = null,
    @SerialName("device_platform") val devicePlatform: String = "android",
    @SerialName("revoke_other_sessions") val revokeOtherSessions: Boolean = false,
)

@Serializable
data class RefreshRequest(
    @SerialName("device_name") val deviceName: String? = null,
    @SerialName("device_id") val deviceId: String? = null,
    @SerialName("device_platform") val devicePlatform: String? = "android",
    @SerialName("revoke_other_sessions") val revokeOtherSessions: Boolean = false,
)

@Serializable
data class LogoutAllRequest(
    @SerialName("include_current") val includeCurrent: Boolean = true,
)
