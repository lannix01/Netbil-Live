package com.marcep.pettycash.feature.shell

import android.Manifest
import android.content.Context
import android.graphics.Paint
import android.content.pm.PackageManager
import android.os.Build
import android.graphics.Bitmap
import android.graphics.pdf.PdfDocument
import android.os.Environment
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.systemBarsPadding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.DirectionsBike
import androidx.compose.material.icons.automirrored.filled.ExitToApp
import androidx.compose.material.icons.filled.AccountBalanceWallet
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Assessment
import androidx.compose.material.icons.filled.Bolt
import androidx.compose.material.icons.filled.Build
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.MarkEmailRead
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Payments
import androidx.compose.material.icons.filled.People
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DatePicker
import androidx.compose.material3.DatePickerDialog
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalDrawerSheet
import androidx.compose.material3.ModalNavigationDrawer
import androidx.compose.material3.NavigationDrawerItem
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.rememberDatePickerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.mutableStateMapOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.Saver
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.platform.LocalClipboardManager
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import androidx.hilt.navigation.compose.hiltViewModel
import com.marcep.pettycash.core.model.ActionResult
import com.marcep.pettycash.core.model.AuthSessionRecord
import com.marcep.pettycash.core.model.ModuleKey
import com.marcep.pettycash.core.model.ModuleSummary
import com.marcep.pettycash.core.notifications.ensureAlertChannel
import com.marcep.pettycash.core.notifications.showSystemAlert
import com.marcep.pettycash.feature.home.HomeUiState
import com.marcep.pettycash.feature.home.HomeViewModel
import com.marcep.pettycash.ui.theme.BrandPrimary
import com.google.zxing.BarcodeFormat
import com.google.zxing.qrcode.QRCodeWriter
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import java.time.Instant
import java.time.LocalDate
import java.time.ZoneId
import java.util.Locale
import java.io.File
import java.io.FileOutputStream

/**
 * Improvements packed into one file:
 * - Snackbars instead of blocking success/error dialogs
 * - Typed-ish form state objects with validation + keyboard types
 * - Stable list keys
 * - Clean section scaffold with empty states + loading state
 * - Refresh routing centralized
 * - Quick-pick chips for IDs (human friendly, no raw “Quick IDs” walls)
 */

private data class UiFeedback(
    val title: String,
    val message: String,
    val isError: Boolean,
)

private enum class FundingMode(val apiValue: String) { Auto("auto"), Single("single") }
private enum class SpendingType(val apiValue: String) { Bike("bike"), Meal("meal"), Other("other") }
private enum class LogoutTarget { Current, All }

private const val TableMinWidthDp = 760
private const val DuePollIntervalMs = 3 * 60 * 1000L
private const val CompanyName = "Marcep Agency"

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ShellScreen(
    userName: String?,
    userRole: String?,
    activeSessionsCount: Int?,
    activeSessionsError: String?,
    sessionScope: String,
    sessionRows: List<AuthSessionRecord>,
    onRefreshSessionStats: () -> Unit,
    onRevokeSession: (tokenId: Int, isCurrent: Boolean) -> Unit,
    onLogoutCurrent: () -> Unit,
    onLogoutAll: () -> Unit,
    homeViewModel: HomeViewModel = hiltViewModel(),
    operationsViewModel: OperationsViewModel = hiltViewModel(),
) {
    val scope = rememberCoroutineScope()
    val context = LocalContext.current
    val drawerState = androidx.compose.material3.rememberDrawerState(
        initialValue = androidx.compose.material3.DrawerValue.Closed,
    )

    var selectedSection by rememberSaveable { mutableStateOf(AppSection.DASHBOARD) }
    var feedback by remember { mutableStateOf<UiFeedback?>(null) }
    val pushedHostelDueIds = remember { mutableStateMapOf<Int, Long>() }

    val notificationPermissionLauncher = rememberLauncherForActivityResult(
        contract = ActivityResultContracts.RequestPermission(),
    ) { /* no-op */ }

    val snackbarHostState = remember { SnackbarHostState() }

    val homeState by homeViewModel.uiState.collectAsState()
    val creditsState by operationsViewModel.creditsState.collectAsState()
    val spendingsState by operationsViewModel.spendingsState.collectAsState()
    val hostelsState by operationsViewModel.hostelsState.collectAsState()
    val maintenanceScheduleState by operationsViewModel.maintenanceScheduleState.collectAsState()
    val maintenanceHistoryState by operationsViewModel.maintenanceHistoryState.collectAsState()
    val maintenanceFlagsState by operationsViewModel.maintenanceFlagsState.collectAsState()
    val bikesState by operationsViewModel.bikesState.collectAsState()
    val respondentsState by operationsViewModel.respondentsState.collectAsState()
    val notificationsState by operationsViewModel.notificationsState.collectAsState()
    val lookupsState by operationsViewModel.lookupsState.collectAsState()
    val hostelPaymentsState by operationsViewModel.hostelPaymentsState.collectAsState()

    fun emitFeedback(result: ActionResult<String>) {
        feedback = UiFeedback(
            title = if (result.ok) "Success" else "Action failed",
            message = result.data ?: result.error ?: "Done",
            isError = !result.ok,
        )
    }

    val refreshers: Map<AppSection, () -> Unit> = remember {
        mapOf(
            AppSection.DASHBOARD to { homeViewModel.refreshAll() },
            AppSection.CREDITS to { operationsViewModel.refreshCredits() },
            AppSection.SPENDINGS to {
                operationsViewModel.refreshSpendings()
                operationsViewModel.refreshLookups()
            },
            AppSection.TOKENS to {
                operationsViewModel.refreshHostels()
                operationsViewModel.refreshLookups()
            },
            AppSection.MAINTENANCE to {
                operationsViewModel.refreshMaintenance()
                operationsViewModel.refreshLookups()
            },
            AppSection.BIKES to { operationsViewModel.refreshBikes() },
            AppSection.RESPONDENTS to { operationsViewModel.refreshRespondents() },
            AppSection.NOTIFICATIONS to { operationsViewModel.refreshNotifications() },
            AppSection.REPORTS to { operationsViewModel.refreshLookups() },
            AppSection.SESSION to { onRefreshSessionStats() },
        )
    }

    fun refreshCurrentSection() {
        refreshers[selectedSection]?.invoke()
    }

    val drawerGroups = remember {
        listOf(
            "Overview" to listOf(AppSection.DASHBOARD, AppSection.REPORTS),
            "Money In" to listOf(AppSection.CREDITS),
            "Spendings" to listOf(AppSection.SPENDINGS, AppSection.TOKENS, AppSection.MAINTENANCE),
            "Master Data" to listOf(AppSection.BIKES, AppSection.RESPONDENTS),
            "System" to listOf(AppSection.NOTIFICATIONS, AppSection.SESSION),
        )
    }

    LaunchedEffect(Unit) {
        ensureAlertChannel(context)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) {
            notificationPermissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
        }
        homeViewModel.refreshAll()
        operationsViewModel.refreshLookups()
        operationsViewModel.refreshNotifications()
        operationsViewModel.refreshHostels()
    }

    LaunchedEffect(Unit) {
        // Foreground app poll loop every 3 minutes for due token alerts.
        while (true) {
            delay(DuePollIntervalMs)
            operationsViewModel.refreshHostels()
        }
    }

    LaunchedEffect(hostelsState.rows) {
        val now = System.currentTimeMillis()
        val dueRows = hostelsState.rows.filter { it.id != null && (it.tone == "due_today" || it.tone == "overdue") }
        dueRows.forEach { row ->
            val hostelId = row.id ?: return@forEach
            val lastNotifiedAt = pushedHostelDueIds[hostelId] ?: 0L
            if (now - lastNotifiedAt < DuePollIntervalMs) return@forEach
            showSystemAlert(
                context = context,
                notificationId = 900_000 + hostelId,
                title = "Token Due Alert",
                message = "${row.title}: ${row.meta.ifBlank { row.subtitle }}",
            )
            pushedHostelDueIds[hostelId] = now
        }
    }

    LaunchedEffect(selectedSection) {
        if (selectedSection != AppSection.TOKENS) {
            operationsViewModel.clearHostelPayments()
        }
        refreshCurrentSection()
    }

    LaunchedEffect(feedback) {
        val info = feedback ?: return@LaunchedEffect
        snackbarHostState.showSnackbar(
            message = "${info.title}: ${info.message}",
            withDismissAction = true,
        )
        // keep last feedback available for logs if you want; UX-wise, we clear it
        feedback = null
    }

    ModalNavigationDrawer(
        drawerState = drawerState,
        drawerContent = {
            ModalDrawerSheet(
                modifier = Modifier.widthIn(max = 300.dp),
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxHeight()
                        .verticalScroll(rememberScrollState()),
                ) {
                    Spacer(Modifier.height(16.dp))
                    Text(
                        text = "Skybrix PettyCash",
                        style = MaterialTheme.typography.titleLarge,
                        modifier = Modifier.padding(horizontal = 16.dp),
                    )
                    Text(
                        text = "Operations ledger",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(horizontal = 16.dp),
                    )
                    Spacer(Modifier.height(12.dp))
                    drawerGroups.forEach { (groupTitle, sections) ->
                        Text(
                            text = groupTitle.uppercase(),
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(horizontal = 18.dp, vertical = 6.dp),
                        )
                        sections.forEach { section ->
                            NavigationDrawerItem(
                                label = { Text(section.title) },
                                selected = selectedSection == section,
                                onClick = {
                                    selectedSection = section
                                    scope.launch { drawerState.close() }
                                },
                                icon = { Icon(sectionIcon(section), contentDescription = null) },
                                modifier = Modifier.padding(horizontal = 12.dp, vertical = 2.dp),
                            )
                        }
                    }
                    Spacer(Modifier.height(12.dp))
                }
            }
        },
    ) {
        Scaffold(
            snackbarHost = { SnackbarHost(hostState = snackbarHostState) },
            topBar = {
                TopAppBar(
                    modifier = Modifier.systemBarsPadding(),
                    title = {
                        Column {
                            Text(selectedSection.title)
                            Text(
                                selectedSection.subtitle,
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                    },
                    navigationIcon = {
                        IconButton(onClick = { scope.launch { drawerState.open() } }) {
                            Icon(Icons.Default.Menu, contentDescription = "Open menu")
                        }
                    },
                    actions = {
                        IconButton(onClick = { refreshCurrentSection() }) {
                            Icon(Icons.Default.Refresh, contentDescription = "Refresh")
                        }
                    },
                )
            },
        ) { innerPadding ->
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(innerPadding)
                    .background(
                        brush = Brush.verticalGradient(
                            colors = listOf(
                                MaterialTheme.colorScheme.background,
                                MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.25f),
                            ),
                        ),
                    )
                    .imePadding(),
            ) {
                when (selectedSection) {
                    AppSection.DASHBOARD -> DashboardSection(
                        homeState = homeState,
                        onOpenSection = { selectedSection = it },
                    )

                    AppSection.CREDITS -> CreditsSection(
                        state = creditsState,
                        onSearchQueryChanged = operationsViewModel::setCreditsQuery,
                        onCreate = { form ->
                            scope.launch {
                                emitFeedback(
                                    operationsViewModel.createCredit(
                                        amountRaw = form.amount,
                                        transactionCostRaw = form.fee,
                                        date = form.date,
                                        reference = form.reference,
                                        description = form.description,
                                    ),
                                )
                            }
                        },
                        onPerPageSelected = { operationsViewModel.refreshCredits(it) },
                    )

                    AppSection.SPENDINGS -> SpendingsSection(
                        state = spendingsState,
                        lookups = lookupsState,
                        onSearchQueryChanged = operationsViewModel::setSpendingsQuery,
                        onCreate = { form ->
                            scope.launch {
                                val requestedAmount = form.amount.toDoubleOrNull() ?: 0.0
                                val available = lookupsState.availableBatchBalance
                                if (lookupsState.batches.isNotEmpty() && requestedAmount > available) {
                                    emitFeedback(
                                        ActionResult.failure(
                                            "Insufficient available balance. Requested KES ${moneyValue(requestedAmount)}, available KES ${moneyValue(available)}.",
                                        ),
                                    )
                                    return@launch
                                }
                                emitFeedback(
                                    operationsViewModel.createSpending(
                                        funding = form.funding.apiValue,
                                        batchIdRaw = form.batchId,
                                        type = form.type.apiValue,
                                        subType = form.subType,
                                        bikeIdRaw = form.bikeId,
                                        amountRaw = form.amount,
                                        transactionCostRaw = form.fee,
                                        date = form.date,
                                        respondentIdRaw = form.respondentId,
                                        reference = form.reference,
                                        description = form.description,
                                        particulars = form.particulars,
                                    ),
                                )
                            }
                        },
                        onPerPageSelected = { operationsViewModel.refreshSpendings(it) },
                    )

                    AppSection.TOKENS -> TokensSection(
                        hostelsState = hostelsState,
                        hostelPaymentsState = hostelPaymentsState,
                        lookups = lookupsState,
                        onCreateHostel = { form ->
                            scope.launch {
                                emitFeedback(
                                    operationsViewModel.createHostel(
                                        form.name,
                                        form.meterNo,
                                        form.phone,
                                        form.routers,
                                        form.stake,
                                        form.amountDue,
                                    ),
                                )
                            }
                        },
                        onCreatePayment = { hostelId, form ->
                            scope.launch {
                                emitFeedback(
                                    operationsViewModel.createHostelPayment(
                                        hostelIdRaw = hostelId.toString(),
                                        funding = form.funding.apiValue,
                                        batchIdRaw = form.batchId,
                                        amountRaw = form.amount,
                                        transactionCostRaw = form.fee,
                                        date = form.date,
                                        reference = form.reference,
                                        receiverName = form.receiverName,
                                        receiverPhone = form.receiverPhone,
                                        notes = form.notes,
                                        meterNo = form.meterNo,
                                    ),
                                )
                            }
                        },
                        onUpdateHostel = { hostelId, form ->
                            scope.launch {
                                emitFeedback(
                                    operationsViewModel.updateHostel(
                                        hostelId = hostelId,
                                        hostelName = form.name,
                                        meterNo = form.meterNo,
                                        phoneNo = form.phone,
                                        noOfRoutersRaw = form.routers,
                                        stake = form.stake,
                                        amountDueRaw = form.amountDue,
                                    ),
                                )
                            }
                        },
                        onOpenHostel = { hostelId, paymentsPerPage ->
                            operationsViewModel.refreshHostelPayments(hostelId, paymentsPerPage)
                        },
                        onPerPageSelected = { operationsViewModel.refreshHostels(it) },
                        onHostelsSearchQueryChanged = operationsViewModel::setHostelsQuery,
                    )

                    AppSection.MAINTENANCE -> MaintenanceSection(
                        scheduleState = maintenanceScheduleState,
                        historyState = maintenanceHistoryState,
                        flagsState = maintenanceFlagsState,
                        lookups = lookupsState,
                        onSearchQueryChanged = operationsViewModel::setMaintenanceQuery,
                        onCreateService = { form ->
                            scope.launch {
                                emitFeedback(
                                    operationsViewModel.createBikeService(
                                        bikeIdRaw = form.bikeId,
                                        serviceDate = form.serviceDate,
                                        nextDueDate = form.nextDueDate,
                                        amountRaw = form.amount,
                                        transactionCostRaw = form.fee,
                                        reference = form.reference,
                                        workDone = form.workDone,
                                    ),
                                )
                            }
                        },
                        onSetUnroadworthy = { bikeId, isUnroadworthy, notes ->
                            scope.launch {
                                emitFeedback(
                                    operationsViewModel.setBikeUnroadworthy(
                                        bikeId = bikeId,
                                        isUnroadworthy = isUnroadworthy,
                                        notes = notes,
                                    ),
                                )
                            }
                        },
                        onPerPageSelected = { operationsViewModel.refreshMaintenance(it) },
                    )

                    AppSection.BIKES -> BikesSection(
                        state = bikesState,
                        onSearchQueryChanged = operationsViewModel::setBikesQuery,
                        onCreate = { form ->
                            scope.launch { emitFeedback(operationsViewModel.createBike(form.plateNo, form.model, form.status)) }
                        },
                        onPerPageSelected = { operationsViewModel.refreshBikes(it) },
                    )

                    AppSection.RESPONDENTS -> RespondentsSection(
                        state = respondentsState,
                        onSearchQueryChanged = operationsViewModel::setRespondentsQuery,
                        onCreate = { form ->
                            scope.launch { emitFeedback(operationsViewModel.createRespondent(form.name, form.phone, form.category)) }
                        },
                        onPerPageSelected = { operationsViewModel.refreshRespondents(it) },
                    )

                    AppSection.NOTIFICATIONS -> NotificationsSection(
                        state = notificationsState,
                        onPerPageSelected = { operationsViewModel.refreshNotifications(it) },
                        onSearchQueryChanged = operationsViewModel::setNotificationsQuery,
                        onCreate = { form ->
                            scope.launch { emitFeedback(operationsViewModel.createNotification(form.title, form.message, form.type)) }
                        },
                        onReadOne = { id ->
                            scope.launch { emitFeedback(operationsViewModel.markNotificationRead(id)) }
                        },
                        onReadAll = {
                            scope.launch { emitFeedback(operationsViewModel.markAllNotificationsRead()) }
                        },
                    )

                    AppSection.REPORTS -> ReportsSection(
                        lookups = lookupsState,
                        onRefresh = { operationsViewModel.refreshLookups() },
                    )

                    AppSection.SESSION -> SessionSection(
                        userName = userName,
                        userRole = userRole,
                        activeSessionsCount = activeSessionsCount,
                        activeSessionsError = activeSessionsError,
                        sessionScope = sessionScope,
                        sessionRows = sessionRows,
                        onRefreshSessionStats = onRefreshSessionStats,
                        onRevokeSession = onRevokeSession,
                        onLogoutCurrent = onLogoutCurrent,
                        onLogoutAll = onLogoutAll,
                    )
                }
            }
        }
    }
}

