package com.marcep.pettycash.core.network

import kotlinx.serialization.json.JsonObject
import retrofit2.Call
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface PettyApiService {
    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): ApiEnvelope<JsonObject>

    @GET("auth/me")
    suspend fun me(): ApiEnvelope<JsonObject>

    @POST("auth/refresh")
    suspend fun refresh(@Body request: RefreshRequest = RefreshRequest()): ApiEnvelope<JsonObject>

    @POST("auth/logout-all")
    suspend fun logoutAll(@Body request: LogoutAllRequest = LogoutAllRequest()): ApiEnvelope<JsonObject>

    @DELETE("auth/tokens/current")
    suspend fun logoutCurrent(): ApiEnvelope<JsonObject>

    @DELETE("auth/tokens/{tokenId}")
    suspend fun revokeSession(@Path("tokenId") tokenId: Int): ApiEnvelope<JsonObject>

    @GET("auth/tokens")
    suspend fun sessions(
        @Query("all_users") allUsers: Int? = null,
        @Query("user_id") userId: Int? = null,
    ): ApiEnvelope<JsonObject>

    @GET("insights/dashboard")
    suspend fun dashboard(): ApiEnvelope<JsonObject>

    @GET("credits")
    suspend fun credits(
        @Query("per_page") perPage: Int = 15,
        @Query("q") query: String? = null,
    ): ApiEnvelope<JsonObject>

    @GET("spendings")
    suspend fun spendings(
        @Query("per_page") perPage: Int = 15,
        @Query("q") query: String? = null,
    ): ApiEnvelope<JsonObject>

    @GET("tokens/hostels")
    suspend fun hostels(
        @Query("per_page") perPage: Int = 15,
        @Query("q") query: String? = null,
    ): ApiEnvelope<JsonObject>

    @GET("maintenances/schedule")
    suspend fun maintenanceSchedule(
        @Query("per_page") perPage: Int = 15,
        @Query("q") query: String? = null,
    ): ApiEnvelope<JsonObject>

    @GET("maintenances/history")
    suspend fun maintenanceHistory(
        @Query("per_page") perPage: Int = 15,
        @Query("q") query: String? = null,
    ): ApiEnvelope<JsonObject>

    @GET("maintenances/unroadworthy")
    suspend fun maintenanceUnroadworthy(
        @Query("per_page") perPage: Int = 15,
        @Query("q") query: String? = null,
    ): ApiEnvelope<JsonObject>

    @GET("masters/bikes")
    suspend fun bikes(
        @Query("per_page") perPage: Int = 15,
        @Query("q") query: String? = null,
    ): ApiEnvelope<JsonObject>

    @GET("masters/respondents")
    suspend fun respondents(
        @Query("per_page") perPage: Int = 15,
        @Query("q") query: String? = null,
    ): ApiEnvelope<JsonObject>

    @GET("reports/lookups")
    suspend fun reportLookups(@Query("batch_limit") batchLimit: Int = 100): ApiEnvelope<JsonObject>

    @GET("notifications")
    suspend fun notifications(
        @Query("per_page") perPage: Int = 25,
        @Query("q") query: String? = null,
    ): ApiEnvelope<JsonObject>

    @POST("notifications")
    suspend fun createNotification(@Body request: JsonObject): ApiEnvelope<JsonObject>

    @POST("notifications/read-all")
    suspend fun readAllNotifications(): ApiEnvelope<JsonObject>

    @POST("notifications/{notification}/read")
    suspend fun readNotification(@Path("notification") notificationId: Int): ApiEnvelope<JsonObject>

    @GET("batches/available")
    suspend fun availableBatches(): ApiEnvelope<JsonObject>

    @POST("masters/bikes")
    suspend fun createBike(@Body request: JsonObject): ApiEnvelope<JsonObject>

    @PATCH("masters/bikes/{bike}")
    suspend fun updateBike(@Path("bike") bikeId: Int, @Body request: JsonObject): ApiEnvelope<JsonObject>

    @POST("masters/respondents")
    suspend fun createRespondent(@Body request: JsonObject): ApiEnvelope<JsonObject>

    @PATCH("masters/respondents/{respondent}")
    suspend fun updateRespondent(@Path("respondent") respondentId: Int, @Body request: JsonObject): ApiEnvelope<JsonObject>

    @POST("credits")
    suspend fun createCredit(@Body request: JsonObject): ApiEnvelope<JsonObject>

    @POST("spendings")
    suspend fun createSpending(@Body request: JsonObject): ApiEnvelope<JsonObject>

    @POST("tokens/hostels")
    suspend fun createHostel(@Body request: JsonObject): ApiEnvelope<JsonObject>

    @PATCH("tokens/hostels/{hostel}")
    suspend fun updateHostel(
        @Path("hostel") hostelId: Int,
        @Body request: JsonObject,
    ): ApiEnvelope<JsonObject>

    @GET("tokens/hostels/{hostel}")
    suspend fun hostelDetails(
        @Path("hostel") hostelId: Int,
        @Query("payments_per_page") paymentsPerPage: Int = 20,
    ): ApiEnvelope<JsonObject>

    @POST("tokens/hostels/{hostel}/payments")
    suspend fun createHostelPayment(
        @Path("hostel") hostelId: Int,
        @Body request: JsonObject,
    ): ApiEnvelope<JsonObject>

    @POST("maintenances/bikes/{bike}/services")
    suspend fun createBikeService(
        @Path("bike") bikeId: Int,
        @Body request: JsonObject,
    ): ApiEnvelope<JsonObject>

    @POST("maintenances/bikes/{bike}/unroadworthy")
    suspend fun setBikeUnroadworthy(
        @Path("bike") bikeId: Int,
        @Body request: JsonObject,
    ): ApiEnvelope<JsonObject>
}

interface TokenRefreshService {
    @POST("auth/refresh")
    fun refresh(
        @Header("Authorization") authorization: String,
        @Body request: RefreshRequest = RefreshRequest(),
    ): Call<ApiEnvelope<JsonObject>>
}
