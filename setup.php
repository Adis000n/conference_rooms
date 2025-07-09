<?php
// Skrypt inicjalizujący bazę danych z przykładowymi danymi
require_once 'db_connect.php';

echo "<h2>Inicjalizacja bazy danych</h2>";

// Sprawdź czy tabele istnieją
$tables = ['users', 'rooms', 'reservations', 'notifications'];
$missing_tables = [];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "<p style='color: red;'>Brakujące tabele: " . implode(', ', $missing_tables) . "</p>";
    echo "<p>Proszę zaimportować plik system_rezerwacji_sal_konferencyjnych.sql</p>";
    exit;
}

echo "<p style='color: green;'>✓ Wszystkie tabele istnieją</p>";

// Sprawdź czy są sale
$result = $conn->query("SELECT COUNT(*) as count FROM rooms");
$room_count = $result->fetch_assoc()['count'];

if ($room_count == 0) {
    echo "<p>Dodawanie przykładowych sal...</p>";
    
    $rooms = [
        ['Sala Konferencyjna A', 20, 'Projektor, ekran, klimatyzacja, flipchart, system audio', 1],
        ['Sala Konferencyjna B', 15, 'Projektor, ekran, klimatyzacja, tablica', 1],
        ['Sala Konferencyjna C', 30, 'Projektor, ekran, klimatyzacja, system audio-video, mikrofony', 1],
        ['Sala Szkoleniowa', 25, 'Projektor, ekran, klimatyzacja, flipchart, komputer', 1],
        ['Sala Wykonawcza', 10, 'Monitor, klimatyzacja, tablica', 1]
    ];
    
    $stmt = $conn->prepare("INSERT INTO rooms (nazwa, pojemnosc, wyposazenie, dostepnosc) VALUES (?, ?, ?, ?)");
    
    foreach ($rooms as $room) {
        $stmt->bind_param("sisi", $room[0], $room[1], $room[2], $room[3]);
        $stmt->execute();
    }
    
    echo "<p style='color: green;'>✓ Dodano " . count($rooms) . " sal</p>";
} else {
    echo "<p style='color: blue;'>ℹ Znaleziono $room_count sal w bazie</p>";
}

// Sprawdź czy wszystkie wymagane kolumny istnieją
$columns_to_check = [
    'reservations' => ['status', 'data_utworzenia', 'opis'],
    'notifications' => ['data_utworzenia', 'przeczytane'],
    'rooms' => ['dostepnosc']
];