/* ----------------------------- Dashboard ----------------------------- */

@Composable
private fun DashboardSection(
    homeState: HomeUiState,
    onOpenSection: (AppSection) -> Unit,
) {
    val moduleRows = remember(homeState.modules) { dashboardRows(homeState.modules) }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            SectionHeader(
                summary = "Live totals across pettycash modules",
                error = homeState.error,
                loading = homeState.loading,
            )
        }

        if (!homeState.loading && homeState.modules.isEmpty()) {
            item {
                EmptyStateCard(
                    title = "No module summaries yet",
                    message = "Pull refresh or check connectivity.",
                )
            }
        } else {
            items(moduleRows, key = { row -> row.firstOrNull()?.key?.name ?: "row" }) { row ->
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp),
                ) {
                    row.forEach { summary ->
                        val destination = dashboardSectionFor(summary.key)
                        Card(
                            modifier = Modifier
                                .weight(1f)
                                .height(148.dp)
                                .clickable(enabled = destination != null) { destination?.let(onOpenSection) },
                            colors = CardDefaults.cardColors(
                                containerColor = if (summary.ok) MaterialTheme.colorScheme.surface else MaterialTheme.colorScheme.errorContainer,
                            ),
                        ) {
                            Column(
                                modifier = Modifier
                                    .fillMaxSize()
                                    .padding(12.dp),
                                verticalArrangement = Arrangement.spacedBy(6.dp),
                            ) {
                                Text(
                                    text = dashboardLabel(summary.key),
                                    style = MaterialTheme.typography.labelLarge,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                    maxLines = 2,
                                    overflow = TextOverflow.Clip,
                                )
                                Text(
                                    text = summary.value,
                                    style = dashboardValueTextStyle(summary.value),
                                    fontWeight = FontWeight.SemiBold,
                                    color = dashboardValueColor(summary.key),
                                    maxLines = 3,
                                    overflow = TextOverflow.Clip,
                                )
                                if (summary.subtitle.isNotBlank()) {
                                    Text(
                                        text = summary.subtitle,
                                        style = MaterialTheme.typography.bodySmall.copy(fontSize = MaterialTheme.typography.bodySmall.fontSize * 0.95f),
                                        maxLines = 2,
                                        overflow = TextOverflow.Clip,
                                    )
                                }
                            }
                        }
                    }
                    if (row.size == 1) Spacer(modifier = Modifier.weight(1f))
                }
            }

            item {
                Card(
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                ) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(10.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp),
                    ) {
                        Text(
                            text = "Quick Actions",
                            style = MaterialTheme.typography.labelLarge,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                        ) {
                            DashboardQuickAction("Credit", Icons.Default.AccountBalanceWallet, modifier = Modifier.weight(1f)) {
                                onOpenSection(AppSection.CREDITS)
                            }
                            DashboardQuickAction("Spending", Icons.Default.Payments, modifier = Modifier.weight(1f)) {
                                onOpenSection(AppSection.SPENDINGS)
                            }
                        }
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                        ) {
                            DashboardQuickAction("Hostels", Icons.Default.Home, modifier = Modifier.weight(1f)) {
                                onOpenSection(AppSection.TOKENS)
                            }
                            DashboardQuickAction("Notify", Icons.Default.Notifications, modifier = Modifier.weight(1f)) {
                                onOpenSection(AppSection.NOTIFICATIONS)
                            }
                        }
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                        ) {
                            DashboardQuickAction("Bikes", Icons.AutoMirrored.Filled.DirectionsBike, modifier = Modifier.weight(1f)) {
                                onOpenSection(AppSection.BIKES)
                            }
                            DashboardQuickAction("Respondents", Icons.Default.People, modifier = Modifier.weight(1f)) {
                                onOpenSection(AppSection.RESPONDENTS)
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun DashboardQuickAction(
    label: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    modifier: Modifier = Modifier,
    onClick: () -> Unit,
) {
    Button(
        onClick = onClick,
        modifier = modifier,
        contentPadding = PaddingValues(horizontal = 10.dp, vertical = 8.dp),
        colors = ButtonDefaults.buttonColors(
            containerColor = MaterialTheme.colorScheme.secondaryContainer,
            contentColor = MaterialTheme.colorScheme.onSecondaryContainer,
        ),
    ) {
        Icon(icon, contentDescription = null)
        Spacer(Modifier.width(6.dp))
        Text(label, maxLines = 1, overflow = TextOverflow.Ellipsis)
    }
}

/* ----------------------------- Credits ----------------------------- */

private data class CreditFormState(
    val amount: String = "",
    val fee: String = "0",
    val date: String = todayDate(),
    val reference: String = "",
    val description: String = "",
) {
    fun isValid(): Boolean = amount.toDoubleOrNull()?.let { it > 0 } == true && isIsoDate(date)
}

private val CreditFormSaver: Saver<CreditFormState, Map<String, String>> = Saver(
    save = {
        mapOf(
            "amount" to it.amount,
            "fee" to it.fee,
            "date" to it.date,
            "reference" to it.reference,
            "description" to it.description,
        )
    },
    restore = {
        CreditFormState(
            amount = it["amount"] ?: "",
            fee = it["fee"] ?: "0",
            date = it["date"] ?: todayDate(),
            reference = it["reference"] ?: "",
            description = it["description"] ?: "",
        )
    },
)

@Composable
private fun CreditsSection(
    state: MenuListState,
    onSearchQueryChanged: (String) -> Unit,
    onCreate: (CreditFormState) -> Unit,
    onPerPageSelected: (Int) -> Unit,
) {
    var perPage by rememberSaveable { mutableStateOf(25) }
    var showCreate by rememberSaveable { mutableStateOf(false) }
    var form by rememberSaveable(stateSaver = CreditFormSaver) { mutableStateOf(CreditFormState()) }

    ListSectionScaffold(
        summary = state.summary,
        loading = state.loading,
        error = state.error,
        perPage = perPage,
        onPerPageSelected = {
            perPage = it
            onPerPageSelected(it)
        },
        onSearchQueryChange = onSearchQueryChanged,
        primaryAction = { PrimaryActionButton("Add Credit", icon = Icons.Default.Add) { showCreate = true } },
        rows = state.rows,
        emptyTitle = "No credits yet",
        emptyMessage = "Record your first credit to start tracking funds.",
    )

    if (showCreate) {
        AppFormDialog(
            title = "Record Credit",
            onDismiss = { showCreate = false },
            onSubmit = {
                onCreate(form)
                form = CreditFormState() // reset
                showCreate = false
            },
            submitLabel = "Save Credit",
            submitEnabled = form.isValid(),
        ) {
            MoneyField("Amount", form.amount) { form = form.copy(amount = it) }
            MoneyField("Transaction Cost", form.fee) { form = form.copy(fee = it) }
            DateField("Date (YYYY-MM-DD)", form.date) { form = form.copy(date = it) }
            LabeledField("Reference (optional)", form.reference) { form = form.copy(reference = it) }
            LabeledField("Description (optional)", form.description, singleLine = false) { form = form.copy(description = it) }
            InlineValidationHint(ok = form.isValid(), message = "Amount > 0 and valid date required.")
        }
    }
}

/* ----------------------------- Spendings ----------------------------- */

private data class SpendingFormState(
    val funding: FundingMode = FundingMode.Auto,
    val batchId: String = "",
    val type: SpendingType = SpendingType.Bike,
    val subType: String = "fuel",
    val bikeId: String = "",
    val amount: String = "",
    val fee: String = "0",
    val date: String = todayDate(),
    val respondentId: String = "",
    val reference: String = "",
    val description: String = "",
    val particulars: String = "",
) {
    fun isValid(): Boolean {
        val amtOk = amount.toDoubleOrNull()?.let { it > 0 } == true
        val dateOk = isIsoDate(date)
        val batchOk = (funding != FundingMode.Single) || batchId.isNotBlank()
        val bikeOk = (type != SpendingType.Bike) || bikeId.isNotBlank()
        val maintOk = (type != SpendingType.Bike || subType.lowercase() != "maintenance") || particulars.isNotBlank()
        val referenceOk = reference.isNotBlank()
        return amtOk && dateOk && batchOk && bikeOk && maintOk && referenceOk
    }
}

private val SpendingFormSaver: Saver<SpendingFormState, Map<String, String>> = Saver(
    save = {
        mapOf(
            "funding" to it.funding.name,
            "batchId" to it.batchId,
            "type" to it.type.name,
            "subType" to it.subType,
            "bikeId" to it.bikeId,
            "amount" to it.amount,
            "fee" to it.fee,
            "date" to it.date,
            "respondentId" to it.respondentId,
            "reference" to it.reference,
            "description" to it.description,
            "particulars" to it.particulars,
        )
    },
    restore = {
        SpendingFormState(
            funding = runCatching { FundingMode.valueOf(it["funding"] ?: FundingMode.Auto.name) }.getOrDefault(FundingMode.Auto),
            batchId = it["batchId"] ?: "",
            type = runCatching { SpendingType.valueOf(it["type"] ?: SpendingType.Bike.name) }.getOrDefault(SpendingType.Bike),
            subType = it["subType"] ?: "fuel",
            bikeId = it["bikeId"] ?: "",
            amount = it["amount"] ?: "",
            fee = it["fee"] ?: "0",
            date = it["date"] ?: todayDate(),
            respondentId = it["respondentId"] ?: "",
            reference = it["reference"] ?: "",
            description = it["description"] ?: "",
            particulars = it["particulars"] ?: "",
        )
    },
)

@Composable
private fun SpendingsSection(
    state: MenuListState,
    lookups: MenuLookupsState,
    onSearchQueryChanged: (String) -> Unit,
    onCreate: (SpendingFormState) -> Unit,
    onPerPageSelected: (Int) -> Unit,
) {
    var perPage by rememberSaveable { mutableStateOf(25) }
    var showCreate by rememberSaveable { mutableStateOf(false) }
    var form by rememberSaveable(stateSaver = SpendingFormSaver) { mutableStateOf(SpendingFormState()) }

    ListSectionScaffold(
        summary = state.summary,
        loading = state.loading,
        error = state.error,
        perPage = perPage,
        onPerPageSelected = {
            perPage = it
            onPerPageSelected(it)
        },
        onSearchQueryChange = onSearchQueryChanged,
        primaryAction = { PrimaryActionButton("Add Spending", icon = Icons.Default.Add) { showCreate = true } },
        rows = state.rows,
        emptyTitle = "No spendings yet",
        emptyMessage = "Record spendings to track fuel, meals, maintenance, and other costs.",
    )

    if (showCreate) {
        val enteredAmount = form.amount.toDoubleOrNull() ?: 0.0
        val exceedsBalance = enteredAmount > 0 && enteredAmount > lookups.availableBatchBalance

        AppFormDialog(
            title = "Record Spending",
            onDismiss = { showCreate = false },
            onSubmit = {
                onCreate(form)
                form = SpendingFormState()
                showCreate = false
            },
            submitLabel = "Save Spending",
            submitEnabled = form.isValid() && !exceedsBalance,
        ) {
            Card(
                colors = CardDefaults.cardColors(
                    containerColor = if (exceedsBalance) {
                        MaterialTheme.colorScheme.errorContainer
                    } else {
                        MaterialTheme.colorScheme.secondaryContainer.copy(alpha = 0.55f)
                    },
                ),
            ) {
                Column(
                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 8.dp),
                    verticalArrangement = Arrangement.spacedBy(2.dp),
                ) {
                    Text(
                        text = "Available balance: KES ${moneyValue(lookups.availableBatchBalance)}",
                        style = MaterialTheme.typography.bodySmall,
                        color = if (exceedsBalance) MaterialTheme.colorScheme.onErrorContainer else MaterialTheme.colorScheme.onSecondaryContainer,
                    )
                    if (exceedsBalance) {
                        Text(
                            text = "Entered amount is above available balance.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.error,
                        )
                    }
                }
            }
            DropdownField(
                label = "Funding",
                selectedValue = form.funding.apiValue,
                options = listOf(
                    DropdownOption("Auto", FundingMode.Auto.apiValue),
                    DropdownOption("Single", FundingMode.Single.apiValue),
                ),
                onSelected = {
                    form = form.copy(
                        funding = if (it == FundingMode.Single.apiValue) FundingMode.Single else FundingMode.Auto,
                    )
                },
            )
            if (form.funding == FundingMode.Single) {
                DropdownField(
                    label = "Batch",
                    selectedValue = form.batchId,
                    options = lookups.batches
                        .mapNotNull { row -> row.id?.let { DropdownOption("#$it • ${row.title}", it.toString()) } },
                    onSelected = { form = form.copy(batchId = it) },
                )
                val selectedBatch = lookups.batches.firstOrNull { it.id?.toString() == form.batchId }
                if (selectedBatch != null) {
                    Text(
                        text = selectedBatch.subtitle.ifBlank { "No available balance data for selected batch." },
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.primary,
                    )
                }
            }

            DropdownField(
                label = "Category",
                selectedValue = form.type.apiValue,
                options = listOf(
                    DropdownOption("Bike", SpendingType.Bike.apiValue),
                    DropdownOption("Meal", SpendingType.Meal.apiValue),
                    DropdownOption("Other", SpendingType.Other.apiValue),
                ),
                onSelected = {
                    form = form.copy(
                        type = when (it) {
                            SpendingType.Meal.apiValue -> SpendingType.Meal
                            SpendingType.Other.apiValue -> SpendingType.Other
                            else -> SpendingType.Bike
                        },
                    )
                },
            )

            if (form.type == SpendingType.Bike) {
                DropdownField(
                    label = "Bike Sub-Type",
                    selectedValue = form.subType,
                    options = listOf(
                        DropdownOption("Fuel", "fuel"),
                        DropdownOption("Maintenance", "maintenance"),
                    ),
                    onSelected = { form = form.copy(subType = it) },
                )
                DropdownField(
                    label = "Bike",
                    selectedValue = form.bikeId,
                    options = lookups.bikes
                        .mapNotNull { row -> row.id?.let { DropdownOption("#$it • ${row.title}", it.toString()) } },
                    onSelected = { form = form.copy(bikeId = it) },
                )
                LabeledField("Particulars (maintenance needs this)", form.particulars, singleLine = false) { form = form.copy(particulars = it) }
            }

            MoneyField("Amount", form.amount) { form = form.copy(amount = it) }
            MoneyField("Transaction Cost", form.fee) { form = form.copy(fee = it) }
            DateField("Date (YYYY-MM-DD)", form.date) { form = form.copy(date = it) }
            DropdownField(
                label = "Respondent (optional)",
                selectedValue = form.respondentId,
                options = lookups.respondents
                    .mapNotNull { row -> row.id?.let { DropdownOption("#$it • ${row.title}", it.toString()) } },
                onSelected = { form = form.copy(respondentId = it) },
                includeEmptyOption = true,
            )
            LabeledField("Reference", form.reference) { form = form.copy(reference = it) }
            LabeledField("Description (optional)", form.description, singleLine = false) { form = form.copy(description = it) }
            InlineValidationHint(
                ok = form.isValid() && !exceedsBalance,
                message = if (exceedsBalance) {
                    "Amount exceeds available balance."
                } else {
                    "Required: amount>0, valid date, reference. If funding=single -> batch. If category=bike -> bike."
                },
            )
        }
    }
}

/* ----------------------------- Tokens (Hostels + Payments) ----------------------------- */

private data class HostelFormState(
    val name: String = "",
    val meterNo: String = "",
    val phone: String = "",
    val routers: String = "0",
    val stake: String = "monthly",
    val amountDue: String = "",
) {
    fun isValid(): Boolean {
        return name.isNotBlank() &&
                meterNo.isNotBlank() &&
                phone.isNotBlank() &&
                routers.toIntOrNull()?.let { it >= 0 } == true &&
                amountDue.toDoubleOrNull()?.let { it > 0 } == true
    }
}

private val HostelFormSaver: Saver<HostelFormState, Map<String, String>> = Saver(
    save = {
        mapOf(
            "name" to it.name,
            "meterNo" to it.meterNo,
            "phone" to it.phone,
            "routers" to it.routers,
            "stake" to it.stake,
            "amountDue" to it.amountDue,
        )
    },
    restore = {
        HostelFormState(
            name = it["name"] ?: "",
            meterNo = it["meterNo"] ?: "",
            phone = it["phone"] ?: "",
            routers = it["routers"] ?: "0",
            stake = it["stake"] ?: "monthly",
            amountDue = it["amountDue"] ?: "",
        )
    },
)

private data class PaymentFormState(
    val funding: FundingMode = FundingMode.Auto,
    val batchId: String = "",
    val amount: String = "",
    val fee: String = "0",
    val date: String = todayDate(),
    val reference: String = "",
    val receiverName: String = "",
    val receiverPhone: String = "",
    val notes: String = "",
    val meterNo: String = "",
) {
    fun isValid(): Boolean {
        val amtOk = amount.toDoubleOrNull()?.let { it > 0 } == true
        val dateOk = isIsoDate(date)
        val batchOk = (funding != FundingMode.Single) || batchId.isNotBlank()
        val referenceOk = reference.isNotBlank()
        return amtOk && dateOk && batchOk && referenceOk
    }
}

private val PaymentFormSaver: Saver<PaymentFormState, Map<String, String>> = Saver(
    save = {
        mapOf(
            "funding" to it.funding.name,
            "batchId" to it.batchId,
            "amount" to it.amount,
            "fee" to it.fee,
            "date" to it.date,
            "reference" to it.reference,
            "receiverName" to it.receiverName,
            "receiverPhone" to it.receiverPhone,
            "notes" to it.notes,
            "meterNo" to it.meterNo,
        )
    },
    restore = {
        PaymentFormState(
            funding = runCatching { FundingMode.valueOf(it["funding"] ?: FundingMode.Auto.name) }.getOrDefault(FundingMode.Auto),
            batchId = it["batchId"] ?: "",
            amount = it["amount"] ?: "",
            fee = it["fee"] ?: "0",
            date = it["date"] ?: todayDate(),
            reference = it["reference"] ?: "",
            receiverName = it["receiverName"] ?: "",
            receiverPhone = it["receiverPhone"] ?: "",
            notes = it["notes"] ?: "",
            meterNo = it["meterNo"] ?: "",
        )
    },
)

@Composable
private fun TokensSection(
    hostelsState: MenuListState,
    hostelPaymentsState: HostelPaymentsState,
    lookups: MenuLookupsState,
    onCreateHostel: (HostelFormState) -> Unit,
    onCreatePayment: (hostelId: Int, form: PaymentFormState) -> Unit,
    onUpdateHostel: (hostelId: Int, form: HostelFormState) -> Unit,
    onOpenHostel: (hostelId: Int, paymentsPerPage: Int) -> Unit,
    onPerPageSelected: (Int) -> Unit,
    onHostelsSearchQueryChanged: (String) -> Unit,
) {
    var hostelsPerPage by rememberSaveable { mutableStateOf(25) }
    var paymentsPerPage by rememberSaveable { mutableStateOf(20) }
    var selectedHostelId by rememberSaveable { mutableStateOf<Int?>(null) }
    var hostelsQuery by rememberSaveable { mutableStateOf("") }
    var paymentsQuery by rememberSaveable { mutableStateOf("") }
    var selectedRowDetail by remember { mutableStateOf<MenuRecord?>(null) }

    var showHostelCreate by rememberSaveable { mutableStateOf(false) }
    var showHostelEdit by rememberSaveable { mutableStateOf(false) }
    var showPaymentCreate by rememberSaveable { mutableStateOf(false) }

    var hostelForm by rememberSaveable(stateSaver = HostelFormSaver) { mutableStateOf(HostelFormState()) }
    var hostelEditForm by rememberSaveable(stateSaver = HostelFormSaver) { mutableStateOf(HostelFormState()) }
    var paymentForm by rememberSaveable(stateSaver = PaymentFormSaver) { mutableStateOf(PaymentFormState()) }

    val selectedHostel = selectedHostelId
    val hostelRows = remember(hostelsState.rows, hostelsQuery) {
        filterRows(hostelsState.rows, hostelsQuery)
    }
    val paymentRows = remember(hostelPaymentsState.rows, paymentsQuery) {
        filterRows(hostelPaymentsState.rows, paymentsQuery)
    }
    val tableScroll = rememberScrollState()
    val paymentTableScroll = rememberScrollState()

    fun openHostelDetails(hostelId: Int) {
        selectedHostelId = hostelId
        paymentsQuery = ""
        onOpenHostel(hostelId, paymentsPerPage)
    }

    fun prefillPaymentForm() {
        paymentForm = paymentForm.copy(
            date = todayDate(),
            meterNo = hostelPaymentsState.hostelMeterNo.ifBlank { paymentForm.meterNo },
            receiverName = hostelPaymentsState.defaultReceiverName.ifBlank { paymentForm.receiverName },
            receiverPhone = hostelPaymentsState.defaultReceiverPhone.ifBlank { paymentForm.receiverPhone },
        )
    }

    fun prefillHostelEditForm() {
        hostelEditForm = HostelFormState(
            name = hostelPaymentsState.hostelTitle,
            meterNo = hostelPaymentsState.hostelMeterNo,
            phone = hostelPaymentsState.hostelPhone,
            routers = hostelPaymentsState.hostelRouters.toString(),
            stake = hostelPaymentsState.hostelStake.ifBlank { "monthly" },
            amountDue = hostelPaymentsState.hostelAmountDue.toString(),
        )
    }

    Column(
        modifier = Modifier.fillMaxSize(),
    ) {
        Column(
            modifier = Modifier.padding(horizontal = 16.dp, vertical = 12.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.Top,
            ) {
                Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    SectionHeader(
                        summary = hostelsState.summary,
                        error = hostelsState.error,
                        loading = hostelsState.loading,
                    )
                    Text(
                        text = "Open hostel to view details and payments.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                PrimaryActionButton("Add Hostel", icon = Icons.Default.Add) { showHostelCreate = true }
            }

            PerPageChooser(selected = hostelsPerPage, label = "Hostels") {
                hostelsPerPage = it
                onPerPageSelected(it)
            }
        }

        Column(
            modifier = Modifier
                .fillMaxWidth()
                .weight(1f),
        ) {
            Text(
                text = "Hostels",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
                modifier = Modifier.padding(horizontal = 16.dp),
            )
            Spacer(Modifier.height(6.dp))
            SearchField(
                query = hostelsQuery,
                onQueryChange = {
                    hostelsQuery = it
                    onHostelsSearchQueryChanged(it)
                },
                hint = "Search hostels",
                modifier = Modifier.padding(horizontal = 16.dp),
            )
            Spacer(Modifier.height(8.dp))
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .weight(1f)
                    .padding(horizontal = 16.dp)
                    .horizontalScroll(tableScroll),
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .widthIn(min = TableMinWidthDp.dp),
                ) {
                    TableHeaderRow(withAction = true, actionTitle = "View")
                    if (!hostelsState.loading && hostelRows.isEmpty()) {
                        EmptyStateCard(
                            title = "No hostels found",
                            message = "Add hostels and tap one to view all payments under it.",
                        )
                    } else {
                        LazyColumn(
                            modifier = Modifier.fillMaxSize(),
                            contentPadding = PaddingValues(vertical = 4.dp),
                            verticalArrangement = Arrangement.spacedBy(6.dp),
                        ) {
                            items(hostelRows, key = { it.id ?: it.title }) { row ->
                                TableDataRow(
                                    row = row,
                                    selected = selectedHostel == row.id,
                                    withAction = true,
                                    onAction = {
                                        row.id?.let { hostelId ->
                                            openHostelDetails(hostelId)
                                        }
                                    },
                                )
                            }
                        }
                    }
                }
            }
        }
    }

    if (selectedHostel != null) {
        val dueColor = when {
            hostelPaymentsState.daysToDue == null -> MaterialTheme.colorScheme.surfaceVariant
            hostelPaymentsState.daysToDue < 0 -> MaterialTheme.colorScheme.errorContainer
            hostelPaymentsState.daysToDue == 0 -> MaterialTheme.colorScheme.tertiaryContainer
            hostelPaymentsState.daysToDue <= 3 -> MaterialTheme.colorScheme.secondaryContainer
            else -> MaterialTheme.colorScheme.surfaceVariant
        }

        androidx.compose.ui.window.Dialog(onDismissRequest = { selectedHostelId = null }) {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .heightIn(max = 680.dp),
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(14.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    Text(
                        text = hostelPaymentsState.hostelTitle.ifBlank { "Hostel #$selectedHostel" },
                        style = MaterialTheme.typography.titleLarge,
                    )
                    if (hostelPaymentsState.hostelMeta.isNotBlank()) {
                        Text(
                            text = hostelPaymentsState.hostelMeta,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                    if (hostelPaymentsState.loading) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                        ) {
                            CircularProgressIndicator(modifier = Modifier.width(14.dp), strokeWidth = 2.dp)
                            Text("Loading hostel details...", style = MaterialTheme.typography.bodySmall)
                        }
                    }

                    Card(
                        colors = CardDefaults.cardColors(containerColor = dueColor),
                    ) {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(10.dp),
                            verticalArrangement = Arrangement.spacedBy(4.dp),
                        ) {
                            Text(
                                text = hostelPaymentsState.dueBadge.ifBlank { "Due status unavailable" },
                                style = MaterialTheme.typography.labelLarge,
                                fontWeight = FontWeight.SemiBold,
                            )
                            Text(
                                text = "Amount due: KES ${moneyValue(hostelPaymentsState.hostelAmountDue)}",
                                style = MaterialTheme.typography.bodySmall,
                            )
                            if (hostelPaymentsState.daysToDue != null) {
                                Text(
                                    text = "Days to due: ${hostelPaymentsState.daysToDue}",
                                    style = MaterialTheme.typography.bodySmall,
                                )
                            }
                        }
                    }

                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        SecondaryActionButton(
                            text = "Edit Hostel",
                            icon = Icons.Default.Settings,
                            enabled = hostelPaymentsState.hostelId == selectedHostel,
                        ) {
                            prefillHostelEditForm()
                            showHostelEdit = true
                        }
                        PrimaryActionButton(
                            text = "Record Payment",
                            icon = Icons.Default.Payments,
                            enabled = hostelPaymentsState.hostelId == selectedHostel,
                        ) {
                            prefillPaymentForm()
                            showPaymentCreate = true
                        }
                        SecondaryActionButton("Close", icon = Icons.Default.Visibility) {
                            selectedHostelId = null
                        }
                    }

                    PerPageChooser(
                        selected = paymentsPerPage,
                        options = listOf(10, 20, 50, 100),
                        label = "Payments",
                    ) {
                        paymentsPerPage = it
                        onOpenHostel(selectedHostel, it)
                    }
                    SearchField(
                        query = paymentsQuery,
                        onQueryChange = { paymentsQuery = it },
                        hint = "Search payments",
                    )

                    Box(
                        modifier = Modifier
                            .weight(1f)
                            .horizontalScroll(paymentTableScroll),
                    ) {
                        Column(
                            modifier = Modifier
                                .fillMaxSize()
                                .widthIn(min = TableMinWidthDp.dp),
                        ) {
                            TableHeaderRow(withAction = false)

                            if (!hostelPaymentsState.loading && paymentRows.isEmpty()) {
                                EmptyStateCard(
                                    title = "No payments yet",
                                    message = "Record payment for this hostel from this dialog.",
                                )
                            } else {
                                LazyColumn(
                                    modifier = Modifier.fillMaxSize(),
                                    contentPadding = PaddingValues(top = 0.dp, bottom = 8.dp),
                                    verticalArrangement = Arrangement.spacedBy(6.dp),
                                ) {
                                    items(paymentRows, key = { it.id ?: it.title }) { row ->
                                        TableDataRow(
                                            row = row,
                                            onClick = { selectedRowDetail = row },
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    val tokenRowDetail = selectedRowDetail
    if (tokenRowDetail != null) {
        RowDetailDialog(row = tokenRowDetail, onDismiss = { selectedRowDetail = null })
    }

    if (showHostelCreate) {
        AppFormDialog(
            title = "Add Hostel",
            onDismiss = { showHostelCreate = false },
            onSubmit = {
                onCreateHostel(hostelForm)
                hostelForm = HostelFormState()
                showHostelCreate = false
            },
            submitLabel = "Save Hostel",
            submitEnabled = hostelForm.isValid(),
        ) {
            LabeledField("Hostel Name", hostelForm.name) { hostelForm = hostelForm.copy(name = it) }
            LabeledField("Meter No", hostelForm.meterNo) { hostelForm = hostelForm.copy(meterNo = it) }
            LabeledField("Phone", hostelForm.phone) { hostelForm = hostelForm.copy(phone = it) }
            IntField("Routers", hostelForm.routers) { hostelForm = hostelForm.copy(routers = it) }

            DropdownField(
                label = "Stake",
                selectedValue = hostelForm.stake,
                options = listOf(
                    DropdownOption("Monthly", "monthly"),
                    DropdownOption("Semester", "semester"),
                ),
                onSelected = { hostelForm = hostelForm.copy(stake = it) },
            )

            MoneyField("Amount Due", hostelForm.amountDue) { hostelForm = hostelForm.copy(amountDue = it) }
            InlineValidationHint(ok = hostelForm.isValid(), message = "Required: name, meter no, phone, routers>=0, amountDue>0")
        }
    }

    if (showHostelEdit) {
        val currentHostelId = selectedHostelId
        AppFormDialog(
            title = "Edit Hostel",
            onDismiss = { showHostelEdit = false },
            onSubmit = {
                currentHostelId?.let { hostelId ->
                    onUpdateHostel(hostelId, hostelEditForm)
                    showHostelEdit = false
                }
            },
            submitLabel = "Save Changes",
            submitEnabled = hostelEditForm.isValid() && currentHostelId != null,
        ) {
            LabeledField("Hostel Name", hostelEditForm.name) { hostelEditForm = hostelEditForm.copy(name = it) }
            LabeledField("Meter No", hostelEditForm.meterNo) { hostelEditForm = hostelEditForm.copy(meterNo = it) }
            LabeledField("Phone", hostelEditForm.phone) { hostelEditForm = hostelEditForm.copy(phone = it) }
            IntField("Routers", hostelEditForm.routers) { hostelEditForm = hostelEditForm.copy(routers = it) }
            DropdownField(
                label = "Stake",
                selectedValue = hostelEditForm.stake,
                options = listOf(
                    DropdownOption("Monthly", "monthly"),
                    DropdownOption("Semester", "semester"),
                ),
                onSelected = { hostelEditForm = hostelEditForm.copy(stake = it) },
            )
            MoneyField("Amount Due", hostelEditForm.amountDue) { hostelEditForm = hostelEditForm.copy(amountDue = it) }
            InlineValidationHint(ok = hostelEditForm.isValid(), message = "Required: name, meter, phone, routers>=0, amountDue>0")
        }
    }

    if (showPaymentCreate) {
        val currentHostelId = selectedHostelId
        val paymentAmount = paymentForm.amount.toDoubleOrNull() ?: 0.0
        val exceedsBalance = paymentAmount > 0 && paymentAmount > lookups.availableBatchBalance
        AppFormDialog(
            title = "Record Hostel Payment",
            onDismiss = { showPaymentCreate = false },
            onSubmit = {
                currentHostelId?.let { hostelId ->
                    onCreatePayment(hostelId, paymentForm)
                    paymentForm = PaymentFormState()
                    showPaymentCreate = false
                }
            },
            submitLabel = "Save Payment",
            submitEnabled = paymentForm.isValid() && !exceedsBalance && currentHostelId != null,
        ) {
            Text(
                text = "Hostel: ${hostelPaymentsState.hostelTitle.ifBlank { currentHostelId?.let { "Hostel #$it" } ?: "-" }}",
                style = MaterialTheme.typography.labelMedium,
            )
            Text(
                text = listOfNotNull(
                    if (hostelPaymentsState.hostelMeterNo.isBlank()) null else "Meter: ${hostelPaymentsState.hostelMeterNo}",
                    if (hostelPaymentsState.hostelPhone.isBlank()) null else "Phone: ${hostelPaymentsState.hostelPhone}",
                ).ifEmpty { listOf("Hostel profile data unavailable") }.joinToString(" | "),
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Card(
                colors = CardDefaults.cardColors(
                    containerColor = if (exceedsBalance) {
                        MaterialTheme.colorScheme.errorContainer
                    } else {
                        MaterialTheme.colorScheme.secondaryContainer.copy(alpha = 0.55f)
                    },
                ),
            ) {
                Column(
                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 8.dp),
                    verticalArrangement = Arrangement.spacedBy(2.dp),
                ) {
                    Text(
                        text = "Available balance: KES ${moneyValue(lookups.availableBatchBalance)}",
                        style = MaterialTheme.typography.bodySmall,
                        color = if (exceedsBalance) MaterialTheme.colorScheme.onErrorContainer else MaterialTheme.colorScheme.onSecondaryContainer,
                    )
                    if (exceedsBalance) {
                        Text(
                            text = "Payment amount is above available balance.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.error,
                        )
                    }
                }
            }

            DropdownField(
                label = "Funding",
                selectedValue = paymentForm.funding.apiValue,
                options = listOf(
                    DropdownOption("Auto", FundingMode.Auto.apiValue),
                    DropdownOption("Single", FundingMode.Single.apiValue),
                ),
                onSelected = {
                    paymentForm = paymentForm.copy(
                        funding = if (it == FundingMode.Single.apiValue) FundingMode.Single else FundingMode.Auto,
                    )
                },
            )
            if (paymentForm.funding == FundingMode.Single) {
                DropdownField(
                    label = "Batch",
                    selectedValue = paymentForm.batchId,
                    options = lookups.batches
                        .mapNotNull { row -> row.id?.let { DropdownOption("#$it • ${row.title}", it.toString()) } },
                    onSelected = { paymentForm = paymentForm.copy(batchId = it) },
                )
            }

            MoneyField("Amount", paymentForm.amount) { paymentForm = paymentForm.copy(amount = it) }
            MoneyField("Transaction Cost", paymentForm.fee) { paymentForm = paymentForm.copy(fee = it) }
            DateField("Date (YYYY-MM-DD)", paymentForm.date) { paymentForm = paymentForm.copy(date = it) }

            LabeledField("Reference", paymentForm.reference) { paymentForm = paymentForm.copy(reference = it) }
            LabeledField("Receiver Name (optional)", paymentForm.receiverName) { paymentForm = paymentForm.copy(receiverName = it) }
            LabeledField("Receiver Phone (optional)", paymentForm.receiverPhone) { paymentForm = paymentForm.copy(receiverPhone = it) }
            LabeledField("Notes (optional)", paymentForm.notes, singleLine = false) { paymentForm = paymentForm.copy(notes = it) }
            LabeledField("Meter No (optional)", paymentForm.meterNo) { paymentForm = paymentForm.copy(meterNo = it) }

            InlineValidationHint(
                ok = paymentForm.isValid() && !exceedsBalance,
                message = if (exceedsBalance) {
                    "Amount exceeds available balance."
                } else {
                    "Required: amount>0, valid date, reference. If funding=single -> batch."
                },
            )
        }
    }
}

/* ----------------------------- Maintenance ----------------------------- */

private data class ServiceFormState(
    val entryType: String = "service",
    val bikeId: String = "",
    val serviceDate: String = todayDate(),
    val nextDueDate: String = nextDueInDays(todayDate(), 21),
    val amount: String = "0",
    val fee: String = "0",
    val reference: String = "",
    val workDone: String = "",
) {
    fun isValid(): Boolean = bikeId.isNotBlank() && isIsoDate(serviceDate) && (nextDueDate.isBlank() || isIsoDate(nextDueDate))
}

private val ServiceFormSaver: Saver<ServiceFormState, Map<String, String>> = Saver(
    save = {
        mapOf(
            "entryType" to it.entryType,
            "bikeId" to it.bikeId,
            "serviceDate" to it.serviceDate,
            "nextDueDate" to it.nextDueDate,
            "amount" to it.amount,
            "fee" to it.fee,
            "reference" to it.reference,
            "workDone" to it.workDone,
        )
    },
    restore = {
        ServiceFormState(
            entryType = it["entryType"] ?: "service",
            bikeId = it["bikeId"] ?: "",
            serviceDate = it["serviceDate"] ?: todayDate(),
            nextDueDate = it["nextDueDate"] ?: nextDueInDays(it["serviceDate"] ?: todayDate(), 21),
            amount = it["amount"] ?: "0",
            fee = it["fee"] ?: "0",
            reference = it["reference"] ?: "",
            workDone = it["workDone"] ?: "",
        )
    },
)

@Composable
private fun MaintenanceSection(
    scheduleState: MenuListState,
    historyState: MenuListState,
    flagsState: MenuListState,
    lookups: MenuLookupsState,
    onSearchQueryChanged: (String) -> Unit,
    onCreateService: (ServiceFormState) -> Unit,
    onSetUnroadworthy: (bikeId: Int, isUnroadworthy: Boolean, notes: String) -> Unit,
    onPerPageSelected: (Int) -> Unit,
) {
    var perPage by rememberSaveable { mutableStateOf(25) }
    var query by rememberSaveable { mutableStateOf("") }
    var showCreate by rememberSaveable { mutableStateOf(false) }
    var form by rememberSaveable(stateSaver = ServiceFormSaver) { mutableStateOf(ServiceFormState()) }
    var selectedBikeActionRow by remember { mutableStateOf<MenuRecord?>(null) }
    var selectedRowDetail by remember { mutableStateOf<MenuRecord?>(null) }
    var unroadworthyNotes by rememberSaveable { mutableStateOf("") }
    val scheduleTableScroll = rememberScrollState()
    val historyTableScroll = rememberScrollState()
    val flagsTableScroll = rememberScrollState()

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp),
    ) {
        item {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(10.dp),
                verticalAlignment = Alignment.Top,
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    SectionHeader(
                        summary = scheduleState.summary,
                        error = scheduleState.error ?: historyState.error ?: flagsState.error,
                        loading = scheduleState.loading || historyState.loading || flagsState.loading,
                    )
                }
                PrimaryActionButton("Add Service", icon = Icons.Default.Add) { showCreate = true }
            }
            Spacer(Modifier.height(8.dp))

            PerPageChooser(selected = perPage) {
                perPage = it
                onPerPageSelected(it)
            }

            Spacer(Modifier.height(8.dp))
            IdQuickPickRow(
                title = "Bikes",
                items = lookups.bikes,
                idOf = { it.id },
                labelOf = { "${it.id}:${it.title}" },
                onPick = { pickedId -> form = form.copy(bikeId = pickedId.toString()) },
            )
            Spacer(Modifier.height(8.dp))
            SearchField(
                query = query,
                onQueryChange = {
                    query = it
                    onSearchQueryChanged(it)
                },
                hint = "Search maintenance records",
            )

            Spacer(Modifier.height(10.dp))
            Text("Schedule", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Spacer(Modifier.height(6.dp))
            Box(modifier = Modifier.horizontalScroll(scheduleTableScroll)) {
                TableHeaderRow(withAction = false)
            }
        }

        if (!scheduleState.loading && scheduleState.rows.isEmpty()) {
            item {
                EmptyStateCard(
                    title = "No scheduled services",
                    message = "Add a service entry to populate the schedule.",
                )
            }
        } else {
            items(scheduleState.rows, key = { it.id ?: it.title }) { row ->
                Box(modifier = Modifier.horizontalScroll(scheduleTableScroll)) {
                    TableDataRow(
                        row = row,
                        onClick = {
                            selectedBikeActionRow = row
                            unroadworthyNotes = ""
                        },
                    )
                }
            }
        }

        item {
            Spacer(Modifier.height(8.dp))
            HorizontalDivider()
            Spacer(Modifier.height(8.dp))
            Text("Service History", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Spacer(Modifier.height(6.dp))
            Box(modifier = Modifier.horizontalScroll(historyTableScroll)) {
                TableHeaderRow(withAction = false)
            }
        }

        if (!historyState.loading && historyState.rows.isEmpty()) {
            item { EmptyStateCard(title = "No history yet", message = "Services you record will show here.") }
        } else {
            items(historyState.rows, key = { "h-${it.id ?: it.title}" }) { row ->
                Box(modifier = Modifier.horizontalScroll(historyTableScroll)) {
                    TableDataRow(row = row, onClick = { selectedRowDetail = row })
                }
            }
        }

        item {
            Spacer(Modifier.height(8.dp))
            HorizontalDivider()
            Spacer(Modifier.height(8.dp))
            Text("Unroadworthy", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Spacer(Modifier.height(6.dp))
            Box(modifier = Modifier.horizontalScroll(flagsTableScroll)) {
                TableHeaderRow(withAction = false)
            }
        }

        if (!flagsState.loading && flagsState.rows.isEmpty()) {
            item { EmptyStateCard(title = "No flagged bikes", message = "Unroadworthy bikes will show here.") }
        } else {
            items(flagsState.rows, key = { "f-${it.id ?: it.title}" }) { row ->
                Box(modifier = Modifier.horizontalScroll(flagsTableScroll)) {
                    TableDataRow(
                        row = row,
                        onClick = {
                            selectedBikeActionRow = row
                            unroadworthyNotes = row.meta
                        },
                    )
                }
            }
        }
    }

    val bikeActionRow = selectedBikeActionRow
    if (bikeActionRow != null) {
        val bikeId = bikeActionRow.id
        val currentlyFlagged = bikeActionRow.tone == "unroadworthy"
        androidx.compose.ui.window.Dialog(onDismissRequest = { selectedBikeActionRow = null }) {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .heightIn(max = 460.dp),
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(14.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    Text(
                        text = bikeActionRow.title,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                    )
                    if (bikeActionRow.subtitle.isNotBlank()) {
                        Text(bikeActionRow.subtitle, style = MaterialTheme.typography.bodySmall)
                    }
                    if (bikeActionRow.meta.isNotBlank()) {
                        Text(
                            bikeActionRow.meta,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                    LabeledField(
                        label = "Unroadworthy Notes",
                        value = unroadworthyNotes,
                        singleLine = false,
                    ) { unroadworthyNotes = it }
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        PrimaryActionButton(
                            text = "Record Service",
                            icon = Icons.Default.Build,
                            enabled = bikeId != null,
                        ) {
                            bikeId?.let {
                                form = form.copy(bikeId = it.toString())
                                showCreate = true
                                selectedBikeActionRow = null
                            }
                        }
                        SecondaryActionButton(
                            text = if (currentlyFlagged) "Mark Roadworthy" else "Flag Unroadworthy",
                            icon = Icons.Default.Settings,
                            enabled = bikeId != null,
                        ) {
                            bikeId?.let {
                                onSetUnroadworthy(it, !currentlyFlagged, unroadworthyNotes)
                                selectedBikeActionRow = null
                            }
                        }
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.End,
                    ) {
                        TextButton(onClick = { selectedBikeActionRow = null }) { Text("Close") }
                    }
                }
            }
        }
    }

    val detailRow = selectedRowDetail
    if (detailRow != null) {
        RowDetailDialog(
            row = detailRow,
            onDismiss = { selectedRowDetail = null },
        )
    }

    if (showCreate) {
        AppFormDialog(
            title = "Record Service",
            onDismiss = { showCreate = false },
            onSubmit = {
                val prepared = form.copy(
                    workDone = if (form.entryType == "maintenance" && form.workDone.isNotBlank()) {
                        "Maintenance: ${form.workDone}"
                    } else {
                        form.workDone
                    },
                )
                onCreateService(prepared)
                form = ServiceFormState()
                showCreate = false
            },
            submitLabel = "Save Service",
            submitEnabled = form.isValid(),
        ) {
            DropdownField(
                label = "Entry Type",
                selectedValue = form.entryType,
                options = listOf(
                    DropdownOption("Service", "service"),
                    DropdownOption("Maintenance", "maintenance"),
                ),
                onSelected = { form = form.copy(entryType = it) },
            )
            DropdownField(
                label = "Bike",
                selectedValue = form.bikeId,
                options = lookups.bikes
                    .mapNotNull { row -> row.id?.let { DropdownOption("#$it • ${row.title}", it.toString()) } },
                onSelected = { form = form.copy(bikeId = it) },
            )
            DateField("Service Date", form.serviceDate) {
                form = form.copy(
                    serviceDate = it,
                    nextDueDate = nextDueInDays(it, 21),
                )
            }
            DateField("Next Due Date (optional)", form.nextDueDate) { form = form.copy(nextDueDate = it) }
            MoneyField("Amount (optional)", form.amount) { form = form.copy(amount = it) }
            MoneyField("Transaction Cost (optional)", form.fee) { form = form.copy(fee = it) }
            LabeledField("Reference (optional)", form.reference) { form = form.copy(reference = it) }
            LabeledField(
                label = if (form.entryType == "maintenance") "Maintenance Notes (optional)" else "Work Done (optional)",
                value = form.workDone,
                singleLine = false,
            ) { form = form.copy(workDone = it) }

            InlineValidationHint(ok = form.isValid(), message = "Required: bikeId + valid service date. Next due date must be valid if provided.")
        }
    }
}

/* ----------------------------- Bikes ----------------------------- */

private data class BikeFormState(
    val plateNo: String = "",
    val model: String = "",
    val status: String = "active",
) {
    fun isValid(): Boolean = plateNo.isNotBlank()
}

private val BikeFormSaver: Saver<BikeFormState, Map<String, String>> = Saver(
    save = { mapOf("plateNo" to it.plateNo, "model" to it.model, "status" to it.status) },
    restore = { BikeFormState(plateNo = it["plateNo"] ?: "", model = it["model"] ?: "", status = it["status"] ?: "active") },
)

@Composable
private fun BikesSection(
    state: MenuListState,
    onSearchQueryChanged: (String) -> Unit,
    onCreate: (BikeFormState) -> Unit,
    onPerPageSelected: (Int) -> Unit,
) {
    var perPage by rememberSaveable { mutableStateOf(25) }
    var showCreate by rememberSaveable { mutableStateOf(false) }
    var form by rememberSaveable(stateSaver = BikeFormSaver) { mutableStateOf(BikeFormState()) }

    ListSectionScaffold(
        summary = state.summary,
        loading = state.loading,
        error = state.error,
        perPage = perPage,
        onPerPageSelected = {
            perPage = it
            onPerPageSelected(it)
        },
        onSearchQueryChange = onSearchQueryChanged,
        primaryAction = { PrimaryActionButton("Add Bike", icon = Icons.Default.Add) { showCreate = true } },
        rows = state.rows,
        emptyTitle = "No bikes yet",
        emptyMessage = "Add bikes to track fuel and maintenance.",
    )

    if (showCreate) {
        AppFormDialog(
            title = "Create Bike",
            onDismiss = { showCreate = false },
            onSubmit = {
                onCreate(form)
                form = BikeFormState()
                showCreate = false
            },
            submitLabel = "Save Bike",
            submitEnabled = form.isValid(),
        ) {
            LabeledField("Plate No", form.plateNo) { form = form.copy(plateNo = it) }
            LabeledField("Model (optional)", form.model) { form = form.copy(model = it) }
            DropdownField(
                label = "Status",
                selectedValue = form.status,
                options = listOf(
                    DropdownOption("Active", "active"),
                    DropdownOption("Inactive", "inactive"),
                    DropdownOption("Maintenance", "maintenance"),
                ),
                onSelected = { form = form.copy(status = it) },
            )
            InlineValidationHint(ok = form.isValid(), message = "Required: plate number.")
        }
    }
}

/* ----------------------------- Respondents ----------------------------- */

private data class RespondentFormState(
    val name: String = "",
    val phone: String = "",
    val category: String = "",
) {
    fun isValid(): Boolean = name.isNotBlank()
}

private val RespondentFormSaver: Saver<RespondentFormState, Map<String, String>> = Saver(
    save = { mapOf("name" to it.name, "phone" to it.phone, "category" to it.category) },
    restore = { RespondentFormState(name = it["name"] ?: "", phone = it["phone"] ?: "", category = it["category"] ?: "") },
)

@Composable
private fun RespondentsSection(
    state: MenuListState,
    onSearchQueryChanged: (String) -> Unit,
    onCreate: (RespondentFormState) -> Unit,
    onPerPageSelected: (Int) -> Unit,
) {
    var perPage by rememberSaveable { mutableStateOf(25) }
    var showCreate by rememberSaveable { mutableStateOf(false) }
    var form by rememberSaveable(stateSaver = RespondentFormSaver) { mutableStateOf(RespondentFormState()) }

    ListSectionScaffold(
        summary = state.summary,
        loading = state.loading,
        error = state.error,
        perPage = perPage,
        onPerPageSelected = {
            perPage = it
            onPerPageSelected(it)
        },
        onSearchQueryChange = onSearchQueryChanged,
        primaryAction = { PrimaryActionButton("Add Respondent", icon = Icons.Default.Add) { showCreate = true } },
        rows = state.rows,
        emptyTitle = "No respondents yet",
        emptyMessage = "Add payees/recipients to tag transactions cleanly.",
    )

    if (showCreate) {
        AppFormDialog(
            title = "Create Respondent",
            onDismiss = { showCreate = false },
            onSubmit = {
                onCreate(form)
                form = RespondentFormState()
                showCreate = false
            },
            submitLabel = "Save Respondent",
            submitEnabled = form.isValid(),
        ) {
            LabeledField("Name", form.name) { form = form.copy(name = it) }
            LabeledField("Phone (optional)", form.phone) { form = form.copy(phone = it) }
            DropdownField(
                label = "Category (optional)",
                selectedValue = form.category,
                options = listOf(
                    DropdownOption("Fuel", "fuel"),
                    DropdownOption("Maintenance", "maintenance"),
                    DropdownOption("Meals", "meals"),
                    DropdownOption("Supplier", "supplier"),
                    DropdownOption("Other", "other"),
                ),
                onSelected = { form = form.copy(category = it) },
                includeEmptyOption = true,
            )
            InlineValidationHint(ok = form.isValid(), message = "Required: name.")
        }
    }
}

/* ----------------------------- Notifications ----------------------------- */

private data class NotificationFormState(
    val title: String = "",
    val message: String = "",
    val type: String = "manual_notice",
) {
    fun isValid(): Boolean = title.isNotBlank() && message.isNotBlank()
}

private val NotificationFormSaver: Saver<NotificationFormState, Map<String, String>> = Saver(
    save = { mapOf("title" to it.title, "message" to it.message, "type" to it.type) },
    restore = { NotificationFormState(title = it["title"] ?: "", message = it["message"] ?: "", type = it["type"] ?: "manual_notice") },
)

@Composable
private fun NotificationsSection(
    state: MenuListState,
    onPerPageSelected: (Int) -> Unit,
    onSearchQueryChanged: (String) -> Unit,
    onCreate: (NotificationFormState) -> Unit,
    onReadOne: (Int) -> Unit,
    onReadAll: () -> Unit,
) {
    var perPage by rememberSaveable { mutableStateOf(25) }
    var query by rememberSaveable { mutableStateOf("") }
    var showCreate by rememberSaveable { mutableStateOf(false) }
    var form by rememberSaveable(stateSaver = NotificationFormSaver) { mutableStateOf(NotificationFormState()) }
    var selectedNotification by remember { mutableStateOf<MenuRecord?>(null) }
    val latestSearchCallback = androidx.compose.runtime.rememberUpdatedState(onSearchQueryChanged)

    LaunchedEffect(query) {
        delay(320)
        latestSearchCallback.value(query)
    }

    Column(modifier = Modifier.fillMaxSize()) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            SectionHeader(summary = state.summary, error = state.error, loading = state.loading)

            BoxWithConstraints(modifier = Modifier.fillMaxWidth()) {
                val compact = maxWidth < 520.dp
                if (compact) {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        SecondaryActionButton(
                            text = "Mark All Read",
                            icon = Icons.Default.MarkEmailRead,
                            onClick = onReadAll,
                        )
                        PrimaryActionButton(
                            text = "Send Notification",
                            icon = Icons.Default.Notifications,
                            onClick = { showCreate = true },
                        )
                    }
                } else {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                    ) {
                        SecondaryActionButton(
                            text = "Mark All Read",
                            icon = Icons.Default.MarkEmailRead,
                            onClick = onReadAll,
                        )
                        PrimaryActionButton(
                            text = "Send Notification",
                            icon = Icons.Default.Notifications,
                            onClick = { showCreate = true },
                        )
                    }
                }
            }

            PerPageChooser(selected = perPage) {
                perPage = it
                onPerPageSelected(it)
            }

            SearchField(
                query = query,
                onQueryChange = { query = it },
                hint = "Search notifications",
            )
        }

        if (!state.loading && state.rows.isEmpty()) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(horizontal = 16.dp, vertical = 4.dp),
                contentAlignment = Alignment.TopCenter,
            ) {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    EmptyStateCard(
                        title = "No notifications",
                        message = "System notices and manual alerts will appear here.",
                    )
                    Text(
                        text = "New alerts will show here. Tap an item to open full details.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            }
        } else {
            LazyColumn(
                modifier = Modifier.fillMaxSize(),
                contentPadding = PaddingValues(horizontal = 16.dp, vertical = 4.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                items(state.rows, key = { it.id ?: it.title }) { row ->
                    val isUnread = row.tone != "default"
                    val stamp = row.meta.split("|").lastOrNull()?.trim().orEmpty()
                    Card(
                        modifier = Modifier.clickable {
                            selectedNotification = row
                            if (isUnread) row.id?.let(onReadOne)
                        },
                        colors = CardDefaults.cardColors(
                            containerColor = if (isUnread) {
                                MaterialTheme.colorScheme.tertiaryContainer
                            } else {
                                MaterialTheme.colorScheme.surface
                            },
                        ),
                    ) {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(12.dp),
                            verticalArrangement = Arrangement.spacedBy(4.dp),
                        ) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(8.dp),
                            ) {
                                Text(
                                    text = row.title,
                                    style = MaterialTheme.typography.titleSmall,
                                    fontWeight = FontWeight.SemiBold,
                                    modifier = Modifier.weight(1f),
                                    maxLines = 1,
                                    overflow = TextOverflow.Ellipsis,
                                )
                                Text(
                                    text = if (isUnread) "Unread" else "Read",
                                    style = MaterialTheme.typography.labelSmall,
                                    color = if (isUnread) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant,
                                )
                            }
                            if (stamp.isNotBlank()) {
                                Text(
                                    text = stamp,
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                    maxLines = 1,
                                    overflow = TextOverflow.Ellipsis,
                                )
                            }
                        }
                    }
                }
            }
        }
    }

    val currentNotification = selectedNotification
    if (currentNotification != null) {
        val metaLines = currentNotification.meta
            .split("|")
            .map { it.trim() }
            .filter { it.isNotBlank() }
        androidx.compose.ui.window.Dialog(onDismissRequest = { selectedNotification = null }) {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .heightIn(max = 520.dp),
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(14.dp)
                        .verticalScroll(rememberScrollState()),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    Text(
                        currentNotification.title,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                    )
                    Text(
                        text = if (currentNotification.tone != "default") "Status: Unread" else "Status: Read",
                        style = MaterialTheme.typography.labelMedium,
                        color = if (currentNotification.tone != "default") MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                    Text(
                        currentNotification.subtitle.ifBlank { "No message body." },
                        style = MaterialTheme.typography.bodyMedium,
                    )
                    if (metaLines.isNotEmpty()) {
                        HorizontalDivider()
                        Text(
                            "Details",
                            style = MaterialTheme.typography.labelLarge,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                        metaLines.forEach { line ->
                            Text(
                                line,
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.End,
                    ) {
                        TextButton(onClick = { selectedNotification = null }) {
                            Text("Close")
                        }
                    }
                }
            }
        }
    }

    if (showCreate) {
        AppFormDialog(
            title = "Send In-App Notification",
            onDismiss = { showCreate = false },
            onSubmit = {
                onCreate(form)
                form = NotificationFormState()
                showCreate = false
            },
            submitLabel = "Send",
            submitEnabled = form.isValid(),
        ) {
            LabeledField("Title", form.title) { form = form.copy(title = it) }
            LabeledField("Message", form.message, singleLine = false) { form = form.copy(message = it) }
            DropdownField(
                label = "Type",
                selectedValue = form.type,
                options = listOf(
                    DropdownOption("Manual Notice", "manual_notice"),
                    DropdownOption("Billing Notice", "billing_notice"),
                    DropdownOption("Reminder", "reminder"),
                ),
                onSelected = { form = form.copy(type = it) },
            )
            InlineValidationHint(ok = form.isValid(), message = "Required: title + message.")
        }
    }
}

/* ----------------------------- Reports ----------------------------- */

@Composable
private fun ReportsSection(
    lookups: MenuLookupsState,
    onRefresh: () -> Unit,
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val clipboard = LocalClipboardManager.current
    var selectedRespondent by remember { mutableStateOf<MenuRecord?>(null) }
    var showHostelExport by remember { mutableStateOf(false) }
    var exportInfo by remember { mutableStateOf<String?>(null) }

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp),
    ) {
        item {
            SectionHeader(
                summary = "Reference IDs + available balances for fast data entry.",
                error = lookups.error,
                loading = lookups.loading,
            )
            Spacer(Modifier.height(8.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp, Alignment.End),
            ) {
                SecondaryActionButton(
                    text = "Export PDF",
                    icon = Icons.Default.Assessment,
                ) {
                    scope.launch {
                        val path = exportLookupsPdf(context, lookups)
                        exportInfo = path?.let { "Saved PDF: $it" } ?: "PDF export failed."
                    }
                }
                PrimaryActionButton("Reload Lookups", icon = Icons.Default.Refresh, onClick = onRefresh)
            }
            if (!exportInfo.isNullOrBlank()) {
                Text(
                    text = exportInfo ?: "",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }

        item {
            Card {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(12.dp),
                    verticalArrangement = Arrangement.spacedBy(6.dp),
                ) {
                    Text("Available Funding Batches", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                    Text("Net balance: KES ${moneyValue(lookups.availableBatchBalance)}", style = MaterialTheme.typography.bodySmall)
                    lookups.batches.take(10).forEach { row ->
                        Text(
                            text = "${row.id}: ${row.title} | ${row.subtitle}",
                            style = MaterialTheme.typography.bodySmall,
                        )
                    }
                }
            }
        }
        item {
            Card {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(12.dp),
                    verticalArrangement = Arrangement.spacedBy(6.dp),
                ) {
                    Text("Bike IDs", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                    lookups.bikes.take(12).forEach { row ->
                        Text(
                            text = "${row.id}: ${row.title} | ${row.subtitle}",
                            style = MaterialTheme.typography.bodySmall,
                            color = if (row.tone == "unroadworthy") MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.onSurface,
                        )
                    }
                }
            }
        }
        item {
            Card {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(12.dp),
                    verticalArrangement = Arrangement.spacedBy(6.dp),
                ) {
                    Text("Respondent IDs", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                    lookups.respondents.take(12).forEach { row ->
                        Button(
                            onClick = { selectedRespondent = row },
                            modifier = Modifier.fillMaxWidth(),
                            colors = ButtonDefaults.buttonColors(
                                containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                                contentColor = MaterialTheme.colorScheme.onSurface,
                            ),
                        ) {
                            Text(
                                "${row.id}: ${row.title}",
                                modifier = Modifier.fillMaxWidth(),
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis,
                            )
                        }
                    }
                }
            }
        }
        item {
            Card {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(12.dp),
                    verticalArrangement = Arrangement.spacedBy(6.dp),
                ) {
                    Text("Hostel IDs", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                    Text("Total hostels: ${lookups.hostels.size}", style = MaterialTheme.typography.bodySmall)
                    SecondaryActionButton(
                        text = "Export Hostel List",
                        icon = Icons.Default.Assessment,
                        enabled = lookups.hostels.isNotEmpty(),
                    ) { showHostelExport = true }
                }
            }
        }
    }

    val respondent = selectedRespondent
    if (respondent != null) {
        val payload = "RESP|${respondent.id}|${respondent.title}|${respondent.meta}"
        val qrBitmap = remember(payload) { generateQrImage(payload) }
        androidx.compose.ui.window.Dialog(onDismissRequest = { selectedRespondent = null }) {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .heightIn(max = 440.dp),
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(14.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    Text(CompanyName, style = MaterialTheme.typography.labelLarge)
                    Text("Employee Badge", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text("Respondent ID: ${respondent.id ?: "-"}", style = MaterialTheme.typography.bodySmall)
                    Text("Name: ${respondent.title}", style = MaterialTheme.typography.bodyMedium)
                    Text("Phone/Role: ${respondent.meta}", style = MaterialTheme.typography.bodySmall)
                    if (qrBitmap != null) {
                        Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.55f))) {
                            Box(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(10.dp),
                                contentAlignment = Alignment.Center,
                            ) {
                                Image(
                                    bitmap = qrBitmap,
                                    contentDescription = "Respondent QR",
                                    modifier = Modifier.size(170.dp),
                                )
                            }
                        }
                    }
                    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.55f))) {
                        Column(Modifier.padding(10.dp)) {
                            Text("QR Payload", style = MaterialTheme.typography.labelSmall)
                            Text(payload, style = MaterialTheme.typography.bodySmall)
                        }
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp, Alignment.End),
                    ) {
                        SecondaryActionButton("Copy Payload", icon = Icons.Default.Assessment) {
                            clipboard.setText(AnnotatedString(payload))
                        }
                        TextButton(onClick = { selectedRespondent = null }) { Text("Close") }
                    }
                }
            }
        }
    }

    if (showHostelExport) {
        val export = buildString {
            appendLine("id,hostel,meter,phone,stake,amount_due")
            lookups.hostels.forEach { row ->
                appendLine("${row.id ?: ""},${csvSafe(row.title)},${csvSafe(row.subtitle)},${csvSafe(row.meta)}")
            }
        }
        androidx.compose.ui.window.Dialog(onDismissRequest = { showHostelExport = false }) {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .heightIn(max = 500.dp),
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(14.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    Text("Hostel Export", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text("Total hostels: ${lookups.hostels.size}", style = MaterialTheme.typography.bodySmall)
                    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))) {
                        Column(
                            modifier = Modifier
                                .fillMaxWidth()
                                .heightIn(max = 300.dp)
                                .verticalScroll(rememberScrollState())
                                .padding(10.dp),
                        ) {
                            Text(export, style = MaterialTheme.typography.bodySmall)
                        }
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.End,
                    ) {
                        SecondaryActionButton("Copy Export", icon = Icons.Default.Assessment) {
                            clipboard.setText(AnnotatedString(export))
                        }
                        Spacer(Modifier.width(8.dp))
                        TextButton(onClick = { showHostelExport = false }) { Text("Close") }
                    }
                }
            }
        }
    }
}

