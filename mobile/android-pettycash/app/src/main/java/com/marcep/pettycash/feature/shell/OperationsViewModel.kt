package com.marcep.pettycash.feature.shell

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.marcep.pettycash.core.model.ActionResult
import com.marcep.pettycash.core.network.PettyApiService
import com.marcep.pettycash.core.network.arr
import com.marcep.pettycash.core.network.asObj
import com.marcep.pettycash.core.network.double
import com.marcep.pettycash.core.network.int
import com.marcep.pettycash.core.network.obj
import com.marcep.pettycash.core.network.str
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.JsonObjectBuilder
import kotlinx.serialization.json.JsonPrimitive
import kotlinx.serialization.json.buildJsonObject
import java.text.DecimalFormat

data class MenuRecord(
    val id: Int? = null,
    val title: String,
    val subtitle: String = "",
    val meta: String = "",
    val tone: String = "default",
)

data class MenuListState(
    val loading: Boolean = false,
    val rows: List<MenuRecord> = emptyList(),
    val summary: String = "",
    val error: String? = null,
)

data class MenuLookupsState(
    val loading: Boolean = false,
    val error: String? = null,
    val availableBatchBalance: Double = 0.0,
    val batches: List<MenuRecord> = emptyList(),
    val bikes: List<MenuRecord> = emptyList(),
    val respondents: List<MenuRecord> = emptyList(),
    val hostels: List<MenuRecord> = emptyList(),
)

data class HostelPaymentsState(
    val loading: Boolean = false,
    val hostelId: Int? = null,
    val hostelTitle: String = "",
    val hostelMeta: String = "",
    val hostelMeterNo: String = "",
    val hostelPhone: String = "",
    val hostelStake: String = "",
    val hostelRouters: Int = 0,
    val hostelAmountDue: Double = 0.0,
    val dueBadge: String = "",
    val daysToDue: Int? = null,
    val defaultReceiverName: String = "",
    val defaultReceiverPhone: String = "",
    val rows: List<MenuRecord> = emptyList(),
    val summary: String = "",
    val error: String? = null,
)

