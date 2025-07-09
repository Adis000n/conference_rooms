<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

$query = "SELECT 
    DATE_FORMAT(czas_start, '%Y-%m-%d %H:%i') as start_formatted,
    DATE_FORMAT(czas_stop, '%Y-%m-%d %H:%i') as end_formatted,
    NOW() BETWEEN czas_start AND czas_stop as is_current,
    status,
    opis,
    u.imie, u.nazwisko
FROM reservations r
JOIN users u ON r.id_uzytkownika = u.id
WHERE r.id_sali = ? AND r.czas_stop >= NOW() AND r.status IN ('approved', 'pending')
ORDER BY r.czas_start";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
$is_currently_booked = false;

while ($row = $result->fetch_assoc()) {
    $bookings[] = [
        'start' => $row['start_formatted'],
        'end' => $row['end_formatted'],
        'status' => $row['status'],
        'description' => $row['opis'],
        'user' => $row['imie'] . ' ' . $row['nazwisko']
    ];
    if ($row['is_current'] && $row['status'] === 'approved') {
        $is_currently_booked = true;
    }
}

$response = [
    'is_currently_booked' => $is_currently_booked,
    'bookings' => $bookings
];

header('Content-Type: application/json');
echo json_encode($response);
?>
