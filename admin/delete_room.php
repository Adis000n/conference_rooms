<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Menedzer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$roomId = $data['id'];

// First delete all reservations for this room
$stmt = $conn->prepare("DELETE FROM reservations WHERE id_sali = ?");
$stmt->bind_param("i", $roomId);
$stmt->execute();

// Then delete the room
$stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
$stmt->bind_param("i", $roomId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
