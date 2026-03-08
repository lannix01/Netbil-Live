package com.marcep.pettycash.di

import com.jakewharton.retrofit2.converter.kotlinx.serialization.asConverterFactory
import com.marcep.pettycash.BuildConfig
import com.marcep.pettycash.core.network.AuthHeaderInterceptor
import com.marcep.pettycash.core.network.PettyApiService
import com.marcep.pettycash.core.network.TokenAuthenticator
import com.marcep.pettycash.core.network.TokenRefreshService
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import javax.inject.Named
import javax.inject.Singleton
import kotlinx.serialization.json.Json
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit

@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {

    @Provides
    @Singleton
    fun provideJson(): Json = Json {
        ignoreUnknownKeys = true
        explicitNulls = false
        isLenient = true
    }

    @Provides
    @Singleton
    fun provideLoggingInterceptor(): HttpLoggingInterceptor {
        return HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }
    }

    @Provides
    @Singleton
    @Named("baseUrl")
    fun provideBaseUrl(): String {
        val raw = BuildConfig.API_BASE_URL.trim()
        return if (raw.endsWith('/')) raw else "$raw/"
    }

    @Provides
    @Singleton
    @Named("refreshClient")
    fun provideRefreshClient(loggingInterceptor: HttpLoggingInterceptor): OkHttpClient {
        return OkHttpClient.Builder()
            .addInterceptor(loggingInterceptor)
            .build()
    }

    @Provides
    @Singleton
    @Named("mainClient")
    fun provideMainClient(
        authHeaderInterceptor: AuthHeaderInterceptor,
        tokenAuthenticator: TokenAuthenticator,
        loggingInterceptor: HttpLoggingInterceptor,
    ): OkHttpClient {
        return OkHttpClient.Builder()
            .addInterceptor(authHeaderInterceptor)
            .authenticator(tokenAuthenticator)
            .addInterceptor(loggingInterceptor)
            .build()
    }

    @Provides
    @Singleton
    @Named("refreshRetrofit")
    fun provideRefreshRetrofit(
        @Named("refreshClient") client: OkHttpClient,
        @Named("baseUrl") baseUrl: String,
        json: Json,
    ): Retrofit {
        return Retrofit.Builder()
            .baseUrl(baseUrl)
            .client(client)
            .addConverterFactory(json.asConverterFactory("application/json".toMediaType()))
            .build()
    }

    @Provides
    @Singleton
    @Named("mainRetrofit")
    fun provideMainRetrofit(
        @Named("mainClient") client: OkHttpClient,
        @Named("baseUrl") baseUrl: String,
        json: Json,
    ): Retrofit {
        return Retrofit.Builder()
            .baseUrl(baseUrl)
            .client(client)
            .addConverterFactory(json.asConverterFactory("application/json".toMediaType()))
            .build()
    }

    @Provides
    @Singleton
    fun provideTokenRefreshService(@Named("refreshRetrofit") retrofit: Retrofit): TokenRefreshService {
        return retrofit.create(TokenRefreshService::class.java)
    }

    @Provides
    @Singleton
    fun providePettyApiService(@Named("mainRetrofit") retrofit: Retrofit): PettyApiService {
        return retrofit.create(PettyApiService::class.java)
    }
}
