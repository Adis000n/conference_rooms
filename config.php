<?php
// Konfiguracja środowiska deweloperskiego
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Włącz logowanie błędów
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Funkcje pomocnicze do debugowania
function debug_log($message, $data = null) {
    $log_entry = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log_entry .= " - " . print_r($data, true);
    }
    error_log($log_entry);
}

function json_response($data, $http_code = 200) {
    http_response_code($http_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

function validate_session() {
    if (!isset($_SESSION['user_id'])) {
        json_response(['error' => 'Nie jesteś zalogowany'], 401);
    }
}

function validate_admin() {
    validate_session();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Menedzer') {
        json_response(['error' => 'Brak uprawnień administratora'], 403);
    }
}

// Test połączenia z bazą
function test_database() {
    try {
        require_once 'db_connect.php';
        
        // Test podstawowy
        $result = $conn->query("SELECT 1");
        if (!$result) {
            throw new Exception("Podstawowy test zapytania nie powiódł się");
        }
        
        // Test tabel
        $tables = ['users', 'rooms', 'reservations', 'notifications'];
        foreach ($tables as $table) {
            $result = $conn->query("SELECT COUNT(*) FROM $table");
            if (!$result) {
                throw new Exception("Tabela $table nie istnieje lub jest niedostępna");
            }
        }
        
        return ['status' => 'success', 'message' => 'Baza danych działa poprawnie'];
        
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// Jeśli plik jest wywoływany bezpośrednio, uruchom testy
if (basename($_SERVER['PHP_SELF']) === 'config.php') {
    header('Content-Type: application/json');
    echo json_encode([
        'php_version' => PHP_VERSION,
        'server_time' => date('Y-m-d H:i:s'),
        'database_test' => test_database(),
        'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive',
        'extensions' => [
            'mysqli' => extension_loaded('mysqli'),
            'json' => extension_loaded('json'),
            'mbstring' => extension_loaded('mbstring')
        ]
    ], JSON_PRETTY_PRINT);
}
?>
