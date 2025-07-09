<!DOCTYPE html>
<html>
<head>
    <title>Test System</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>System Rezerwacji Sal Konferencyjnych - Test</h1>
    
    <div class="test-section">
        <h2>Test Połączenia z Bazą Danych</h2>
        <?php
        try {
            require_once 'db_connect.php';
            echo '<p class="success">✓ Połączenie z bazą danych działa!</p>';
            
            // Test query
            $result = $conn->query("SELECT COUNT(*) as count FROM users");
            if ($result) {
                $row = $result->fetch_assoc();
                echo '<p class="info">Liczba użytkowników w bazie: ' . $row['count'] . '</p>';
            }
            
            // Test rooms
            $result = $conn->query("SELECT COUNT(*) as count FROM rooms");
            if ($result) {
                $row = $result->fetch_assoc();
                echo '<p class="info">Liczba sal w bazie: ' . $row['count'] . '</p>';
                
                if ($row['count'] == 0) {
                    echo '<p class="error">⚠ Brak sal w bazie danych. Dodaj przykładowe dane!</p>';
                    echo '<p>Uruchom: <code>mysql -u root system_rezerwacji_sal_konferencyjnych < sample_data.sql</code></p>';
                }
            }
            
        } catch (Exception $e) {
            echo '<p class="error">✗ Błąd połączenia: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Test API Endpoints</h2>
        <p><a href="check_reservation.php?room_id=1" target="_blank">Test check_reservation.php?room_id=1</a></p>
        <p><a href="check_rooms_status.php" target="_blank">Test check_rooms_status.php</a></p>
    </div>
    
    <div class="test-section">
        <h2>Linki do Aplikacji</h2>
        <p><a href="index.php">Strona główna (wymaga logowania)</a></p>
        <p><a href="login.php">Logowanie</a></p>
        <p><a href="register.php">Rejestracja</a></p>
    </div>
    
    <div class="test-section">
        <h2>Przykładowe Konta Testowe</h2>
        <ul>
            <li><strong>Administrator:</strong> karol123@gmail.com (hasło: sprawdź w bazie)</li>
            <li><strong>Użytkownik:</strong> pawel123@gmail.com / hasło: 321</li>
            <li><strong>Użytkownik:</strong> michal123@gmail.com / hasło: 231</li>
        </ul>
    </div>

    <div class="test-section">
        <h2>Instrukcje Uruchomienia</h2>
        <ol>
            <li>Upewnij się, że XAMPP jest uruchomiony (Apache + MySQL)</li>
            <li>Zaimportuj bazę danych: <code>system_rezerwacji_sal_konferencyjnych.sql</code></li>
            <li>Opcjonalnie dodaj przykładowe dane: <code>sample_data.sql</code></li>
            <li>Otwórz <a href="login.php">stronę logowania</a></li>
        </ol>
    </div>
</body>
</html>