/* ----------------------------- Session ----------------------------- */

@Composable
private fun SessionSection(
    userName: String?,
    userRole: String?,
    activeSessionsCount: Int?,
    activeSessionsError: String?,
    sessionScope: String,
    sessionRows: List<AuthSessionRecord>,
    onRefreshSessionStats: () -> Unit,
    onRevokeSession: (tokenId: Int, isCurrent: Boolean) -> Unit,
    onLogoutCurrent: () -> Unit,
    onLogoutAll: () -> Unit,
) {
    val scope = rememberCoroutineScope()
    val isAdminScope = sessionScope == "all_users"
    var query by rememberSaveable { mutableStateOf("") }
    var selectedSession by remember { mutableStateOf<AuthSessionRecord?>(null) }
    var logoutTarget by remember { mutableStateOf<LogoutTarget?>(null) }
    var logoutInProgress by remember { mutableStateOf(false) }
    val filteredSessions = remember(sessionRows, query) {
        val q = query.trim().lowercase()
        if (q.isBlank()) return@remember sessionRows
        sessionRows.filter { session ->
            listOfNotNull(
                session.name,
                session.devicePlatform,
                session.lastUserAgent,
                session.userName,
                session.userEmail,
                session.userRole,
                session.lastIp,
                sessionSourceLabel(session),
            ).any { it.lowercase().contains(q) } || session.id.toString().contains(q)
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(10.dp),
            verticalAlignment = Alignment.Top,
        ) {
            Card(modifier = Modifier.weight(1f)) {
                Column(
                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 10.dp),
                    verticalArrangement = Arrangement.spacedBy(4.dp),
                ) {
                    Text("Logged in as", style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    Text(userName ?: "-", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    Text("Role: ${userRole ?: "viewer"}", style = MaterialTheme.typography.bodySmall)
                    Text("Active sessions: ${activeSessionsCount ?: "-"}", style = MaterialTheme.typography.bodySmall)
                    Text(
                        "Scope: ${if (isAdminScope) "All users (admin)" else "Current user"}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                    if (!activeSessionsError.isNullOrBlank()) {
                        Text(
                            text = activeSessionsError,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.error,
                        )
                    }
                }
            }
            Card(modifier = Modifier.weight(1f)) {
                Column(
                    modifier = Modifier.padding(10.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    SecondaryActionButton(
                        text = "Refresh Sessions",
                        icon = Icons.Default.Refresh,
                        onClick = onRefreshSessionStats,
                    )
                    PrimaryActionButton(
                        text = "Logout this device",
                        icon = Icons.AutoMirrored.Filled.ExitToApp,
                    ) { logoutTarget = LogoutTarget.Current }
                    SecondaryActionButton(
                        text = "Logout all devices",
                        icon = Icons.AutoMirrored.Filled.ExitToApp,
                    ) { logoutTarget = LogoutTarget.All }
                    if (logoutInProgress) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(8.dp),
                        ) {
                            CircularProgressIndicator(modifier = Modifier.size(14.dp), strokeWidth = 2.dp)
                            Text("Clearing session...", style = MaterialTheme.typography.bodySmall)
                        }
                    }
                }
            }
        }

        Card(modifier = Modifier.fillMaxWidth()) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(12.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                Text("Logged In Sessions", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                SearchField(
                    query = query,
                    onQueryChange = { query = it },
                    hint = "Search sessions",
                )

                if (filteredSessions.isEmpty()) {
                    EmptyStateCard(
                        title = "No active sessions",
                        message = "Use Refresh Sessions to load current web/app tokens.",
                    )
                } else {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .heightIn(max = 420.dp)
                            .verticalScroll(rememberScrollState()),
                        verticalArrangement = Arrangement.spacedBy(8.dp),
                    ) {
                        filteredSessions.forEach { row ->
                            Card(
                                colors = CardDefaults.cardColors(
                                    containerColor = if (row.isCurrent) {
                                        MaterialTheme.colorScheme.primaryContainer
                                    } else {
                                        MaterialTheme.colorScheme.surface
                                    },
                                ),
                            ) {
                                Column(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .clickable { selectedSession = row }
                                        .padding(10.dp),
                                    verticalArrangement = Arrangement.spacedBy(4.dp),
                                ) {
                                    val sourceLabel = sessionSourceLabel(row)
                                    val owner = if (sessionScope == "all_users") {
                                        "${row.userName ?: "Unknown"} (${row.userRole ?: "-"})"
                                    } else {
                                        row.name
                                    }
                                    Text(
                                        text = owner,
                                        style = MaterialTheme.typography.labelLarge,
                                        fontWeight = FontWeight.SemiBold,
                                    )
                                    Text(
                                        text = "Source: $sourceLabel${row.devicePlatform?.let { " • $it" } ?: ""}",
                                        style = MaterialTheme.typography.bodySmall,
                                    )
                                    Text(
                                        text = "Last used: ${row.lastUsedAt ?: "-"}",
                                        style = MaterialTheme.typography.bodySmall,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                                    )
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.End,
                                    ) {
                                        if (isAdminScope) {
                                            SecondaryActionButton(
                                                text = "Terminate",
                                                icon = Icons.AutoMirrored.Filled.ExitToApp,
                                            ) {
                                                onRevokeSession(row.id, row.isCurrent)
                                            }
                                        } else {
                                            Text(
                                                text = if (row.isCurrent) "Current session" else "View only",
                                                style = MaterialTheme.typography.bodySmall,
                                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                            )
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    val sessionDetail = selectedSession
    if (sessionDetail != null) {
        androidx.compose.ui.window.Dialog(onDismissRequest = { selectedSession = null }) {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .heightIn(max = 480.dp),
            ) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(14.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    Text(
                        text = "Session #${sessionDetail.id}",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                    )
                    Text("Source: ${sessionSourceLabel(sessionDetail)}", style = MaterialTheme.typography.bodySmall)
                    Text("Name: ${sessionDetail.name}", style = MaterialTheme.typography.bodySmall)
                    if (sessionScope == "all_users") {
                        Text("User: ${sessionDetail.userName ?: "-"} (${sessionDetail.userRole ?: "-"})", style = MaterialTheme.typography.bodySmall)
                        Text("Email: ${sessionDetail.userEmail ?: "-"}", style = MaterialTheme.typography.bodySmall)
                    }
                    Text("Device Platform: ${sessionDetail.devicePlatform ?: "-"}", style = MaterialTheme.typography.bodySmall)
                    Text("Device ID: ${sessionDetail.deviceId ?: "-"}", style = MaterialTheme.typography.bodySmall)
                    Text("Last IP: ${sessionDetail.lastIp ?: "-"}", style = MaterialTheme.typography.bodySmall)
                    Text("Last Used: ${sessionDetail.lastUsedAt ?: "-"}", style = MaterialTheme.typography.bodySmall)
                    Text("Expires At: ${sessionDetail.expiresAt ?: "-"}", style = MaterialTheme.typography.bodySmall)
                    if (!sessionDetail.lastUserAgent.isNullOrBlank()) {
                        Text(
                            text = "User Agent: ${sessionDetail.lastUserAgent}",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.End,
                    ) {
                        if (isAdminScope) {
                            SecondaryActionButton(
                                text = "Terminate Session",
                                icon = Icons.AutoMirrored.Filled.ExitToApp,
                            ) {
                                onRevokeSession(sessionDetail.id, sessionDetail.isCurrent)
                                selectedSession = null
                            }
                            Spacer(Modifier.width(8.dp))
                        }
                        TextButton(onClick = { selectedSession = null }) { Text("Close") }
                    }
                }
            }
        }
    }

    val pendingLogout = logoutTarget
    if (pendingLogout != null) {
        androidx.compose.material3.AlertDialog(
            onDismissRequest = { logoutTarget = null },
            title = { Text("Confirm Logout") },
            text = {
                Text(
                    if (pendingLogout == LogoutTarget.Current) {
                        "Logout this device now?"
                    } else {
                        "Logout all devices, including this one?"
                    },
                )
            },
            confirmButton = {
                TextButton(
                    onClick = {
                        logoutTarget = null
                        logoutInProgress = true
                        scope.launch {
                            delay(220)
                            if (pendingLogout == LogoutTarget.Current) {
                                onLogoutCurrent()
                            } else {
                                onLogoutAll()
                            }
                            delay(1500)
                            logoutInProgress = false
                        }
                    },
                ) { Text("Continue") }
            },
            dismissButton = {
                TextButton(onClick = { logoutTarget = null }) { Text("Cancel") }
            },
        )
    }
}

/* ----------------------------- Shared UI ----------------------------- */

@Composable
private fun ListSectionScaffold(
    summary: String,
    loading: Boolean,
    error: String?,
    perPage: Int,
    onPerPageSelected: (Int) -> Unit,
    onSearchQueryChange: ((String) -> Unit)? = null,
    primaryAction: @Composable () -> Unit,
    secondaryAction: (@Composable () -> Unit)? = null,
    rows: List<MenuRecord>,
    emptyTitle: String,
    emptyMessage: String,
    headerExtra: (@Composable () -> Unit)? = null,
    rowActionIcon: androidx.compose.ui.graphics.vector.ImageVector? = null,
    onRowClick: ((MenuRecord) -> Unit)? = null,
    onRowAction: ((MenuRecord) -> Unit)? = null,
) {
    var query by rememberSaveable { mutableStateOf("") }
    var selectedRowDetail by remember { mutableStateOf<MenuRecord?>(null) }
    val latestSearchCallback = androidx.compose.runtime.rememberUpdatedState(onSearchQueryChange)
    val filteredRows = remember(rows, query, onSearchQueryChange) {
        if (onSearchQueryChange == null) filterRows(rows, query) else rows
    }

    LaunchedEffect(query) {
        latestSearchCallback.value?.let { callback ->
            delay(320)
            callback(query)
        }
    }

    Column(
        modifier = Modifier.fillMaxSize(),
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.Top,
                horizontalArrangement = Arrangement.spacedBy(10.dp),
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    SectionHeader(summary = summary, error = error, loading = loading)
                }
                Row(
                    modifier = Modifier.horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    secondaryAction?.invoke()
                    primaryAction()
                }
            }

            PerPageChooser(selected = perPage, onSelected = onPerPageSelected)

            headerExtra?.invoke()
            SearchField(
                query = query,
                onQueryChange = { query = it },
                hint = "Search list",
            )
        }

        val tableScroll = rememberScrollState()
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 16.dp)
                .horizontalScroll(tableScroll),
        ) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .widthIn(min = TableMinWidthDp.dp),
            ) {
                TableHeaderRow(withAction = rowActionIcon != null)

                if (!loading && filteredRows.isEmpty()) {
                    EmptyStateCard(title = emptyTitle, message = emptyMessage)
                } else {
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        contentPadding = PaddingValues(top = 0.dp, bottom = 16.dp),
                        verticalArrangement = Arrangement.spacedBy(6.dp),
                    ) {
                        items(filteredRows, key = { it.id ?: it.title }) { row ->
                            TableDataRow(
                                row = row,
                                withAction = rowActionIcon != null,
                                actionIcon = rowActionIcon ?: Icons.Default.Visibility,
                                onClick = {
                                    if (onRowClick != null) onRowClick.invoke(row) else selectedRowDetail = row
                                },
                                onAction = if (onRowAction == null) null else { { onRowAction.invoke(row) } },
                            )
                        }
                    }
                }
            }
        }
    }

    val rowDetail = selectedRowDetail
    if (rowDetail != null) {
        RowDetailDialog(row = rowDetail, onDismiss = { selectedRowDetail = null })
    }
}

