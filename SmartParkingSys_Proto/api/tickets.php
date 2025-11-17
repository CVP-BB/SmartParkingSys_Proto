<?php
require_once "db.php";
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner = trim($_POST['owner'] ?? '');
    $plate = trim($_POST['plate'] ?? '');
    $vtype = trim($_POST['vtype'] ?? '');
    $floor = trim($_POST['floor'] ?? ''); // expected "Floor 1" or "Floor 2"
    $zone_code = trim($_POST['zone_code'] ?? ''); // "A","B","C"
    $slot_number = intval($_POST['slot_number'] ?? 0);

    if (!$owner || !$plate || !$vtype || !$floor || !$zone_code || !$slot_number) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    // validate vehicle type -> zone mapping (safety)
    $zone_map = ['A' => 'Car', 'B' => 'Motorcycle', 'C' => 'Truck'];
    if (!isset($zone_map[$zone_code]) || $zone_map[$zone_code] !== $vtype) {
        echo json_encode(['success' => false, 'error' => 'Vehicle type does not match selected zone']);
        exit;
    }

    // Check if slot already taken (active)
    $zone_full_name = "Zone $zone_code - Slot $slot_number";
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM tickets WHERE floor = ? AND zone = ? AND slot_number = ? AND active = 1");
    $stmt->execute([$floor, $zone_full_name, $slot_number]);
    $row = $stmt->fetch();
    if ($row && $row['c'] > 0) {
        echo json_encode(['success' => false, 'error' => 'Selected slot is already taken']);
        exit;
    }

    $ticket_uid = uniqid("TICKET-");

    $stmt = $pdo->prepare("INSERT INTO tickets (ticket_uid, owner, plate, vtype, floor, zone, zone_code, slot_number, active)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$ticket_uid, $owner, $plate, $vtype, $floor, $zone_full_name, $zone_code, $slot_number]);

    // return full ticket record
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE ticket_uid = ?");
    $stmt->execute([$ticket_uid]);
    $ticket = $stmt->fetch();

    echo json_encode(['success' => true, 'ticket' => $ticket]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT * FROM tickets ORDER BY created_at DESC");
    echo json_encode($stmt->fetchAll());
}
