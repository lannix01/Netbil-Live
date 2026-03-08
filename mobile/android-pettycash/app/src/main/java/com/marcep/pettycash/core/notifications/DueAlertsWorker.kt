package com.marcep.pettycash.core.notifications

import android.content.Context
import androidx.hilt.work.HiltWorker
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.marcep.pettycash.core.network.PettyApiService
import com.marcep.pettycash.core.network.arr
import com.marcep.pettycash.core.network.asObj
import com.marcep.pettycash.core.network.int
import com.marcep.pettycash.core.network.str
import dagger.assisted.Assisted
import dagger.assisted.AssistedInject
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

@HiltWorker
class DueAlertsWorker @AssistedInject constructor(
    @Assisted appContext: Context,
    @Assisted workerParams: WorkerParameters,
    private val api: PettyApiService,
) : CoroutineWorker(appContext, workerParams) {

    override suspend fun doWork() = withContext(Dispatchers.IO) {
        ensureAlertChannel(applicationContext)
        if (!canPostNotifications(applicationContext)) {
            return@withContext Result.success()
        }

        runCatching {
            pushDueHostelNotifications()
            Result.success()
        }.getOrElse {
            Result.retry()
        }
    }

    private suspend fun pushDueHostelNotifications() {
        val response = api.hostels(perPage = 100)
        if (!response.success) return

        val hostels = response.data
            ?.arr("hostels")
            ?.mapNotNull { it.asObj() }
            .orEmpty()
        val now = System.currentTimeMillis()

        hostels.forEach { row ->
            val status = row.str("due_status").orEmpty()
            if (status != "due_today" && status != "overdue") return@forEach

            val hostelId = row.int("id") ?: return@forEach
            if (!shouldNotify("hostel_$hostelId", now)) return@forEach

            val hostelName = row.str("hostel_name").orEmpty().ifBlank { "Hostel #$hostelId" }
            val dueBadge = row.str("due_badge").orEmpty()
            val dueText = listOfNotNull(
                if (dueBadge.isBlank()) null else dueBadge,
                row.str("next_due_date")?.takeIf { it.isNotBlank() }?.let { "Due: $it" },
            ).ifEmpty { listOf("Token payment is due.") }.joinToString(" • ")

            showSystemAlert(
                context = applicationContext,
                notificationId = 900_000 + hostelId,
                title = "Token Due Alert",
                message = "$hostelName: $dueText",
            )
        }
    }

    private fun shouldNotify(key: String, nowMillis: Long): Boolean {
        val prefs = applicationContext.getSharedPreferences(PrefsName, Context.MODE_PRIVATE)
        val previous = prefs.getLong(key, 0L)
        if (nowMillis - previous < ThrottleMs) return false
        prefs.edit().putLong(key, nowMillis).apply()
        return true
    }

    private companion object {
        const val PrefsName = "petty_alert_worker"
        const val ThrottleMs = 3 * 60 * 1000L
    }
}
