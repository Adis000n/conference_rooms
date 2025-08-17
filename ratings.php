<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

// Handle AJAX requests for rating operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'add_rating':
                addRating($conn, $_SESSION['user_id'], $input);
                break;
            case 'get_ratings':
                getRatings($conn, $input['room_id']);
                break;
            case 'get_user_rating':
                getUserRating($conn, $_SESSION['user_id'], $input['room_id']);
                break;
        }
    }
}

function addRating($conn, $user_id, $data) {
    $room_id = (int)$data['room_id'];
    $rating = (int)$data['rating'];
    $comment = isset($data['comment']) ? trim($data['comment']) : '';
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Ocena musi być z zakresu 1-5']);
        return;
    }
    
    // Check if user has already rated this room
    $check_stmt = $conn->prepare("SELECT id FROM ratings WHERE id_uzytkownika = ? AND id_sali = ?");
    $check_stmt->bind_param("ii", $user_id, $room_id);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        // Update existing rating
        $stmt = $conn->prepare("UPDATE ratings SET ocena = ?, komentarz = ?, data_utworzenia = CURRENT_TIMESTAMP WHERE id_uzytkownika = ? AND id_sali = ?");
        $stmt->bind_param("isii", $rating, $comment, $user_id, $room_id);
    } else {
        // Insert new rating
        $stmt = $conn->prepare("INSERT INTO ratings (id_uzytkownika, id_sali, ocena, komentarz) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $user_id, $room_id, $rating, $comment);
    }
    
    if ($stmt->execute()) {
        // Update room's average rating and count
        updateRoomRatingStats($conn, $room_id);
        echo json_encode(['success' => true, 'message' => 'Ocena została zapisana']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Błąd podczas zapisywania oceny']);
    }
}

function getRatings($conn, $room_id) {
    $stmt = $conn->prepare("
        SELECT r.ocena, r.komentarz, r.data_utworzenia, 
               CONCAT(u.imie, ' ', u.nazwisko) as nazwa_uzytkownika
        FROM ratings r 
        JOIN users u ON r.id_uzytkownika = u.id 
        WHERE r.id_sali = ? 
        ORDER BY r.data_utworzenia DESC
    ");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ratings = [];
    while ($row = $result->fetch_assoc()) {
        $ratings[] = $row;
    }
    
    // Get average rating and count
    $avg_stmt = $conn->prepare("
        SELECT AVG(ocena) as srednia, COUNT(*) as liczba 
        FROM ratings 
        WHERE id_sali = ?
    ");
    $avg_stmt->bind_param("i", $room_id);
    $avg_stmt->execute();
    $avg_result = $avg_stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'ratings' => $ratings,
        'average' => $avg_result['srednia'] ? round($avg_result['srednia'], 1) : 0,
        'count' => $avg_result['liczba']
    ]);
}

function getUserRating($conn, $user_id, $room_id) {
    $stmt = $conn->prepare("SELECT ocena, komentarz FROM ratings WHERE id_uzytkownika = ? AND id_sali = ?");
    $stmt->bind_param("ii", $user_id, $room_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    echo json_encode($result ?: null);
}

function updateRoomRatingStats($conn, $room_id) {
    $stmt = $conn->prepare("
        UPDATE rooms 
        SET srednia_ocena = (
            SELECT AVG(ocena) FROM ratings WHERE id_sali = ?
        ),
        liczba_ocen = (
            SELECT COUNT(*) FROM ratings WHERE id_sali = ?
        )
        WHERE id = ?
    ");
    $stmt->bind_param("iii", $room_id, $room_id, $room_id);
    $stmt->execute();
}
?>
