package com.marcep.pettycash.core.repository

import com.marcep.pettycash.core.model.ModuleKey
import com.marcep.pettycash.core.model.ModuleSummary
import com.marcep.pettycash.core.network.PettyApiService
import com.marcep.pettycash.core.network.arr
import com.marcep.pettycash.core.network.double
import com.marcep.pettycash.core.network.int
import com.marcep.pettycash.core.network.obj
import com.marcep.pettycash.core.network.str
import kotlinx.coroutines.async
import kotlinx.coroutines.coroutineScope
import java.text.DecimalFormat
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class HomeRepository @Inject constructor(
    private val api: PettyApiService,
) {
    private val money = DecimalFormat("#,##0.00")

    suspend fun loadAllSummaries(): List<ModuleSummary> = coroutineScope {
        val dashboard = async { loadDashboard() }
        val credits = async { loadCredits() }
        val spendings = async { loadSpendings() }
        val tokens = async { loadHostels() }
        val maintenance = async { loadMaintenance() }
        val masters = async { loadMasters() }
        val respondents = async { loadRespondents() }

        listOf(
            dashboard.await(),
            credits.await(),
            spendings.await(),
            tokens.await(),
            maintenance.await(),
            masters.await(),
            respondents.await(),
        )
    }

    private suspend fun loadDashboard(): ModuleSummary {
        return try {
            val res = api.dashboard()
            if (!res.success || res.data == null) {
                return ModuleSummary(ModuleKey.DASHBOARD, "--", res.message.ifBlank { "Failed" }, false)
            }
            val summary = res.data.obj("summary")
            val balance = summary?.double("balance") ?: 0.0
            val spent = summary?.double("total_spent") ?: 0.0
            val credited = summary?.double("total_credited") ?: 0.0
            ModuleSummary(
                key = ModuleKey.DASHBOARD,
                value = "KES ${money.format(balance)}",
                subtitle = "Available | Credited: KES ${money.format(credited)} | Spent: KES ${money.format(spent)}",
                ok = true,
            )
        } catch (t: Throwable) {
            ModuleSummary(ModuleKey.DASHBOARD, "--", t.message ?: "Error", false)
        }
    }

    private suspend fun loadCredits(): ModuleSummary {
        return try {
            val res = api.credits(15)
            if (!res.success || res.data == null) {
                return ModuleSummary(ModuleKey.CREDITS, "--", res.message.ifBlank { "Failed" }, false)
            }
            val totalNet = res.data.obj("summary")?.double("total_net_amount") ?: 0.0
            ModuleSummary(
                key = ModuleKey.CREDITS,
                value = "KES ${money.format(totalNet)}",
                subtitle = "Money received",
                ok = true,
            )
        } catch (t: Throwable) {
            ModuleSummary(ModuleKey.CREDITS, "--", t.message ?: "Error", false)
        }
    }

    private suspend fun loadSpendings(): ModuleSummary {
        return try {
            val res = api.spendings(15)
            if (!res.success || res.data == null) {
                return ModuleSummary(ModuleKey.SPENDINGS, "--", res.message.ifBlank { "Failed" }, false)
            }
            val net = res.data.obj("summary")?.double("net_total") ?: 0.0
            val topBucketSummary = try {
                val dash = api.dashboard()
                if (!dash.success || dash.data == null) {
                    null
                } else {
                val top = dash.data.obj("top_bucket")
                val bucketType = top?.str("type").orEmpty()
                val bucketSubType = top?.str("sub_type").orEmpty()
                val bucketTotal = top?.double("total") ?: 0.0
                if (bucketType.isBlank()) null else {
                    val bucketLabel = if (bucketSubType.isBlank()) bucketType else "$bucketType:$bucketSubType"
                    "Most spending: $bucketLabel (KES ${money.format(bucketTotal)})"
                }
                }
            } catch (_: Throwable) {
                null
            }
            ModuleSummary(
                key = ModuleKey.SPENDINGS,
                value = "KES ${money.format(net)}",
                subtitle = topBucketSummary ?: "Money disbursed",
                ok = true,
            )
        } catch (t: Throwable) {
            ModuleSummary(ModuleKey.SPENDINGS, "--", t.message ?: "Error", false)
        }
    }

    private suspend fun loadHostels(): ModuleSummary {
        return try {
            val res = api.hostels(15)
            if (!res.success || res.data == null) {
                return ModuleSummary(ModuleKey.TOKENS, "--", res.message.ifBlank { "Failed" }, false)
            }
            val listSize = res.meta?.obj("pagination")?.int("total") ?: (res.data.arr("hostels")?.size ?: 0)
            val overdue = res.data.obj("summary_current_page")?.int("overdue") ?: 0
            ModuleSummary(
                key = ModuleKey.TOKENS,
                value = "$listSize hostels",
                subtitle = "Overdue on page: $overdue",
                ok = true,
            )
        } catch (t: Throwable) {
            ModuleSummary(ModuleKey.TOKENS, "--", t.message ?: "Error", false)
        }
    }

    private suspend fun loadMaintenance(): ModuleSummary {
        return try {
            val res = api.maintenanceSchedule(15)
            if (!res.success || res.data == null) {
                return ModuleSummary(ModuleKey.MAINTENANCE, "--", res.message.ifBlank { "Failed" }, false)
            }
            val listSize = res.meta?.obj("pagination")?.int("total") ?: (res.data.arr("bikes")?.size ?: 0)
            val overdue = res.data.obj("summary")?.int("overdue") ?: 0
            ModuleSummary(
                key = ModuleKey.MAINTENANCE,
                value = "$listSize bikes",
                subtitle = "Overdue services: $overdue",
                ok = true,
            )
        } catch (t: Throwable) {
            ModuleSummary(ModuleKey.MAINTENANCE, "--", t.message ?: "Error", false)
        }
    }

    private suspend fun loadMasters(): ModuleSummary {
        return try {
            val bikes = api.bikes(15)
            if (!bikes.success || bikes.data == null) {
                return ModuleSummary(ModuleKey.MASTERS, "--", bikes.message.ifBlank { "Failed" }, false)
            }

            val bikeCount = bikes.meta?.obj("pagination")?.int("total") ?: (bikes.data.arr("bikes")?.size ?: 0)

            ModuleSummary(
                key = ModuleKey.MASTERS,
                value = "$bikeCount bikes",
                subtitle = "Registered bikes",
                ok = true,
            )
        } catch (t: Throwable) {
            ModuleSummary(ModuleKey.MASTERS, "--", t.message ?: "Error", false)
        }
    }

    private suspend fun loadRespondents(): ModuleSummary {
        return try {
            val respondents = api.respondents(15)
            if (!respondents.success || respondents.data == null) {
                return ModuleSummary(ModuleKey.RESPONDENTS, "--", respondents.message.ifBlank { "Failed" }, false)
            }

            val respondentCount = respondents.meta?.obj("pagination")?.int("total")
                ?: (respondents.data.arr("respondents")?.size ?: 0)

            ModuleSummary(
                key = ModuleKey.RESPONDENTS,
                value = "$respondentCount respondents",
                subtitle = "Master data",
                ok = true,
            )
        } catch (t: Throwable) {
            ModuleSummary(ModuleKey.RESPONDENTS, "--", t.message ?: "Error", false)
        }
    }
}