@HiltViewModel
class OperationsViewModel @Inject constructor(
    private val api: PettyApiService,
) : ViewModel() {
    private val money = DecimalFormat("#,##0.00")

    private var creditsPerPage = 25
    private var spendingsPerPage = 25
    private var hostelsPerPage = 25
    private var schedulePerPage = 25
    private var bikesPerPage = 25
    private var respondentsPerPage = 25
    private var notificationsPerPage = 25
    private var hostelPaymentsPerPage = 20
    private var creditsQuery = ""
    private var spendingsQuery = ""
    private var hostelsQuery = ""
    private var maintenanceQuery = ""
    private var bikesQuery = ""
    private var respondentsQuery = ""
    private var notificationsQuery = ""

    private val _creditsState = MutableStateFlow(MenuListState())
    val creditsState: StateFlow<MenuListState> = _creditsState.asStateFlow()

    private val _spendingsState = MutableStateFlow(MenuListState())
    val spendingsState: StateFlow<MenuListState> = _spendingsState.asStateFlow()

    private val _hostelsState = MutableStateFlow(MenuListState())
    val hostelsState: StateFlow<MenuListState> = _hostelsState.asStateFlow()

    private val _hostelPaymentsState = MutableStateFlow(HostelPaymentsState())
    val hostelPaymentsState: StateFlow<HostelPaymentsState> = _hostelPaymentsState.asStateFlow()

    private val _maintenanceScheduleState = MutableStateFlow(MenuListState())
    val maintenanceScheduleState: StateFlow<MenuListState> = _maintenanceScheduleState.asStateFlow()

    private val _maintenanceHistoryState = MutableStateFlow(MenuListState())
    val maintenanceHistoryState: StateFlow<MenuListState> = _maintenanceHistoryState.asStateFlow()

    private val _maintenanceFlagsState = MutableStateFlow(MenuListState())
    val maintenanceFlagsState: StateFlow<MenuListState> = _maintenanceFlagsState.asStateFlow()

    private val _bikesState = MutableStateFlow(MenuListState())
    val bikesState: StateFlow<MenuListState> = _bikesState.asStateFlow()

    private val _respondentsState = MutableStateFlow(MenuListState())
    val respondentsState: StateFlow<MenuListState> = _respondentsState.asStateFlow()

    private val _notificationsState = MutableStateFlow(MenuListState())
    val notificationsState: StateFlow<MenuListState> = _notificationsState.asStateFlow()

    private val _lookupsState = MutableStateFlow(MenuLookupsState())
    val lookupsState: StateFlow<MenuLookupsState> = _lookupsState.asStateFlow()

    fun refreshCredits(perPage: Int = creditsPerPage) {
        creditsPerPage = perPage
        viewModelScope.launch { fetchCredits(perPage, creditsQuery) }
    }

    fun refreshSpendings(perPage: Int = spendingsPerPage) {
        spendingsPerPage = perPage
        viewModelScope.launch { fetchSpendings(perPage, spendingsQuery) }
    }

    fun refreshHostels(perPage: Int = hostelsPerPage) {
        hostelsPerPage = perPage
        viewModelScope.launch { fetchHostels(perPage, hostelsQuery) }
    }

    fun refreshMaintenance(perPage: Int = schedulePerPage) {
        schedulePerPage = perPage
        viewModelScope.launch {
            fetchMaintenanceSchedule(perPage, maintenanceQuery)
            fetchMaintenanceHistory(perPage, maintenanceQuery)
            fetchMaintenanceUnroadworthy(perPage, maintenanceQuery)
        }
    }

    fun refreshBikes(perPage: Int = bikesPerPage) {
        bikesPerPage = perPage
        viewModelScope.launch { fetchBikes(perPage, bikesQuery) }
    }

    fun refreshRespondents(perPage: Int = respondentsPerPage) {
        respondentsPerPage = perPage
        viewModelScope.launch { fetchRespondents(perPage, respondentsQuery) }
    }

    fun refreshNotifications(perPage: Int = notificationsPerPage) {
        notificationsPerPage = perPage
        viewModelScope.launch { fetchNotifications(perPage, notificationsQuery) }
    }

    fun refreshHostelPayments(hostelId: Int, perPage: Int = hostelPaymentsPerPage) {
        hostelPaymentsPerPage = perPage
        viewModelScope.launch { fetchHostelPayments(hostelId, perPage) }
    }

    fun clearHostelPayments() {
        _hostelPaymentsState.value = HostelPaymentsState()
    }

    fun refreshLookups() {
        viewModelScope.launch { fetchLookups() }
    }

    fun setCreditsQuery(value: String) {
        val normalized = value.trim()
        if (creditsQuery == normalized) return
        creditsQuery = normalized
        viewModelScope.launch { fetchCredits(creditsPerPage, creditsQuery) }
    }

    fun setSpendingsQuery(value: String) {
        val normalized = value.trim()
        if (spendingsQuery == normalized) return
        spendingsQuery = normalized
        viewModelScope.launch { fetchSpendings(spendingsPerPage, spendingsQuery) }
    }

    fun setHostelsQuery(value: String) {
        val normalized = value.trim()
        if (hostelsQuery == normalized) return
        hostelsQuery = normalized
        viewModelScope.launch { fetchHostels(hostelsPerPage, hostelsQuery) }
    }

    fun setMaintenanceQuery(value: String) {
        val normalized = value.trim()
        if (maintenanceQuery == normalized) return
        maintenanceQuery = normalized
        viewModelScope.launch {
            fetchMaintenanceSchedule(schedulePerPage, maintenanceQuery)
            fetchMaintenanceHistory(schedulePerPage, maintenanceQuery)
            fetchMaintenanceUnroadworthy(schedulePerPage, maintenanceQuery)
        }
    }

    fun setBikesQuery(value: String) {
        val normalized = value.trim()
        if (bikesQuery == normalized) return
        bikesQuery = normalized
        viewModelScope.launch { fetchBikes(bikesPerPage, bikesQuery) }
    }

    fun setRespondentsQuery(value: String) {
        val normalized = value.trim()
        if (respondentsQuery == normalized) return
        respondentsQuery = normalized
        viewModelScope.launch { fetchRespondents(respondentsPerPage, respondentsQuery) }
    }

    fun setNotificationsQuery(value: String) {
        val normalized = value.trim()
        if (notificationsQuery == normalized) return
        notificationsQuery = normalized
        viewModelScope.launch { fetchNotifications(notificationsPerPage, notificationsQuery) }
    }

    suspend fun createBike(plateNo: String, model: String, status: String): ActionResult<String> {
        val cleanPlateNo = plateNo.trim()
        if (cleanPlateNo.isBlank()) return ActionResult.failure("Plate number is required.")

        val payload = buildJsonObject {
            put("plate_no", JsonPrimitive(cleanPlateNo))
            putIfNotBlank("model", model)
            putIfNotBlank("status", status)
        }

        val response = safeApiCall { api.createBike(payload) }
        if (!response.ok) return ActionResult.failure(response.error ?: "Failed to create bike.")

        fetchBikes(bikesPerPage)
        fetchLookups()
        return ActionResult.success(response.data ?: "Bike created.")
    }

    suspend fun createRespondent(name: String, phone: String, category: String): ActionResult<String> {
        val cleanName = name.trim()
        if (cleanName.isBlank()) return ActionResult.failure("Respondent name is required.")

        val payload = buildJsonObject {
            put("name", JsonPrimitive(cleanName))
            putIfNotBlank("phone", phone)
            putIfNotBlank("category", category)
        }

        val response = safeApiCall { api.createRespondent(payload) }
        if (!response.ok) return ActionResult.failure(response.error ?: "Failed to create respondent.")

        fetchRespondents(respondentsPerPage)
        fetchLookups()
        return ActionResult.success(response.data ?: "Respondent created.")
    }

    suspend fun createCredit(
        amountRaw: String,
        transactionCostRaw: String,
        date: String,
        reference: String,
        description: String,
    ): ActionResult<String> {
        val amount = amountRaw.toDoubleOrNull()
            ?: return ActionResult.failure("Amount must be a valid number.")
        if (amount <= 0) return ActionResult.failure("Amount must be greater than 0.")
        if (date.trim().isBlank()) return ActionResult.failure("Date is required.")

        val payload = buildJsonObject {
            put("amount", JsonPrimitive(amount))
            put("date", JsonPrimitive(date.trim()))
            putIfDouble("transaction_cost", transactionCostRaw)
            putIfNotBlank("reference", reference)
            putIfNotBlank("description", description)
        }

        val response = safeApiCall { api.createCredit(payload) }
        if (!response.ok) return ActionResult.failure(response.error ?: "Failed to create credit.")

        fetchCredits(creditsPerPage)
        fetchLookups()
        return ActionResult.success(response.data ?: "Credit recorded.")
    }

    suspend fun createSpending(
        funding: String,
        batchIdRaw: String,
        type: String,
        subType: String,
        bikeIdRaw: String,
        amountRaw: String,
        transactionCostRaw: String,
        date: String,
        respondentIdRaw: String,
        reference: String,
        description: String,
        particulars: String,
    ): ActionResult<String> {
        val cleanType = type.trim().lowercase()
        if (cleanType !in listOf("bike", "meal", "other")) {
            return ActionResult.failure("Type must be bike, meal, or other.")
        }

        val cleanFunding = funding.trim().lowercase()
        if (cleanFunding !in listOf("auto", "single")) {
            return ActionResult.failure("Funding must be auto or single.")
        }

        val amount = amountRaw.toDoubleOrNull()
            ?: return ActionResult.failure("Amount must be a valid number.")
        if (amount <= 0) return ActionResult.failure("Amount must be greater than 0.")
        if (date.trim().isBlank()) return ActionResult.failure("Date is required.")
        if (reference.trim().isBlank()) return ActionResult.failure("Reference is required.")

        if (cleanFunding == "single" && batchIdRaw.trim().toIntOrNull() == null) {
            return ActionResult.failure("Batch ID is required when funding is single.")
        }

        if (cleanType == "bike" && bikeIdRaw.trim().toIntOrNull() == null) {
            return ActionResult.failure("Bike ID is required for bike spendings.")
        }

        val payload = buildJsonObject {
            put("funding", JsonPrimitive(cleanFunding))
            put("type", JsonPrimitive(cleanType))
            put("amount", JsonPrimitive(amount))
            put("date", JsonPrimitive(date.trim()))
            putIfInt("batch_id", batchIdRaw)
            putIfInt("bike_id", bikeIdRaw)
            putIfInt("respondent_id", respondentIdRaw)
            putIfDouble("transaction_cost", transactionCostRaw)
            putIfNotBlank("sub_type", subType)
            putIfNotBlank("reference", reference)
            putIfNotBlank("description", description)
            putIfNotBlank("particulars", particulars)
        }

        val response = safeApiCall { api.createSpending(payload) }
        if (!response.ok) return ActionResult.failure(response.error ?: "Failed to create spending.")

        fetchSpendings(spendingsPerPage)
        return ActionResult.success(response.data ?: "Spending recorded.")
    }

    suspend fun createHostel(
        hostelName: String,
        meterNo: String,
        phoneNo: String,
        noOfRoutersRaw: String,
        stake: String,
        amountDueRaw: String,
    ): ActionResult<String> {
        val cleanName = hostelName.trim()
        if (cleanName.isBlank()) return ActionResult.failure("Hostel name is required.")
        if (meterNo.trim().isBlank()) return ActionResult.failure("Meter number is required.")
        if (phoneNo.trim().isBlank()) return ActionResult.failure("Phone number is required.")
        val routers = noOfRoutersRaw.toIntOrNull()
            ?: return ActionResult.failure("Number of routers is required.")
        if (routers < 0) return ActionResult.failure("Number of routers cannot be negative.")

        val cleanStake = stake.trim().lowercase()
        if (cleanStake !in listOf("monthly", "semester")) {
            return ActionResult.failure("Stake must be monthly or semester.")
        }

        val amountDue = amountDueRaw.toDoubleOrNull()
            ?: return ActionResult.failure("Amount due must be a valid number.")
        if (amountDue <= 0) return ActionResult.failure("Amount due must be greater than 0.")

        val payload = buildJsonObject {
            put("hostel_name", JsonPrimitive(cleanName))
            put("stake", JsonPrimitive(cleanStake))
            put("amount_due", JsonPrimitive(amountDue))
            put("meter_no", JsonPrimitive(meterNo.trim()))
            put("phone_no", JsonPrimitive(phoneNo.trim()))
            put("no_of_routers", JsonPrimitive(routers))
        }

        val response = safeApiCall { api.createHostel(payload) }
        if (!response.ok) return ActionResult.failure(response.error ?: "Failed to create hostel.")

        fetchHostels(hostelsPerPage)
        fetchLookups()
        return ActionResult.success(response.data ?: "Hostel created.")
    }

    suspend fun createHostelPayment(
        hostelIdRaw: String,
        funding: String,
        batchIdRaw: String,
        amountRaw: String,
        transactionCostRaw: String,
        date: String,
        reference: String,
        receiverName: String,
        receiverPhone: String,
        notes: String,
        meterNo: String,
    ): ActionResult<String> {
        val hostelId = hostelIdRaw.trim().toIntOrNull()
            ?: return ActionResult.failure("Hostel ID is required.")

        val cleanFunding = funding.trim().lowercase()
        if (cleanFunding !in listOf("auto", "single")) {
            return ActionResult.failure("Funding must be auto or single.")
        }
        if (cleanFunding == "single" && batchIdRaw.trim().toIntOrNull() == null) {
            return ActionResult.failure("Batch ID is required when funding is single.")
        }

        val amount = amountRaw.toDoubleOrNull()
            ?: return ActionResult.failure("Amount must be a valid number.")
        if (amount <= 0) return ActionResult.failure("Amount must be greater than 0.")
        if (date.trim().isBlank()) return ActionResult.failure("Date is required.")
        if (reference.trim().isBlank()) return ActionResult.failure("Reference is required.")

        val payload = buildJsonObject {
            put("funding", JsonPrimitive(cleanFunding))
            put("amount", JsonPrimitive(amount))
            put("date", JsonPrimitive(date.trim()))
            putIfInt("batch_id", batchIdRaw)
            putIfDouble("transaction_cost", transactionCostRaw)
            put("reference", JsonPrimitive(reference.trim()))
            putIfNotBlank("receiver_name", receiverName)
            putIfNotBlank("receiver_phone", receiverPhone)
            putIfNotBlank("notes", notes)
            putIfNotBlank("meter_no", meterNo)
        }

        val response = safeApiCall { api.createHostelPayment(hostelId, payload) }
        if (!response.ok) return ActionResult.failure(response.error ?: "Failed to record payment.")

        fetchHostels(hostelsPerPage)
        fetchHostelPayments(hostelId, hostelPaymentsPerPage)
        return ActionResult.success(response.data ?: "Payment recorded.")
    }

    suspend fun updateHostel(
        hostelId: Int,
        hostelName: String,
        meterNo: String,
        phoneNo: String,
        noOfRoutersRaw: String,
        stake: String,
        amountDueRaw: String,
    ): ActionResult<String> {
        val cleanName = hostelName.trim()
        if (cleanName.isBlank()) return ActionResult.failure("Hostel name is required.")
        if (meterNo.trim().isBlank()) return ActionResult.failure("Meter number is required.")
        if (phoneNo.trim().isBlank()) return ActionResult.failure("Phone number is required.")

        val routers = noOfRoutersRaw.toIntOrNull()
            ?: return ActionResult.failure("Number of routers is required.")
        if (routers < 0) return ActionResult.failure("Number of routers cannot be negative.")

        val cleanStake = stake.trim().lowercase()
        if (cleanStake !in listOf("monthly", "semester")) {
            return ActionResult.failure("Stake must be monthly or semester.")
        }

        val amountDue = amountDueRaw.toDoubleOrNull()
            ?: return ActionResult.failure("Amount due must be a valid number.")
        if (amountDue <= 0) return ActionResult.failure("Amount due must be greater than 0.")

        val payload = buildJsonObject {
            put("hostel_name", JsonPrimitive(cleanName))
            put("meter_no", JsonPrimitive(meterNo.trim()))
            put("phone_no", JsonPrimitive(phoneNo.trim()))
            put("no_of_routers", JsonPrimitive(routers))
            put("stake", JsonPrimitive(cleanStake))
            put("amount_due", JsonPrimitive(amountDue))
        }

        val response = safeApiCall { api.updateHostel(hostelId, payload) }
        if (!response.ok) return ActionResult.failure(response.error ?: "Failed to update hostel.")

        fetchHostels(hostelsPerPage)
        fetchHostelPayments(hostelId, hostelPaymentsPerPage)
        fetchLookups()
        return ActionResult.success(response.data ?: "Hostel updated.")
    }

    suspend fun createBikeService(
        bikeIdRaw: String,
        serviceDate: String,
        nextDueDate: String,
        amountRaw: String,
        transactionCostRaw: String,
        reference: String,
        workDone: String,
    ): ActionResult<String> {
        val bikeId = bikeIdRaw.trim().toIntOrNull()
            ?: return ActionResult.failure("Bike ID is required.")
        if (serviceDate.trim().isBlank()) return ActionResult.failure("Service date is required.")

        val payload = buildJsonObject {
            put("service_date", JsonPrimitive(serviceDate.trim()))
            putIfNotBlank("next_due_date", nextDueDate)
            putIfDouble("amount", amountRaw)
            putIfDouble("transaction_cost", transactionCostRaw)
            putIfNotBlank("reference", reference)
            putIfNotBlank("work_done", workDone)
        }

        val response = safeApiCall { api.createBikeService(bikeId, payload) }
        if (!response.ok) return ActionResult.failure(response.error ?: "Failed to record service.")

        fetchMaintenanceSchedule(schedulePerPage)
        fetchMaintenanceHistory(schedulePerPage)
        fetchMaintenanceUnroadworthy(schedulePerPage)
        return ActionResult.success(response.data ?: "Service recorded.")
    }

    suspend fun setBikeUnroadworthy(
        bikeId: Int,
        isUnroadworthy: Boolean,
        notes: String,
    ): ActionResult<String> {
        val payload = buildJsonObject {
            put("is_unroadworthy", JsonPrimitive(isUnroadworthy))
            putIfNotBlank("unroadworthy_notes", notes)
        }

        val response = safeApiCall { api.setBikeUnroadworthy(bikeId, payload) }
        if (!response.ok) return ActionResult.failure(response.error ?: "Failed to update bike flag.")

        fetchMaintenanceSchedule(schedulePerPage)
        fetchMaintenanceHistory(schedulePerPage)
        fetchMaintenanceUnroadworthy(schedulePerPage)
        fetchBikes(bikesPerPage)
        fetchLookups()
        return ActionResult.success(response.data ?: "Bike status updated.")
    }

    suspend fun createNotification(
        title: String,
        message: String,
        type: String,
    ): ActionResult<String> {
        val cleanTitle = title.trim()
        val cleanMessage = message.trim()
        if (cleanTitle.isBlank()) return ActionResult.failure("Title is required.")
        if (cleanMessage.isBlank()) return ActionResult.failure("Message is required.")

        val payload = buildJsonObject {
            put("title", JsonPrimitive(cleanTitle))
            put("message", JsonPrimitive(cleanMessage))
            putIfNotBlank("type", type)
            put("channel", JsonPrimitive("app"))
        }

        val response = safeApiCall { api.createNotification(payload) }
        if (!response.ok) return ActionResult.failure(response.error ?: "Failed to create notification.")

        fetchNotifications(notificationsPerPage)
        return ActionResult.success(response.data ?: "Notification created.")
    }

    suspend fun markNotificationRead(notificationId: Int): ActionResult<String> {
        val response = safeApiCall { api.readNotification(notificationId) }
        if (!response.ok) return ActionResult.failure(response.error ?: "Failed to mark notification as read.")

        fetchNotifications(notificationsPerPage)
        return ActionResult.success(response.data ?: "Notification marked as read.")
    }

    suspend fun markAllNotificationsRead(): ActionResult<String> {
        val response = safeApiCall { api.readAllNotifications() }
        if (!response.ok) return ActionResult.failure(response.error ?: "Failed to mark all notifications as read.")

        fetchNotifications(notificationsPerPage)
        return ActionResult.success(response.data ?: "All notifications marked as read.")
    }

    private suspend fun fetchCredits(perPage: Int, query: String = creditsQuery) {
        _creditsState.update { it.copy(loading = true, error = null) }
        val response = safeApiCall { api.credits(perPage, query.ifBlank { null }) }
        if (!response.ok || response.payload == null) {
            _creditsState.update { it.copy(loading = false, error = response.error, rows = emptyList()) }
            return
        }

        val data = response.payload
        val rows = data.arr("credits")
            ?.mapNotNull { it.asObj() }
            ?.map { row ->
                val amount = row.double("net_amount") ?: row.double("amount") ?: 0.0
                val date = row.str("date").orEmpty()
                val batch = row.str("batch_no").orEmpty()
                MenuRecord(
                    id = row.int("id"),
                    title = "KES ${money.format(amount)}",
                    subtitle = "Batch: ${if (batch.isBlank()) "-" else batch} | Date: $date",
                    meta = row.str("description") ?: row.str("reference").orEmpty(),
                )
            }
            .orEmpty()

        val summary = data.obj("summary")
        val net = summary?.double("total_net_amount") ?: 0.0
        val total = paginationTotal(response.meta)
        _creditsState.update {
            it.copy(
                loading = false,
                rows = rows,
                summary = "Total credits: $total | Net total: KES ${money.format(net)}",
                error = null,
            )
        }
    }

    private suspend fun fetchSpendings(perPage: Int, query: String = spendingsQuery) {
        _spendingsState.update { it.copy(loading = true, error = null) }
        val response = safeApiCall { api.spendings(perPage, query.ifBlank { null }) }
        if (!response.ok || response.payload == null) {
            _spendingsState.update { it.copy(loading = false, error = response.error, rows = emptyList()) }
            return
        }

        val data = response.payload
        val rows = data.arr("spendings")
            ?.mapNotNull { it.asObj() }
            ?.map { row ->
                val type = row.str("type").orEmpty()
                val sub = row.str("sub_type").orEmpty()
                val title = buildString {
                    append(type.replaceFirstChar { c -> c.uppercase() })
                    if (sub.isNotBlank()) append(" / $sub")
                    append(" - KES ${money.format(row.double("total") ?: 0.0)}")
                }
                val batch = row.str("batch_no").orEmpty()
                val date = row.str("date").orEmpty()
                val reference = row.str("reference").orEmpty()
                val bikePlate = row.str("bike_plate_no").orEmpty()
                val respondent = row.str("respondent_name").orEmpty()
                val description = row.str("description").orEmpty()
                val particulars = row.str("particulars").orEmpty()
                val metaBits = when (type) {
                    "bike" -> listOfNotNull(
                        if (reference.isBlank()) null else "Ref: $reference",
                        if (bikePlate.isBlank()) null else "Bike: $bikePlate",
                        if (respondent.isBlank()) null else "By: $respondent",
                        if (particulars.isBlank()) null else particulars,
                    )

                    "meal" -> listOfNotNull(
                        if (reference.isBlank()) null else "Ref: $reference",
                        if (respondent.isBlank()) null else "By: $respondent",
                        if (description.isBlank()) null else description,
                    )

                    else -> listOfNotNull(
                        if (reference.isBlank()) null else "Ref: $reference",
                        if (respondent.isBlank()) null else "By: $respondent",
                        if (description.isBlank()) null else description,
                        if (particulars.isBlank()) null else particulars,
                    )
                }

                MenuRecord(
                    id = row.int("id"),
                    title = title,
                    subtitle = listOfNotNull(
                        "Batch: ${if (batch.isBlank()) "-" else batch}",
                        "Date: $date",
                        if (reference.isBlank()) null else "Ref: $reference",
                    ).joinToString(" | "),
                    meta = metaBits.joinToString(" | "),
                )
            }
            .orEmpty()

        val summary = data.obj("summary")
        val net = summary?.double("net_total") ?: 0.0
        val total = paginationTotal(response.meta)
        _spendingsState.update {
            it.copy(
                loading = false,
                rows = rows,
                summary = "Total spendings: $total | Net total: KES ${money.format(net)}",
                error = null,
            )
        }
    }

    private suspend fun fetchHostels(perPage: Int, query: String = hostelsQuery) {
        _hostelsState.update { it.copy(loading = true, error = null) }
        val response = safeApiCall { api.hostels(perPage, query.ifBlank { null }) }
        if (!response.ok || response.payload == null) {
            _hostelsState.update { it.copy(loading = false, error = response.error, rows = emptyList()) }
            return
        }

        val data = response.payload
        val rows = data.arr("hostels")
            ?.mapNotNull { it.asObj() }
            ?.map { row ->
                val hostel = row.str("hostel_name").orEmpty()
                val badge = row.str("due_badge").orEmpty()
                val amount = row.double("amount_due") ?: 0.0
                val stake = row.str("stake").orEmpty()
                val meterNo = row.str("meter_no").orEmpty()
                val daysToDue = row.int("days_to_due")
                MenuRecord(
                    id = row.int("id"),
                    title = hostel,
                    subtitle = listOfNotNull(
                        if (meterNo.isBlank()) null else "Meter: $meterNo",
                        "${stake.replaceFirstChar { c -> c.uppercase() }}",
                        "Due: KES ${money.format(amount)}",
                    ).joinToString(" | "),
                    meta = listOfNotNull(
                        badge.ifBlank { null },
                        if (daysToDue == null) null else "D-$daysToDue",
                    ).joinToString(" | "),
                    tone = row.str("due_status") ?: "default",
                )
            }
            .orEmpty()

        val summary = data.obj("summary_current_page")
        val overdue = summary?.int("overdue") ?: 0
        val dueToday = summary?.int("due_today") ?: 0
        val total = paginationTotal(response.meta)
        _hostelsState.update {
            it.copy(
                loading = false,
                rows = rows,
                summary = "Total hostels: $total | Due today: $dueToday | Overdue: $overdue",
                error = null,
            )
        }
    }

    private suspend fun fetchHostelPayments(hostelId: Int, perPage: Int) {
        _hostelPaymentsState.update {
            it.copy(
                loading = true,
                error = null,
                hostelId = hostelId,
            )
        }

        val response = safeApiCall { api.hostelDetails(hostelId, paymentsPerPage = perPage) }
        if (!response.ok || response.payload == null) {
            _hostelPaymentsState.update {
                it.copy(
                    loading = false,
                    error = response.error ?: "Failed to load hostel payments.",
                    rows = emptyList(),
                )
            }
            return
        }

        val data = response.payload
        val hostel = data.obj("hostel")
        val payments = data.arr("payments")
            ?.mapNotNull { it.asObj() }
            .orEmpty()
        val latestPayment = payments.firstOrNull()
        val rows = payments.map { row ->
            val total = row.double("total") ?: (row.double("amount") ?: 0.0)
            val batchNo = row.str("batch_no").orEmpty()
            val date = row.str("date").orEmpty()
            val receiver = row.str("receiver_name").orEmpty()
            val receiverPhone = row.str("receiver_phone").orEmpty()
            val reference = row.str("reference").orEmpty()

            MenuRecord(
                id = row.int("id"),
                title = "KES ${money.format(total)}",
                subtitle = "Date: $date | Batch: ${if (batchNo.isBlank()) "-" else batchNo}",
                meta = listOfNotNull(
                    if (reference.isBlank()) null else "Ref: $reference",
                    if (receiver.isBlank()) null else "To: $receiver",
                    if (receiverPhone.isBlank()) null else receiverPhone,
                ).joinToString(" | "),
            )
        }

        val hostelName = hostel?.str("hostel_name").orEmpty()
        val amountDue = hostel?.double("amount_due") ?: 0.0
        val dueBadge = hostel?.str("due_badge").orEmpty()
        val nextDueDate = hostel?.str("next_due_date").orEmpty()
        val meterNo = hostel?.str("meter_no").orEmpty()
        val phoneNo = hostel?.str("phone_no").orEmpty()
        val stake = hostel?.str("stake").orEmpty()
        val routers = hostel?.int("no_of_routers") ?: 0
        val daysToDue = hostel?.int("days_to_due")
        val defaultReceiverName = latestPayment?.str("receiver_name").orEmpty()
        val defaultReceiverPhone = latestPayment?.str("receiver_phone").orEmpty()
        val total = paginationTotal(response.meta)

        _hostelPaymentsState.update {
            it.copy(
                loading = false,
                hostelId = hostelId,
                hostelTitle = hostelName,
                hostelMeta = listOfNotNull(
                    "Due: KES ${money.format(amountDue)}",
                    if (stake.isBlank()) null else "Stake: $stake",
                    if (meterNo.isBlank()) null else "Meter: $meterNo",
                    if (phoneNo.isBlank()) null else "Phone: $phoneNo",
                    dueBadge.ifBlank { null },
                    if (nextDueDate.isBlank()) null else "Next due: $nextDueDate",
                ).joinToString(" | "),
                hostelMeterNo = meterNo,
                hostelPhone = phoneNo,
                hostelStake = stake,
                hostelRouters = routers,
                hostelAmountDue = amountDue,
                dueBadge = dueBadge,
                daysToDue = daysToDue,
                defaultReceiverName = defaultReceiverName,
                defaultReceiverPhone = defaultReceiverPhone,
                rows = rows,
                summary = "Total payments: $total",
                error = null,
            )
        }
    }

    private suspend fun fetchMaintenanceSchedule(perPage: Int, query: String = maintenanceQuery) {
        _maintenanceScheduleState.update { it.copy(loading = true, error = null) }
        val response = safeApiCall { api.maintenanceSchedule(perPage, query.ifBlank { null }) }
        if (!response.ok || response.payload == null) {
            _maintenanceScheduleState.update {
                it.copy(loading = false, error = response.error, rows = emptyList())
            }
            return
        }

        val data = response.payload
        val rows = data.arr("bikes")
            ?.mapNotNull { it.asObj() }
            ?.map { row ->
                val status = row.str("schedule_status").orEmpty()
                val due = row.str("next_service_due_date") ?: "-"
                val days = row.int("days_to_due")
                val dueText = when {
                    days == null -> "Due: $due"
                    days < 0 -> "Overdue by ${kotlin.math.abs(days)} day(s)"
                    days == 0 -> "Due today"
                    else -> "Due in $days day(s)"
                }
                MenuRecord(
                    id = row.int("id"),
                    title = row.str("plate_no") ?: "Bike #${row.int("id") ?: "-"}",
                    subtitle = dueText,
                    meta = row.str("model") ?: "",
                    tone = status,
                )
            }
            .orEmpty()

        val summary = data.obj("summary")
        val overdue = summary?.int("overdue") ?: 0
        val soon = summary?.int("due_soon") ?: 0
        val total = paginationTotal(response.meta)
        _maintenanceScheduleState.update {
            it.copy(
                loading = false,
                rows = rows,
                summary = "Total bikes: $total | Overdue: $overdue | Due soon: $soon",
                error = null,
            )
        }
    }

    private suspend fun fetchMaintenanceHistory(perPage: Int, query: String = maintenanceQuery) {
        _maintenanceHistoryState.update { it.copy(loading = true, error = null) }
        val response = safeApiCall { api.maintenanceHistory(perPage, query.ifBlank { null }) }
        if (!response.ok || response.payload == null) {
            _maintenanceHistoryState.update {
                it.copy(loading = false, error = response.error, rows = emptyList())
            }
            return
        }

        val data = response.payload
        val rows = data.arr("services")
            ?.mapNotNull { it.asObj() }
            ?.map { row ->
                val total = row.double("total") ?: 0.0
                MenuRecord(
                    id = row.int("id"),
                    title = row.str("bike_plate_no") ?: "Bike #${row.int("bike_id") ?: "-"}",
                    subtitle = "Service: ${row.str("service_date") ?: "-"} | KES ${money.format(total)}",
                    meta = row.str("work_done") ?: row.str("reference").orEmpty(),
                )
            }
            .orEmpty()

        val summary = data.obj("summary")
        val total = summary?.double("net_total") ?: 0.0
        val records = paginationTotal(response.meta)
        _maintenanceHistoryState.update {
            it.copy(
                loading = false,
                rows = rows,
                summary = "History records: $records | Service net total: KES ${money.format(total)}",
                error = null,
            )
        }
    }

    private suspend fun fetchMaintenanceUnroadworthy(perPage: Int, query: String = maintenanceQuery) {
        _maintenanceFlagsState.update { it.copy(loading = true, error = null) }
        val response = safeApiCall { api.maintenanceUnroadworthy(perPage, query.ifBlank { null }) }
        if (!response.ok || response.payload == null) {
            _maintenanceFlagsState.update {
                it.copy(loading = false, error = response.error, rows = emptyList())
            }
            return
        }

        val data = response.payload
        val rows = data.arr("bikes")
            ?.mapNotNull { it.asObj() }
            ?.map { row ->
                MenuRecord(
                    id = row.int("id"),
                    title = row.str("plate_no") ?: "Bike #${row.int("id") ?: "-"}",
                    subtitle = row.str("model") ?: "Unroadworthy",
                    meta = row.str("unroadworthy_notes").orEmpty(),
                    tone = "unroadworthy",
                )
            }
            .orEmpty()

        val total = paginationTotal(response.meta)
        _maintenanceFlagsState.update {
            it.copy(
                loading = false,
                rows = rows,
                summary = "Flagged bikes: $total",
                error = null,
            )
        }
    }

    private suspend fun fetchBikes(perPage: Int, query: String = bikesQuery) {
        _bikesState.update { it.copy(loading = true, error = null) }
        val response = safeApiCall { api.bikes(perPage, query.ifBlank { null }) }
        if (!response.ok || response.payload == null) {
            _bikesState.update { it.copy(loading = false, error = response.error, rows = emptyList()) }
            return
        }

        val data = response.payload
        val rows = data.arr("bikes")
            ?.mapNotNull { it.asObj() }
            ?.map { row ->
                MenuRecord(
                    id = row.int("id"),
                    title = row.str("plate_no") ?: "Bike #${row.int("id") ?: "-"}",
                    subtitle = "${row.str("model") ?: "-"} | ${row.str("status") ?: "-"}",
                    meta = "Next due: ${row.str("next_service_due_date") ?: "-"}",
                    tone = if (row.str("is_unroadworthy") == "true") "unroadworthy" else "default",
                )
            }
            .orEmpty()

        val total = paginationTotal(response.meta)
        _bikesState.update {
            it.copy(
                loading = false,
                rows = rows,
                summary = "Total bikes: $total",
                error = null,
            )
        }
    }

    private suspend fun fetchRespondents(perPage: Int, query: String = respondentsQuery) {
        _respondentsState.update { it.copy(loading = true, error = null) }
        val response = safeApiCall { api.respondents(perPage, query.ifBlank { null }) }
        if (!response.ok || response.payload == null) {
            _respondentsState.update {
                it.copy(loading = false, error = response.error, rows = emptyList())
            }
            return
        }

        val data = response.payload
        val rows = data.arr("respondents")
            ?.mapNotNull { it.asObj() }
            ?.map { row ->
                MenuRecord(
                    id = row.int("id"),
                    title = row.str("name") ?: "Respondent #${row.int("id") ?: "-"}",
                    subtitle = row.str("category") ?: "-",
                    meta = row.str("phone").orEmpty(),
                )
            }
            .orEmpty()

        val total = paginationTotal(response.meta)
        _respondentsState.update {
            it.copy(
                loading = false,
                rows = rows,
                summary = "Total respondents: $total",
                error = null,
            )
        }
    }

    private suspend fun fetchNotifications(perPage: Int, query: String = notificationsQuery) {
        _notificationsState.update { it.copy(loading = true, error = null) }
        val response = safeApiCall { api.notifications(perPage, query.ifBlank { null }) }
        if (!response.ok || response.payload == null) {
            _notificationsState.update {
                it.copy(loading = false, error = response.error, rows = emptyList())
            }
            return
        }

        val data = response.payload
        val rows = data.arr("notifications")
            ?.mapNotNull { it.asObj() }
            ?.map { row ->
                val isRead = when (row.str("is_read")?.trim()?.lowercase()) {
                    "1", "true", "yes" -> true
                    else -> false
                }
                val createdAt = row.str("created_at").orEmpty()
                val type = row.str("type").orEmpty()
                val hostelName = row.str("hostel_name").orEmpty()
                val meterNo = row.str("meter_no").orEmpty()
                val dueDate = row.str("due_date").orEmpty()
                val daysToDue = row.int("days_to_due")
                MenuRecord(
                    id = row.int("id"),
                    title = row.str("title") ?: "Notification #${row.int("id") ?: "-"}",
                    subtitle = row.str("message") ?: "",
                    meta = listOfNotNull(
                        if (type.isBlank()) null else "Type: $type",
                        if (hostelName.isBlank()) null else "Hostel: $hostelName",
                        if (meterNo.isBlank()) null else "Meter: $meterNo",
                        if (dueDate.isBlank()) null else "Due: $dueDate",
                        if (daysToDue == null) null else "D-$daysToDue",
                        if (createdAt.isBlank()) null else createdAt,
                    ).joinToString(" | "),
                    tone = if (isRead) "default" else "due_today",
                )
            }
            .orEmpty()

        val unreadCount = data.obj("summary")?.int("unread_count") ?: 0
        val total = paginationTotal(response.meta)
        _notificationsState.update {
            it.copy(
                loading = false,
                rows = rows,
                summary = "Total notifications: $total | Unread: $unreadCount",
                error = null,
            )
        }
    }

    private suspend fun fetchLookups() {
        _lookupsState.update { it.copy(loading = true, error = null) }
        val lookupsResponse = safeApiCall { api.reportLookups(150) }
        val batchesResponse = safeApiCall { api.availableBatches() }

        if (!lookupsResponse.ok || lookupsResponse.payload == null) {
            _lookupsState.update {
                it.copy(
                    loading = false,
                    error = lookupsResponse.error ?: "Failed to load lookups.",
                    availableBatchBalance = 0.0,
                    batches = emptyList(),
                    bikes = emptyList(),
                    respondents = emptyList(),
                    hostels = emptyList(),
                )
            }
            return
        }

        val lookups = lookupsResponse.payload
        val availableBatchBalance = batchesResponse.payload
            ?.arr("batches")
            ?.mapNotNull { it.asObj()?.double("available_balance") }
            ?.sum()
            ?: 0.0
        val lookupBatchRows = lookups.arr("batches")
            ?.mapNotNull { v -> v.asObj() }
            .orEmpty()
        val lookupBatchById = lookupBatchRows.associateBy { it.int("id") }

        val availableBatchRows = batchesResponse.payload
            ?.arr("batches")
            ?.mapNotNull { it.asObj() }
            ?.map { row ->
                val id = row.int("id")
                val lookupBatch = id?.let { lookupBatchById[it] }
                val available = row.double("available_balance") ?: 0.0
                val credited = lookupBatch?.double("credited_amount") ?: 0.0
                val opening = lookupBatch?.double("opening_balance") ?: 0.0
                MenuRecord(
                    id = id,
                    title = row.str("batch_no") ?: "Batch #${row.int("id") ?: "-"}",
                    subtitle = "Credited: KES ${money.format(credited)} | Available: KES ${money.format(available)}",
                    meta = "Opening: KES ${money.format(opening)} | ${row.str("created_at").orEmpty()}",
                )
            }
            .orEmpty()

        _lookupsState.update {
            it.copy(
                loading = false,
                error = null,
                availableBatchBalance = availableBatchBalance,
                batches = availableBatchRows,
                bikes = lookups.arr("bikes")
                    ?.mapNotNull { v -> v.asObj() }
                    ?.map { row ->
                        val flagged = row.str("is_unroadworthy") == "true"
                        MenuRecord(
                            id = row.int("id"),
                            title = (if (flagged) "[FLAGGED] " else "") + (row.str("plate_no") ?: "Bike #${row.int("id") ?: "-"}"),
                            subtitle = listOfNotNull(
                                row.str("model"),
                                row.str("status"),
                            ).joinToString(" | "),
                            meta = if (flagged) "Unroadworthy" else "Roadworthy",
                            tone = if (flagged) "unroadworthy" else "default",
                        )
                    }
                    .orEmpty(),
                respondents = lookups.arr("respondents")
                    ?.mapNotNull { v -> v.asObj() }
                    ?.map { row ->
                        MenuRecord(
                            id = row.int("id"),
                            title = row.str("name") ?: "Respondent #${row.int("id") ?: "-"}",
                            subtitle = row.str("category") ?: "Role pending",
                            meta = "Phone: ${row.str("phone").orEmpty()} | Role: -",
                        )
                    }
                    .orEmpty(),
                hostels = lookups.arr("hostels")
                    ?.mapNotNull { v -> v.asObj() }
                    ?.map { row ->
                        MenuRecord(
                            id = row.int("id"),
                            title = row.str("hostel_name") ?: "Hostel #${row.int("id") ?: "-"}",
                            subtitle = listOfNotNull(
                                row.str("stake"),
                                row.str("meter_no")?.let { "Meter: $it" },
                                row.str("phone_no")?.let { "Phone: $it" },
                            ).joinToString(" | "),
                            meta = "Due: KES ${money.format(row.double("amount_due") ?: 0.0)}",
                        )
                    }
                    .orEmpty(),
            )
        }
    }

    private suspend fun safeApiCall(call: suspend () -> com.marcep.pettycash.core.network.ApiEnvelope<JsonObject>): ApiCallResult {
        return try {
            val envelope = call()
            if (!envelope.success) {
                ApiCallResult(
                    ok = false,
                    error = envelope.message.ifBlank { "Request failed." },
                    payload = envelope.data,
                    meta = envelope.meta,
                )
            } else {
                ApiCallResult(
                    ok = true,
                    data = envelope.message.ifBlank { "Success." },
                    payload = envelope.data,
                    meta = envelope.meta,
                )
            }
        } catch (t: Throwable) {
            ApiCallResult(
                ok = false,
                error = t.message ?: "Network request failed.",
                payload = null,
                meta = null,
            )
        }
    }

    private data class ApiCallResult(
        val ok: Boolean,
        val data: String? = null,
        val error: String? = null,
        val payload: JsonObject? = null,
        val meta: JsonObject? = null,
    )

    private fun paginationTotal(meta: JsonObject?): Int {
        return meta
            ?.obj("pagination")
            ?.int("total")
            ?: 0
    }

    private fun JsonObjectBuilder.putIfNotBlank(key: String, raw: String?) {
        if (!raw.isNullOrBlank()) {
            put(key, JsonPrimitive(raw.trim()))
        }
    }

    private fun JsonObjectBuilder.putIfInt(key: String, raw: String?) {
        val parsed = raw?.trim()?.toIntOrNull() ?: return
        put(key, JsonPrimitive(parsed))
    }

    private fun JsonObjectBuilder.putIfDouble(key: String, raw: String?) {
        val parsed = raw?.trim()?.toDoubleOrNull() ?: return
        put(key, JsonPrimitive(parsed))
    }
}
