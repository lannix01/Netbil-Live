package com.marcep.pettycash.core.network

import com.marcep.pettycash.core.data.SessionStore
import javax.inject.Inject
import kotlinx.coroutines.runBlocking
import okhttp3.Interceptor
import okhttp3.Response

class AuthHeaderInterceptor @Inject constructor(
    private val sessionStore: SessionStore,
) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val request = chain.request()

        if (request.header("X-No-Auth") == "true") {
            val clean = request.newBuilder().removeHeader("X-No-Auth").build()
            return chain.proceed(clean)
        }

        val session = runBlocking { sessionStore.currentSession() }
        val auth = session.authHeaderValue()

        val newRequest = if (auth != null) {
            request.newBuilder()
                .header("Authorization", auth)
                .build()
        } else {
            request
        }

        return chain.proceed(newRequest)
    }
}
