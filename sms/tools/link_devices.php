<?php
require_once __DIR__ . '/../config.php';
$port = defined('DB_PORT') ? (int) DB_PORT : 3306;
$conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME, $port);
$conn->set_charset('utf8mb4');
$adminId = 1;
$models = ['Office Pixel 8','Field Galaxy S22','Warehouse OnePlus'];
$stmt = $conn->prepare("INSERT IGNORE INTO DeviceUser (name, deviceID, userID, active) VALUES (?,?,?,1)");
$devs = $conn->query("SELECT ID, model FROM Device WHERE userID=$adminId");
while ($d = $devs->fetch_assoc()) {
    $stmt->bind_param('sii', $d['model'], $d['ID'], $adminId);
    $stmt->execute();
}
echo "DeviceUser links: " . $conn->query("SELECT COUNT(*) FROM DeviceUser")->fetch_row()[0] . "\n";
