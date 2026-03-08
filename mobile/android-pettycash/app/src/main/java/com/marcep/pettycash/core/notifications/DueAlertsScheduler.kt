package com.marcep.pettycash.core.notifications

import android.content.Context
import androidx.work.Constraints
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.NetworkType
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import java.util.concurrent.TimeUnit

object DueAlertsScheduler {
    private const val WorkName = "petty_due_alerts_worker"

    fun schedule(context: Context) {
        val workRequest = PeriodicWorkRequestBuilder<DueAlertsWorker>(
            15,
            TimeUnit.MINUTES,
        ).setConstraints(
            Constraints.Builder()
                .setRequiredNetworkType(NetworkType.CONNECTED)
                .build(),
        ).build()

        WorkManager.getInstance(context).enqueueUniquePeriodicWork(
            WorkName,
            ExistingPeriodicWorkPolicy.KEEP,
            workRequest,
        )
    }

    fun cancel(context: Context) {
        WorkManager.getInstance(context).cancelUniqueWork(WorkName)
    }
}
