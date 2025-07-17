<?php
header('Content-Type: application/json');

// Load MikroTik config
$apiConfig = include __DIR__ . '/../config/mikrotik.php';
$conn = include __DIR__ . '/../config/db.php';

// Load MikroTik API class
require_once __DIR__ . '/routeros_api.class.php';


$API = new RouterosAPI();

// Connect to MikroTik
if (!$API->connect($apiConfig['host'], $apiConfig['user'], $apiConfig['pass'], $apiConfig['port'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not connect to MikroTik']);
    exit;
}

// Fetch system resource data
$API->write('/system/resource/print');
$resource = $API->read()[0] ?? [];

// Fetch interface data
$API->write('/interface/print');
$interfaces = $API->read();

// Check for uplink interface from DB
$uplinkData = ['interface' => null, 'rx' => 0, 'tx' => 0];
if ($conn && $conn->query("SHOW TABLES LIKE 'uplinks'")->num_rows > 0) {
    $result = $conn->query("SELECT * FROM uplinks WHERE graph_enabled = 1 LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $uplink = $result->fetch_assoc()['interface'];
        foreach ($interfaces as $iface) {
            if ($iface['name'] === $uplink) {
                $uplinkData = [
                    'interface' => $iface['name'],
                    'rx' => (int)($iface['rx-byte'] ?? 0),
                    'tx' => (int)($iface['tx-byte'] ?? 0)
                ];
                break;
            }
        }
    }
}

// Format all interfaces
$interfaceTraffic = array_map(function ($iface) {
    return [
        'name' => $iface['name'] ?? 'unknown',
        'rx' => (int)($iface['rx-byte'] ?? 0),
        'tx' => (int)($iface['tx-byte'] ?? 0),
        'status' => ($iface['running'] ?? 'false') === 'true' ? 'up' : 'down'
    ];
}, $interfaces);

// User stats (you can replace with real DB logic later)
$userStats = [
    'active_users_count' => 15,
    'total_users_count' => 40,
    'unique_visitors' => 120,
];

// Final JSON response
$response = [
    'system' => [
        'ram_usage' => isset($resource['total-memory']) ? round(($resource['total-memory'] - $resource['free-memory']) / 1024 / 1024, 1) . ' MB' : 'N/A',
        'ram_percentage' => isset($resource['total-memory']) ? round((($resource['total-memory'] - $resource['free-memory']) / $resource['total-memory']) * 100, 1) : 0,
        'cpu_usage' => $resource['cpu-load'] ?? 0,
        'storage_usage' => isset($resource['total-hdd-space']) ? round(($resource['total-hdd-space'] - $resource['free-hdd-space']) / 1024 / 1024, 1) . ' MB' : 'N/A',
        'hdd_percentage' => isset($resource['total-hdd-space']) ? round((($resource['total-hdd-space'] - $resource['free-hdd-space']) / $resource['total-hdd-space']) * 100, 1) : 0,
        'uptime' => $resource['uptime'] ?? 'unknown'
    ],
    'interface_traffic' => $interfaceTraffic,
    'uplink' => $uplinkData,
    ...$userStats
];

echo json_encode($response);

$API->disconnect();