foreach ($columns_to_check as $table => $columns) {
    $result = $conn->query("DESCRIBE $table");
    $existing_columns = [];
    
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    $missing_columns = array_diff($columns, $existing_columns);
    
    if (!empty($missing_columns)) {
        echo "<p style='color: orange;'>⚠ Tabela $table - brakujące kolumny: " . implode(', ', $missing_columns) . "</p>";
        
        // Dodaj brakujące kolumny
        foreach ($missing_columns as $column) {
            switch ("$table.$column") {
                case 'reservations.status':
                    $conn->query("ALTER TABLE reservations ADD COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending'");
                    break;
                case 'reservations.data_utworzenia':
                    $conn->query("ALTER TABLE reservations ADD COLUMN data_utworzenia TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                    break;
                case 'reservations.opis':
                    $conn->query("ALTER TABLE reservations ADD COLUMN opis TEXT NULL");
                    break;
                case 'notifications.data_utworzenia':
                    $conn->query("ALTER TABLE notifications ADD COLUMN data_utworzenia TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                    break;
                case 'notifications.przeczytane':
                    $conn->query("ALTER TABLE notifications ADD COLUMN przeczytane BOOLEAN DEFAULT FALSE");
                    break;
                case 'rooms.dostepnosc':
                    $conn->query("ALTER TABLE rooms ADD COLUMN dostepnosc BOOLEAN DEFAULT TRUE");
                    break;
            }
            echo "<p style='color: green;'>✓ Dodano kolumnę $column do tabeli $table</p>";
        }
    }
}

// Sprawdź czy są indeksy
$conn->query("ALTER TABLE reservations ADD INDEX IF NOT EXISTS idx_room_time (id_sali, czas_start, czas_stop)");
$conn->query("ALTER TABLE reservations ADD INDEX IF NOT EXISTS idx_user (id_uzytkownika)");

echo "<p style='color: green;'>✓ Indeksy zostały sprawdzone/dodane</p>";

// Sprawdź hasła użytkowników
$result = $conn->query("SELECT id, haslo FROM users WHERE LENGTH(haslo) < 10");
$weak_passwords = [];

while ($row = $result->fetch_assoc()) {
    $weak_passwords[] = $row['id'];
}

if (!empty($weak_passwords)) {
    echo "<p style='color: orange;'>⚠ Znaleziono " . count($weak_passwords) . " użytkowników z niezaszyfrowanymi hasłami</p>";
    echo "<p>Szyfrowanie haseł...</p>";
    
    $updates = [
        3 => '123',  // Mikolaj
        4 => '321',  // Pawel  
        5 => '231',  // Michał
        6 => '132'   // Bartłomiej
    ];
    
    $stmt = $conn->prepare("UPDATE users SET haslo = ? WHERE id = ?");
    
    foreach ($updates as $user_id => $plain_password) {
        if (in_array($user_id, $weak_passwords)) {
            $hashed = password_hash($plain_password, PASSWORD_DEFAULT);
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();
            echo "<p style='color: green;'>✓ Zaszyfrowano hasło dla użytkownika ID: $user_id</p>";
        }
    }
}

// Dodaj przykładowe rezerwacje jeśli ich nie ma
$result = $conn->query("SELECT COUNT(*) as count FROM reservations");
$reservation_count = $result->fetch_assoc()['count'];

if ($reservation_count == 0) {
    echo "<p>Dodawanie przykładowych rezerwacji...</p>";
    
    // Pobierz IDs sal i użytkowników
    $rooms = $conn->query("SELECT id FROM rooms LIMIT 3")->fetch_all(MYSQLI_ASSOC);
    $users = $conn->query("SELECT id FROM users WHERE rola = 'Gość' LIMIT 3")->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($rooms) && !empty($users)) {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $day_after = date('Y-m-d', strtotime('+2 days'));
        
        $reservations = [
            [$users[0]['id'], $rooms[0]['id'], "$tomorrow 10:00:00", "$tomorrow 12:00:00", 'approved', 'Spotkanie zespołu projektowego'],
            [$users[1]['id'], $rooms[1]['id'], "$tomorrow 14:00:00", "$tomorrow 16:00:00", 'pending', 'Prezentacja dla klienta'],
            [$users[2]['id'], $rooms[0]['id'], "$day_after 09:00:00", "$day_after 11:00:00", 'approved', 'Szkolenie pracowników']
        ];
        
        $stmt = $conn->prepare("INSERT INTO reservations (id_uzytkownika, id_sali, czas_start, czas_stop, status, opis) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($reservations as $res) {
            $stmt->bind_param("iissss", $res[0], $res[1], $res[2], $res[3], $res[4], $res[5]);
            $stmt->execute();
        }
        
        echo "<p style='color: green;'>✓ Dodano " . count($reservations) . " przykładowych rezerwacji</p>";
    }
}

echo "<h3>Podsumowanie:</h3>";
echo "<ul>";

// Status użytkowników
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$user_count = $result->fetch_assoc()['count'];
echo "<li>Użytkownicy: $user_count</li>";

// Status sal
$result = $conn->query("SELECT COUNT(*) as count FROM rooms");
$room_count = $result->fetch_assoc()['count'];
echo "<li>Sale: $room_count</li>";

// Status rezerwacji
$result = $conn->query("SELECT COUNT(*) as count FROM reservations");
$reservation_count = $result->fetch_assoc()['count'];
echo "<li>Rezerwacje: $reservation_count</li>";

// Status powiadomień
$result = $conn->query("SELECT COUNT(*) as count FROM notifications");
$notification_count = $result->fetch_assoc()['count'];
echo "<li>Powiadomienia: $notification_count</li>";

echo "</ul>";

echo "<p style='color: green; font-weight: bold;'>🎉 Baza danych jest gotowa do użycia!</p>";
echo "<p><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Przejdź do logowania</a></p>";

echo "<h3>Konta testowe:</h3>";
echo "<ul>";
echo "<li><strong>Administrator:</strong> karol123@gmail.com (rola: Menedzer)</li>";
echo "<li><strong>Użytkownik:</strong> pawel123@gmail.com / hasło: 321</li>";
echo "<li><strong>Użytkownik:</strong> michal123@gmail.com / hasło: 231</li>";
echo "<li><strong>Użytkownik:</strong> bartek123@gmail.com / hasło: 132</li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h2, h3 { color: #333; }
p { margin: 10px 0; }
ul { margin: 10px 0; }
</style>
