<?php
require_once 'db_connect.php';

$query = "SELECT r.id, 
    (SELECT COUNT(*) FROM reservations res 
     WHERE res.id_sali = r.id 
     AND NOW() BETWEEN res.czas_start AND res.czas_stop 
     AND res.status = 'approved') as is_reserved
    FROM rooms r WHERE r.dostepnosc = 1";
$result = $conn->query($query);

$statuses = [];
while ($row = $result->fetch_assoc()) {
    $statuses[$row['id']] = $row['is_reserved'] > 0;
}

header('Content-Type: application/json');
echo json_encode($statuses);
