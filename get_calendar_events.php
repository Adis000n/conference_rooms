<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$start = $input['start'] ?? '';
$end = $input['end'] ?? '';
$rooms = $input['rooms'] ?? [];
$statuses = $input['statuses'] ?? [];

if (empty($rooms) || empty($statuses)) {
    echo json_encode([]);
    exit();
}

// Przygotuj placeholdery dla IN clause
$room_placeholders = str_repeat('?,', count($rooms) - 1) . '?';
$status_placeholders = str_repeat('?,', count($statuses) - 1) . '?';

$query = "SELECT r.*, 
          rm.nazwa as room_name,
          u.imie, u.nazwisko,
          r.id_uzytkownika = ? as is_my_reservation
          FROM reservations r
          JOIN rooms rm ON r.id_sali = rm.id
          JOIN users u ON r.id_uzytkownika = u.id
          WHERE r.id_sali IN ($room_placeholders)
          AND r.status IN ($status_placeholders)
          AND r.czas_start <= ?
          AND r.czas_stop >= ?
          ORDER BY r.czas_start";

$stmt = $conn->prepare($query);

// Przygotuj parametry
$params = [$_SESSION['user_id']];
$params = array_merge($params, $rooms);
$params = array_merge($params, $statuses);
$params[] = $end;
$params[] = $start;

// Przygotuj typy parametrów
$types = 'i' . str_repeat('i', count($rooms)) . str_repeat('s', count($statuses)) . 'ss';

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $color = '#28a745'; // approved - green
    if ($row['status'] === 'pending') {
        $color = '#ffc107'; // pending - yellow
    }
    if ($row['is_my_reservation']) {
        $color = '#17a2b8'; // my reservations - blue
    }
    
    $title = $row['room_name'];
    if ($row['is_my_reservation']) {
        $title .= ' (Moja)';
    } else {
        $title .= ' - ' . $row['imie'] . ' ' . $row['nazwisko'];
    }
    
    $events[] = [
        'id' => $row['id'],
        'title' => $title,
        'start' => $row['czas_start'],
        'end' => $row['czas_stop'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'room_id' => $row['id_sali'],
            'room_name' => $row['room_name'],
            'status' => $row['status'],
            'description' => $row['opis'] ?? '',
            'user_name' => $row['imie'] . ' ' . $row['nazwisko'],
            'is_my_reservation' => $row['is_my_reservation']
        ]
    ];
}

echo json_encode($events);
?>
