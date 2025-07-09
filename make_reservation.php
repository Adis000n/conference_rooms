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
$room_id = (int)$data['room_id'];
$start_datetime = $data['start_datetime'];
$end_datetime = $data['end_datetime'];
$description = $data['description'] ?? '';
$user_id = $_SESSION['user_id'];

// Walidacja danych
if (empty($room_id) || empty($start_datetime) || empty($end_datetime)) {
    echo json_encode(['success' => false, 'message' => 'Wszystkie pola są wymagane']);
    exit();
}

// Sprawdź czy data nie jest w przeszłości
if (strtotime($start_datetime) <= time()) {
    echo json_encode(['success' => false, 'message' => 'Nie można rezerwować w przeszłości']);
    exit();
}

// Sprawdź czy godzina zakończenia jest po godzinie rozpoczęcia
if (strtotime($end_datetime) <= strtotime($start_datetime)) {
    echo json_encode(['success' => false, 'message' => 'Godzina zakończenia musi być późniejsza niż rozpoczęcia']);
    exit();
}

// Sprawdź dostępność sali
$check_query = "SELECT id FROM reservations 
                WHERE id_sali = ? 
                AND status NOT IN ('rejected', 'cancelled')
                AND (
                    (czas_start <= ? AND czas_stop > ?) OR
                    (czas_start < ? AND czas_stop >= ?) OR
                    (czas_start >= ? AND czas_stop <= ?)
                )";

$stmt = $conn->prepare($check_query);
$stmt->bind_param("issssss", $room_id, $start_datetime, $start_datetime, 
                  $end_datetime, $end_datetime, $start_datetime, $end_datetime);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Sala jest już zarezerwowana w tym czasie']);
    exit();
}

// Sprawdź czy sala istnieje i jest dostępna
$room_check = "SELECT dostepnosc FROM rooms WHERE id = ?";
$stmt = $conn->prepare($room_check);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room_result = $stmt->get_result();

if ($room_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Sala nie istnieje']);
    exit();
}

$room = $room_result->fetch_assoc();
if (isset($room['dostepnosc']) && !$room['dostepnosc']) {
    echo json_encode(['success' => false, 'message' => 'Sala jest niedostępna']);
    exit();
}

// Dodaj rezerwację
$insert_query = "INSERT INTO reservations (id_uzytkownika, id_sali, czas_start, czas_stop, opis, status) 
                 VALUES (?, ?, ?, ?, ?, 'pending')";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("iisss", $user_id, $room_id, $start_datetime, $end_datetime, $description);

if ($stmt->execute()) {
    // Dodaj powiadomienie dla administratorów
    $notification_query = "INSERT INTO notifications (id_uzytkownika, wiadomosc, typ_powiadomienia) 
                          SELECT id, 'Nowa rezerwacja oczekuje na zatwierdzenie', 'new_reservation' 
                          FROM users WHERE rola = 'Menedzer'";
    $conn->query($notification_query);
    
    echo json_encode(['success' => true, 'message' => 'Rezerwacja została utworzona i oczekuje na zatwierdzenie']);
} else {
    echo json_encode(['success' => false, 'message' => 'Błąd podczas tworzenia rezerwacji']);
}
?>