@Composable
private fun EmptyStateCard(
    title: String,
    message: String,
) {
    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.45f)),
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp),
            verticalArrangement = Arrangement.spacedBy(6.dp),
        ) {
            Text(title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
            Text(message, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun SearchField(
    query: String,
    onQueryChange: (String) -> Unit,
    hint: String,
    modifier: Modifier = Modifier,
) {
    OutlinedTextField(
        value = query,
        onValueChange = onQueryChange,
        label = { Text(hint) },
        leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
        singleLine = true,
        modifier = modifier.fillMaxWidth(),
    )
}

@Composable
private fun TableHeaderRow(
    withAction: Boolean,
    actionTitle: String = "Action",
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 6.dp)
            .widthIn(min = TableMinWidthDp.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        Text("ID", style = MaterialTheme.typography.labelSmall, modifier = Modifier.width(56.dp))
        Text(
            "Item",
            style = MaterialTheme.typography.labelSmall,
            modifier = Modifier.width(220.dp),
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
        )
        Text(
            "Details",
            style = MaterialTheme.typography.labelSmall,
            modifier = Modifier.width(240.dp),
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
        )
        Text(
            "Info",
            style = MaterialTheme.typography.labelSmall,
            modifier = Modifier.width(220.dp),
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
        )
        if (withAction) {
            Text(
                actionTitle,
                style = MaterialTheme.typography.labelSmall,
                modifier = Modifier.width(52.dp),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
        }
    }
    HorizontalDivider(modifier = Modifier.padding(horizontal = 16.dp))
}

@Composable
private fun TableDataRow(
    row: MenuRecord,
    selected: Boolean = false,
    withAction: Boolean = false,
    actionIcon: androidx.compose.ui.graphics.vector.ImageVector = Icons.Default.Visibility,
    onClick: (() -> Unit)? = null,
    onAction: (() -> Unit)? = null,
) {
    val toneColor = when (row.tone) {
        "overdue", "unroadworthy" -> MaterialTheme.colorScheme.errorContainer
        "due_today" -> MaterialTheme.colorScheme.tertiaryContainer
        "due_soon", "upcoming" -> MaterialTheme.colorScheme.secondaryContainer
        else -> MaterialTheme.colorScheme.surface
    }

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .widthIn(min = TableMinWidthDp.dp),
        colors = CardDefaults.cardColors(
            containerColor = if (selected) MaterialTheme.colorScheme.primaryContainer else toneColor,
        ),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .clickable(enabled = onClick != null || onAction != null) { onClick?.invoke() ?: onAction?.invoke() }
                .padding(horizontal = 10.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            Text(
                text = row.id?.toString() ?: "-",
                style = MaterialTheme.typography.bodySmall,
                modifier = Modifier.width(56.dp),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
            Text(
                text = row.title,
                style = MaterialTheme.typography.bodySmall,
                modifier = Modifier.width(220.dp),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
            Text(
                text = row.subtitle,
                style = MaterialTheme.typography.bodySmall,
                modifier = Modifier.width(240.dp),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
            Text(
                text = row.meta,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.width(220.dp),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
            if (withAction) {
                IconButton(
                    onClick = { onAction?.invoke() },
                    modifier = Modifier.width(52.dp),
                ) {
                    Icon(actionIcon, contentDescription = "Row action")
                }
            }
        }
    }
}

@Composable
private fun RowDetailDialog(
    row: MenuRecord,
    onDismiss: () -> Unit,
) {
    androidx.compose.ui.window.Dialog(onDismissRequest = onDismiss) {
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .heightIn(max = 440.dp),
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(14.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                Text(
                    text = row.title.ifBlank { "Row Details" },
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(
                    text = "ID: ${row.id ?: "-"}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                if (row.subtitle.isNotBlank()) {
                    Text(row.subtitle, style = MaterialTheme.typography.bodyMedium)
                }
                if (row.meta.isNotBlank()) {
                    Text(row.meta, style = MaterialTheme.typography.bodySmall)
                }
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End,
                ) {
                    TextButton(onClick = onDismiss) { Text("Close") }
                }
            }
        }
    }
}

@Composable
private fun InlineValidationHint(ok: Boolean, message: String) {
    val color = if (ok) MaterialTheme.colorScheme.onSurfaceVariant else MaterialTheme.colorScheme.error
    Text(
        text = message,
        style = MaterialTheme.typography.bodySmall,
        color = color,
    )
}

@Composable
private fun <T> IdQuickPickRow(
    title: String,
    items: List<T>,
    idOf: (T) -> Int?,
    labelOf: (T) -> String,
    onPick: (Int) -> Unit,
) {
    if (items.isEmpty()) return
    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
        Text(title, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Row(
            modifier = Modifier.horizontalScroll(rememberScrollState()),
            horizontalArrangement = Arrangement.spacedBy(6.dp),
        ) {
            items.take(6).forEach { item ->
                val id = idOf(item) ?: return@forEach
                FilterChip(
                    selected = false,
                    onClick = { onPick(id) },
                    label = { Text(labelOf(item)) },
                )
            }
        }
    }
}

@Composable
private fun SectionHeader(
    summary: String,
    error: String?,
    loading: Boolean,
) {
    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
        if (summary.isNotBlank()) {
            Text(summary, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium)
        }
        if (!error.isNullOrBlank()) {
            Text(error, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall)
        }
        if (loading) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                CircularProgressIndicator(modifier = Modifier.width(14.dp), strokeWidth = 2.dp)
                Text("Loading...", style = MaterialTheme.typography.bodySmall)
            }
        }
    }
}

@Composable
private fun PerPageChooser(
    selected: Int,
    options: List<Int> = listOf(15, 25, 30, 50, 100),
    label: String = "Rows",
    onSelected: (Int) -> Unit,
) {
    var expanded by remember { mutableStateOf(false) }

    Row(
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        Text(
            text = "$label:",
            style = MaterialTheme.typography.labelMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Box {
            Button(
                onClick = { expanded = true },
                colors = ButtonDefaults.buttonColors(
                    containerColor = MaterialTheme.colorScheme.secondaryContainer,
                    contentColor = MaterialTheme.colorScheme.onSecondaryContainer,
                ),
                contentPadding = PaddingValues(horizontal = 12.dp, vertical = 6.dp),
            ) {
                Text("$selected")
            }
            DropdownMenu(
                expanded = expanded,
                onDismissRequest = { expanded = false },
            ) {
                options.forEach { value ->
                    DropdownMenuItem(
                        text = { Text("$value") },
                        onClick = {
                            expanded = false
                            onSelected(value)
                        },
                    )
                }
            }
        }
    }
}

@Composable
private fun PrimaryActionButton(
    text: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector = Icons.Default.Add,
    enabled: Boolean = true,
    onClick: () -> Unit,
) {
    Button(
        onClick = onClick,
        enabled = enabled,
        colors = ButtonDefaults.buttonColors(
            containerColor = BrandPrimary,
            contentColor = Color.White,
        ),
    ) {
        Icon(icon, contentDescription = null)
        Spacer(Modifier.width(6.dp))
        Text(text)
    }
}

@Composable
private fun SecondaryActionButton(
    text: String,
    icon: androidx.compose.ui.graphics.vector.ImageVector = Icons.Default.Settings,
    enabled: Boolean = true,
    onClick: () -> Unit,
) {
    Button(
        onClick = onClick,
        enabled = enabled,
        colors = ButtonDefaults.buttonColors(
            containerColor = MaterialTheme.colorScheme.secondaryContainer,
            contentColor = MaterialTheme.colorScheme.onSecondaryContainer,
        ),
    ) {
        Icon(icon, contentDescription = null)
        Spacer(Modifier.width(6.dp))
        Text(text)
    }
}

@Composable
private fun AppFormDialog(
    title: String,
    onDismiss: () -> Unit,
    onSubmit: () -> Unit,
    submitLabel: String,
    submitEnabled: Boolean,
    content: @Composable ColumnScope.() -> Unit,
) {
    androidx.compose.ui.window.Dialog(onDismissRequest = onDismiss) {
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .heightIn(max = 560.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        ) {
            Column(
                modifier = Modifier
                    .padding(16.dp)
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                Text(title, style = MaterialTheme.typography.titleLarge)
                content()
                Spacer(Modifier.height(8.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.End,
                ) {
                    TextButton(onClick = onDismiss) { Text("Cancel") }
                    Spacer(Modifier.width(8.dp))
                    Button(
                        onClick = onSubmit,
                        enabled = submitEnabled,
                        colors = ButtonDefaults.buttonColors(
                            containerColor = BrandPrimary,
                            contentColor = Color.White,
                        ),
                    ) {
                        Text(submitLabel)
                    }
                }
            }
        }
    }
}

@Composable
private fun LabeledField(
    label: String,
    value: String,
    singleLine: Boolean = true,
    onValueChange: (String) -> Unit,
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        label = { Text(label) },
        modifier = Modifier.fillMaxWidth(),
        singleLine = singleLine,
        minLines = if (singleLine) 1 else 3,
    )
}

@Composable
private fun MoneyField(
    label: String,
    value: String,
    onValueChange: (String) -> Unit,
) {
    OutlinedTextField(
        value = value,
        onValueChange = { onValueChange(it.filterMoney()) },
        label = { Text(label) },
        modifier = Modifier.fillMaxWidth(),
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
        singleLine = true,
    )
}

@Composable
private fun IntField(
    label: String,
    value: String,
    onValueChange: (String) -> Unit,
) {
    OutlinedTextField(
        value = value,
        onValueChange = { onValueChange(it.filterDigits()) },
        label = { Text(label) },
        modifier = Modifier.fillMaxWidth(),
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
        singleLine = true,
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun DateField(
    label: String,
    value: String,
    onValueChange: (String) -> Unit,
) {
    var openPicker by remember { mutableStateOf(false) }

    OutlinedTextField(
        value = value,
        onValueChange = {},
        label = { Text(label) },
        modifier = Modifier
            .fillMaxWidth()
            .clickable { openPicker = true },
        readOnly = true,
        trailingIcon = { Text("v", style = MaterialTheme.typography.bodyMedium) },
        colors = OutlinedTextFieldDefaults.colors(),
        singleLine = true,
    )

    if (openPicker) {
        val pickerState = rememberDatePickerState(
            initialSelectedDateMillis = isoDateToEpochMillis(value) ?: System.currentTimeMillis(),
        )

        DatePickerDialog(
            onDismissRequest = { openPicker = false },
            confirmButton = {
                TextButton(
                    onClick = {
                        val selectedMillis = pickerState.selectedDateMillis
                        if (selectedMillis != null) {
                            val pickedDate = Instant.ofEpochMilli(selectedMillis)
                                .atZone(ZoneId.systemDefault())
                                .toLocalDate()
                                .toString()
                            onValueChange(pickedDate)
                        }
                        openPicker = false
                    },
                ) { Text("OK") }
            },
            dismissButton = {
                TextButton(onClick = { openPicker = false }) { Text("Cancel") }
            },
        ) {
            DatePicker(state = pickerState)
        }
    }
}

private data class DropdownOption(
    val label: String,
    val value: String,
)

@Composable
private fun DropdownField(
    label: String,
    selectedValue: String,
    options: List<DropdownOption>,
    onSelected: (String) -> Unit,
    includeEmptyOption: Boolean = false,
) {
    var expanded by remember { mutableStateOf(false) }
    val allOptions = remember(options, includeEmptyOption) {
        buildList {
            if (includeEmptyOption) add(DropdownOption("None", ""))
            addAll(options)
        }
    }
    val selectedLabel = allOptions.firstOrNull { it.value == selectedValue }?.label.orEmpty()

    Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
        Text(
            text = label,
            style = MaterialTheme.typography.labelMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Box(modifier = Modifier.fillMaxWidth()) {
            Button(
                onClick = { expanded = true },
                modifier = Modifier.fillMaxWidth(),
                colors = ButtonDefaults.buttonColors(
                    containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.6f),
                    contentColor = MaterialTheme.colorScheme.onSurfaceVariant,
                ),
                contentPadding = PaddingValues(horizontal = 12.dp, vertical = 10.dp),
            ) {
                Text(
                    text = selectedLabel.ifBlank { "Select" },
                    modifier = Modifier.weight(1f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                )
                Spacer(Modifier.width(8.dp))
                Text("v")
            }
            DropdownMenu(
                expanded = expanded,
                onDismissRequest = { expanded = false },
            ) {
                allOptions.forEach { option ->
                    DropdownMenuItem(
                        text = { Text(option.label) },
                        onClick = {
                            onSelected(option.value)
                            expanded = false
                        },
                    )
                }
            }
        }
    }
}

@Composable
private fun LookupHintCard(
    title: String,
    lines: List<String>,
) {
    Card(
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.45f),
        ),
    ) {
        Column(
            modifier = Modifier.padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(4.dp),
        ) {
            Text(title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
            lines.filter { it.isNotBlank() }.forEach { line ->
                Text(line, style = MaterialTheme.typography.bodySmall)
            }
        }
    }
}

/* ----------------------------- Helpers ----------------------------- */

private fun filterRows(rows: List<MenuRecord>, query: String): List<MenuRecord> {
    val q = query.trim().lowercase()
    if (q.isBlank()) return rows
    return rows.filter { row ->
        row.title.lowercase().contains(q) ||
                row.subtitle.lowercase().contains(q) ||
                row.meta.lowercase().contains(q) ||
                (row.id?.toString()?.contains(q) == true)
    }
}

private fun sessionSourceLabel(session: AuthSessionRecord): String {
    val platform = session.devicePlatform?.trim()?.lowercase().orEmpty()
    val name = session.name.trim().lowercase()
    val ua = session.lastUserAgent?.trim()?.lowercase().orEmpty()

    return when {
        platform.contains("android") || platform.contains("ios") -> "App"
        platform.contains("web") || platform.contains("browser") -> "Web"
        name.contains("web") || name.contains("browser") -> "Web"
        name.contains("mobile") || name.contains("android") || name.contains("ios") -> "App"
        ua.contains("mozilla") || ua.contains("chrome") || ua.contains("safari") || ua.contains("firefox") -> "Web"
        ua.contains("okhttp") || ua.contains("retrofit") -> "App"
        else -> "Unknown"
    }
}

private fun sectionIcon(section: AppSection) = when (section) {
    AppSection.DASHBOARD -> Icons.Default.Home
    AppSection.CREDITS -> Icons.Default.AccountBalanceWallet
    AppSection.SPENDINGS -> Icons.Default.Payments
    AppSection.TOKENS -> Icons.Default.Bolt
    AppSection.MAINTENANCE -> Icons.Default.Build
    AppSection.BIKES -> Icons.AutoMirrored.Filled.DirectionsBike
    AppSection.RESPONDENTS -> Icons.Default.People
    AppSection.NOTIFICATIONS -> Icons.Default.Notifications
    AppSection.REPORTS -> Icons.Default.Assessment
    AppSection.SESSION -> Icons.Default.Settings
}

private fun dashboardLabel(key: ModuleKey): String = when (key) {
    ModuleKey.DASHBOARD -> "Balance"
    ModuleKey.CREDITS -> "Total Credited"
    ModuleKey.SPENDINGS -> "Total Spent"
    ModuleKey.TOKENS -> "Token Hostels"
    ModuleKey.MAINTENANCE -> "Maintenance"
    ModuleKey.MASTERS -> "Bikes"
    ModuleKey.RESPONDENTS -> "Respondents"
}

private fun dashboardRows(modules: List<ModuleSummary>): List<List<ModuleSummary>> {
    if (modules.isEmpty()) return emptyList()
    val byKey = modules.associateBy { it.key }
    val consumed = mutableSetOf<ModuleKey>()

    fun pickRow(keys: List<ModuleKey>): List<ModuleSummary> {
        return keys.mapNotNull { key ->
            byKey[key]?.also { consumed += key }
        }
    }

    val rows = mutableListOf<List<ModuleSummary>>()
    listOf(
        listOf(ModuleKey.DASHBOARD, ModuleKey.CREDITS),
        listOf(ModuleKey.SPENDINGS, ModuleKey.TOKENS),
        listOf(ModuleKey.MAINTENANCE, ModuleKey.MASTERS, ModuleKey.RESPONDENTS),
    ).forEach { rowKeys ->
        val row = pickRow(rowKeys)
        if (row.isNotEmpty()) rows += row
    }

    val leftovers = modules.filter { it.key !in consumed }
    rows += leftovers.chunked(2)
    return rows
}

private fun dashboardSectionFor(key: ModuleKey): AppSection? = when (key) {
    ModuleKey.DASHBOARD -> AppSection.DASHBOARD
    ModuleKey.CREDITS -> AppSection.CREDITS
    ModuleKey.SPENDINGS -> AppSection.SPENDINGS
    ModuleKey.TOKENS -> AppSection.TOKENS
    ModuleKey.MAINTENANCE -> AppSection.MAINTENANCE
    ModuleKey.MASTERS -> AppSection.BIKES
    ModuleKey.RESPONDENTS -> AppSection.RESPONDENTS
}

@Composable
private fun dashboardValueColor(key: ModuleKey): Color = when (key) {
    ModuleKey.DASHBOARD -> Color(0xFF1B8C44)
    ModuleKey.CREDITS -> Color(0xFF0B63CE)
    ModuleKey.SPENDINGS -> Color(0xFFB65C00)
    ModuleKey.TOKENS -> Color(0xFF4E3AA3)
    ModuleKey.MAINTENANCE -> Color(0xFF7A3E00)
    ModuleKey.MASTERS -> Color(0xFF1C3E78)
    ModuleKey.RESPONDENTS -> Color(0xFF6A1B9A)
}

@Composable
private fun dashboardValueTextStyle(value: String) = when {
    value.length > 32 -> MaterialTheme.typography.bodySmall
    value.length > 24 -> MaterialTheme.typography.bodyMedium
    value.length > 16 -> MaterialTheme.typography.titleSmall
    else -> MaterialTheme.typography.titleMedium
}

private fun todayDate(): String = LocalDate.now().toString()

private fun nextDueInDays(baseDate: String, days: Long): String {
    val parsed = runCatching { LocalDate.parse(baseDate.trim()) }.getOrNull() ?: return ""
    return parsed.plusDays(days).toString()
}

private fun moneyValue(amount: Double): String = String.format(Locale.US, "%,.2f", amount)

private fun generateQrImage(payload: String): androidx.compose.ui.graphics.ImageBitmap? {
    if (payload.isBlank()) return null
    return runCatching {
        val size = 512
        val matrix = QRCodeWriter().encode(payload, BarcodeFormat.QR_CODE, size, size)
        val bitmap = Bitmap.createBitmap(size, size, Bitmap.Config.ARGB_8888)
        for (x in 0 until size) {
            for (y in 0 until size) {
                bitmap.setPixel(x, y, if (matrix.get(x, y)) android.graphics.Color.BLACK else android.graphics.Color.WHITE)
            }
        }
        bitmap.asImageBitmap()
    }.getOrNull()
}

private fun isIsoDate(value: String): Boolean =
    runCatching { LocalDate.parse(value.trim()) }.isSuccess

private fun isoDateToEpochMillis(value: String): Long? {
    val date = runCatching { LocalDate.parse(value.trim()) }.getOrNull() ?: return null
    return date.atStartOfDay(ZoneId.systemDefault()).toInstant().toEpochMilli()
}

private fun String.filterDigits(): String = filter { it.isDigit() }

private fun String.filterMoney(): String {
    // allows digits + one dot; keeps it simple
    val trimmed = trim()
    val out = StringBuilder()
    var dotUsed = false
    for (c in trimmed) {
        when {
            c.isDigit() -> out.append(c)
            c == '.' && !dotUsed -> {
                dotUsed = true
                out.append(c)
            }
        }
    }
    return out.toString()
}

private fun exportLookupsPdf(context: Context, lookups: MenuLookupsState): String? {
    return runCatching {
        val pageWidth = 595
        val pageHeight = 842
        val document = PdfDocument()
        val titlePaint = Paint().apply {
            textSize = 14f
            isFakeBoldText = true
        }
        val textPaint = Paint().apply { textSize = 10f }
        val smallPaint = Paint().apply { textSize = 9f }

        var pageNumber = 1
        var page = document.startPage(PdfDocument.PageInfo.Builder(pageWidth, pageHeight, pageNumber).create())
        var canvas = page.canvas
        var y = 36
        val left = 28f
        val maxChars = 92

        fun newPage() {
            document.finishPage(page)
            pageNumber += 1
            page = document.startPage(PdfDocument.PageInfo.Builder(pageWidth, pageHeight, pageNumber).create())
            canvas = page.canvas
            y = 36
        }

        fun drawLine(text: String, paint: Paint = textPaint, step: Int = 14) {
            if (y > pageHeight - 30) newPage()
            val safeText = if (text.length > maxChars) text.take(maxChars - 1) + "…" else text
            canvas.drawText(safeText, left, y.toFloat(), paint)
            y += step
        }

        fun drawTitle(text: String) = drawLine(text, titlePaint, step = 18)

        drawTitle("Skybrix PettyCash - Reports & Lookups")
        drawLine("Generated: ${Instant.now()}", smallPaint, step = 12)
        drawLine("Available net balance: KES ${moneyValue(lookups.availableBatchBalance)}", textPaint)
        y += 6

        drawTitle("Funding Batches")
        if (lookups.batches.isEmpty()) {
            drawLine("No batch records.")
        } else {
            lookups.batches.forEach { row ->
                drawLine("#${row.id ?: "-"} ${row.title}")
                drawLine("   ${row.subtitle}", smallPaint, step = 12)
            }
        }
        y += 6

        drawTitle("Bikes")
        if (lookups.bikes.isEmpty()) {
            drawLine("No bike records.")
        } else {
            lookups.bikes.forEach { row ->
                drawLine("#${row.id ?: "-"} ${row.title}")
                if (row.subtitle.isNotBlank()) drawLine("   ${row.subtitle}", smallPaint, step = 12)
                if (row.meta.isNotBlank()) drawLine("   ${row.meta}", smallPaint, step = 12)
            }
        }
        y += 6

        drawTitle("Respondents")
        if (lookups.respondents.isEmpty()) {
            drawLine("No respondent records.")
        } else {
            lookups.respondents.forEach { row ->
                drawLine("#${row.id ?: "-"} ${row.title}")
                if (row.subtitle.isNotBlank()) drawLine("   ${row.subtitle}", smallPaint, step = 12)
                if (row.meta.isNotBlank()) drawLine("   ${row.meta}", smallPaint, step = 12)
            }
        }
        y += 6

        drawTitle("Hostels")
        drawLine("Total hostels: ${lookups.hostels.size}")
        if (lookups.hostels.isEmpty()) {
            drawLine("No hostel records.")
        } else {
            lookups.hostels.forEach { row ->
                drawLine("#${row.id ?: "-"} ${row.title}")
                if (row.subtitle.isNotBlank()) drawLine("   ${row.subtitle}", smallPaint, step = 12)
                if (row.meta.isNotBlank()) drawLine("   ${row.meta}", smallPaint, step = 12)
            }
        }

        document.finishPage(page)
        val dir = context.getExternalFilesDir(Environment.DIRECTORY_DOCUMENTS) ?: context.filesDir
        if (!dir.exists()) dir.mkdirs()
        val file = File(dir, "pettycash-lookups-${System.currentTimeMillis()}.pdf")
        FileOutputStream(file).use { out -> document.writeTo(out) }
        document.close()
        file.absolutePath
    }.getOrNull()
}

private fun csvSafe(raw: String): String {
    val value = raw.replace("\"", "\"\"")
    return "\"$value\""
}
