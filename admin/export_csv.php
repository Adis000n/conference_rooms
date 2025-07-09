<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Menedzer') {
    header("Location: ../login.php");
    exit();
}

$type = $_GET['type'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');

// Ustawienia nagłówka dla pliku CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="raport_' . $type . '_' . date('Y-m-d') . '.csv"');

// Utwórz uchwyt do outputu
$output = fopen('php://output', 'w');

// Dodaj BOM dla UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch ($type) {
    case 'reservations':
        // Nagłówki CSV
        fputcsv($output, [
            'ID Rezerwacji',
            'Użytkownik',
            'Email',
            'Sala',
            'Data rozpoczęcia',
            'Data zakończenia',
            'Status',
            'Opis',
            'Data utworzenia'
        ]);
        
        // Dane rezerwacji
        $query = "SELECT r.id, u.imie, u.nazwisko, u.`e-mail`, rm.nazwa as room_name,
                  r.czas_start, r.czas_stop, r.status, r.opis, r.data_utworzenia
                  FROM reservations r
                  JOIN users u ON r.id_uzytkownika = u.id
                  JOIN rooms rm ON r.id_sali = rm.id
                  WHERE DATE(r.czas_start) BETWEEN ? AND ?
                  ORDER BY r.czas_start";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['imie'] . ' ' . $row['nazwisko'],
                $row['e-mail'],
                $row['room_name'],
                $row['czas_start'],
                $row['czas_stop'],
                $row['status'],
                $row['opis'] ?? '',
                $row['data_utworzenia']
            ]);
        }
        break;
        
    case 'rooms_usage':
        // Nagłówki CSV
        fputcsv($output, [
            'Sala',
            'Pojemność',
            'Liczba rezerwacji',
            'Zatwierdzone rezerwacje',
            'Procent wykorzystania'
        ]);
        
        // Dane wykorzystania sal
        $total_query = "SELECT COUNT(*) as total FROM reservations WHERE DATE(czas_start) BETWEEN ? AND ?";
        $stmt = $conn->prepare($total_query);
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $total_reservations = $stmt->get_result()->fetch_assoc()['total'];
        
        $query = "SELECT r.nazwa, r.pojemnosc,
                  COUNT(res.id) as reservation_count,
                  COUNT(CASE WHEN res.status = 'approved' THEN 1 END) as approved_count
                  FROM rooms r
                  LEFT JOIN reservations res ON r.id = res.id_sali AND DATE(res.czas_start) BETWEEN ? AND ?
                  GROUP BY r.id, r.nazwa, r.pojemnosc
                  ORDER BY reservation_count DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $percentage = $total_reservations > 0 ? round(($row['reservation_count'] / $total_reservations) * 100, 2) : 0;
            fputcsv($output, [
                $row['nazwa'],
                $row['pojemnosc'],
                $row['reservation_count'],
                $row['approved_count'],
                $percentage . '%'
            ]);
        }
        break;
        
    case 'users_activity':
        // Nagłówki CSV
        fputcsv($output, [
            'Użytkownik',
            'Email',
            'Rola',
            'Liczba rezerwacji',
            'Zatwierdzone',
            'Odrzucone',
            'Oczekujące'
        ]);
        
        // Dane aktywności użytkowników
        $query = "SELECT u.imie, u.nazwisko, u.`e-mail`, u.rola,
                  COUNT(r.id) as total_reservations,
                  COUNT(CASE WHEN r.status = 'approved' THEN 1 END) as approved,
                  COUNT(CASE WHEN r.status = 'rejected' THEN 1 END) as rejected,
                  COUNT(CASE WHEN r.status = 'pending' THEN 1 END) as pending
                  FROM users u
                  LEFT JOIN reservations r ON u.id = r.id_uzytkownika AND DATE(r.czas_start) BETWEEN ? AND ?
                  GROUP BY u.id, u.imie, u.nazwisko, u.`e-mail`, u.rola
                  ORDER BY total_reservations DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['imie'] . ' ' . $row['nazwisko'],
                $row['e-mail'],
                $row['rola'],
                $row['total_reservations'],
                $row['approved'],
                $row['rejected'],
                $row['pending']
            ]);
        }
        break;
        
    default:
        fputcsv($output, ['Błąd', 'Nieprawidłowy typ raportu']);
        break;
}

fclose($output);
?>
