<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $query = "SELECT rt.*, r.nazwa as room_name 
              FROM reservation_templates rt 
              JOIN rooms r ON rt.id_sali = r.id 
              WHERE rt.id_uzytkownika = ? 
              ORDER BY rt.nazwa_szablonu";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $templates = [];
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
    
    echo json_encode($templates);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
