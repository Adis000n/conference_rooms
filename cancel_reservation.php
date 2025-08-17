<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowa metoda']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$reservation_id = (int)$data['reservation_id'];
$user_id = $_SESSION['user_id'];

// Verify that the reservation belongs to the current user and is in pending status
$check_query = "SELECT id, status, czas_start FROM reservations 
                WHERE id = ? AND id_uzytkownika = ? AND status = 'pending'";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Nie można anulować tej rezerwacji']);
    exit();
}

$reservation = $result->fetch_assoc();

// Check if reservation is not in the past
if (strtotime($reservation['czas_start']) <= time()) {
    echo json_encode(['success' => false, 'message' => 'Nie można anulować rezerwacji z przeszłości']);
    exit();
}

// Update reservation status to cancelled
$update_query = "UPDATE reservations SET status = 'cancelled' WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $reservation_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Rezerwacja została anulowana']);
} else {
    echo json_encode(['success' => false, 'message' => 'Błąd podczas anulowania rezerwacji']);
}
?>
