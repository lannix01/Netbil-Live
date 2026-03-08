package com.marcep.pettycash.core.notifications

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.content.Context
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import com.marcep.pettycash.R

const val AlertsChannelId: String = "pettycash_alerts"
private const val AlertsChannelName = "PettyCash Alerts"

fun ensureAlertChannel(context: Context) {
    if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
    val manager = context.getSystemService(Context.NOTIFICATION_SERVICE) as? NotificationManager ?: return
    if (manager.getNotificationChannel(AlertsChannelId) != null) return

    val channel = NotificationChannel(
        AlertsChannelId,
        AlertsChannelName,
        NotificationManager.IMPORTANCE_HIGH,
    ).apply {
        description = "PettyCash due alerts and in-app notifications"
    }
    manager.createNotificationChannel(channel)
}

fun canPostNotifications(context: Context): Boolean {
    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
        ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
    ) {
        return false
    }
    return NotificationManagerCompat.from(context).areNotificationsEnabled()
}

fun showSystemAlert(
    context: Context,
    notificationId: Int,
    title: String,
    message: String,
) {
    if (!canPostNotifications(context)) return

    val notification = NotificationCompat.Builder(context, AlertsChannelId)
        .setSmallIcon(R.mipmap.ic_launcher)
        .setContentTitle(title)
        .setContentText(message)
        .setStyle(NotificationCompat.BigTextStyle().bigText(message))
        .setAutoCancel(true)
        .setPriority(NotificationCompat.PRIORITY_HIGH)
        .build()

    NotificationManagerCompat.from(context).notify(notificationId, notification)
}
