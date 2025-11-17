<?php
require_once "db.php";
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_uid = trim($_POST['ticket_uid'] ?? '');
    $event_type = trim($_POST['event_type'] ?? '');

    if (!$ticket_uid || !$event_type || !in_array($event_type, ['entry','exit'])) {
        echo json_encode(['success' => false, 'error' => 'Missing or invalid fields']);
        exit;
    }

    // fetch ticket snapshot
    $stmt = $pdo->prepare("SELECT ticket_uid, owner, plate, vtype, floor, zone, zone_code, slot_number FROM tickets WHERE ticket_uid = ?");
    $stmt->execute([$ticket_uid]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        exit;
    }

    // insert log including snapshot
    $stmt = $pdo->prepare("INSERT INTO logs (ticket_uid, event_type, owner_snapshot, plate_snapshot, vtype_snapshot, floor_snapshot, zone_snapshot, zone_code_snapshot, slot_number_snapshot) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $ticket_uid,
        $event_type,
        $ticket['owner'],
        $ticket['plate'],
        $ticket['vtype'],
        $ticket['floor'],
        $ticket['zone'],
        $ticket['zone_code'],
        $ticket['slot_number']
    ]);

    // update active flag
    if ($event_type === 'exit') {
        $pdo->prepare("UPDATE tickets SET active = 0 WHERE ticket_uid = ?")->execute([$ticket_uid]);
    } else if ($event_type === 'entry') {
        $pdo->prepare("UPDATE tickets SET active = 1 WHERE ticket_uid = ?")->execute([$ticket_uid]);
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // latest 200 logs
    $stmt = $pdo->query("SELECT * FROM logs ORDER BY created_at DESC LIMIT 200");
    echo json_encode($stmt->fetchAll());
}
