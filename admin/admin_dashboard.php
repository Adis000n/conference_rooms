<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Menedzer') {
    header("Location: ../index.php");
    exit();
}

$rooms_query = "SELECT * FROM rooms";
$rooms_result = $conn->query($rooms_query);

$reservations_query = "SELECT r.*, u.`e-mail` as user_email, rm.nazwa as room_name 
                      FROM reservations r 
                      JOIN users u ON r.id_uzytkownika = u.id 
                      JOIN rooms rm ON r.id_sali = rm.id 
                      ORDER BY r.czas_start DESC";
$reservations_result = $conn->query($reservations_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Panel Administracyjny</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Sale konferencyjne</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Strona główna</a>
                <a class="nav-link" href="../logout.php">Wyloguj</a>
            </div>
        </div>
    </nav>    <div class="container">
        <h2 class="mb-4">Panel Administracyjny</h2>
        
        <!-- Szybkie statystyki -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Łączne sale</h5>
                        <h3 class="text-primary"><?php echo $rooms_result->num_rows; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Rezerwacje</h5>
                        <h3 class="text-success"><?php echo $reservations_result->num_rows; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Oczekujące</h5>
                        <?php
                        $pending_query = "SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'";
                        $pending_result = $conn->query($pending_query);
                        $pending_count = $pending_result->fetch_assoc()['count'];
                        ?>
                        <h3 class="text-warning"><?php echo $pending_count; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Użytkownicy</h5>
                        <?php
                        $users_query = "SELECT COUNT(*) as count FROM users";
                        $users_result = $conn->query($users_query);
                        $users_count = $users_result->fetch_assoc()['count'];
                        ?>
                        <h3 class="text-info"><?php echo $users_count; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu administracyjne -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-door-open fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Zarządzanie Salami</h5>
                        <p class="card-text">Dodawaj, edytuj i usuwaj sale konferencyjne</p>
                        <a href="rooms.php" class="btn btn-primary">Przejdź</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Zarządzanie Rezerwacjami</h5>
                        <p class="card-text">Przeglądaj, zatwierdź lub odrzuć rezerwacje</p>
                        <a href="reservations.php" class="btn btn-success">Przejdź</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Zarządzanie Użytkownikami</h5>
                        <p class="card-text">Dodawaj, edytuj użytkowników i przydzielaj role</p>
                        <a href="users.php" class="btn btn-info">Przejdź</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-bar fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Raporty i Statystyki</h5>
                        <p class="card-text">Generuj raporty o wykorzystaniu sal</p>
                        <a href="reports.php" class="btn btn-warning">Przejdź</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-bell fa-3x text-danger mb-3"></i>
                        <h5 class="card-title">Powiadomienia</h5>
                        <p class="card-text">Przeglądaj powiadomienia systemu</p>
                        <a href="../notifications.php" class="btn btn-danger">Przejdź</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sale konferencyjne -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Zarządzanie salami</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    <i class="fas fa-plus"></i> Dodaj salę
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nazwa</th>
                                <th>Pojemność</th>
                                <th>Wyposażenie</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($room = $rooms_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['nazwa']); ?></td>
                                <td><?php echo $room['pojemnosc']; ?></td>
                                <td><?php echo htmlspecialchars($room['wyposazenie']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning edit-room" data-room-id="<?php echo $room['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-room" data-room-id="<?php echo $room['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reservations Overview -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Przegląd rezerwacji</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sala</th>
                                <th>Użytkownik</th>
                                <th>Od</th>
                                <th>Do</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($reservation = $reservations_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['room_name']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['user_email']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($reservation['czas_start'])); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($reservation['czas_stop'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger delete-reservation" 
                                            data-reservation-id="<?php echo $reservation['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dodaj nową salę</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addRoomForm">
                        <div class="mb-3">
                            <label for="roomName" class="form-label">Nazwa sali</label>
                            <input type="text" class="form-control" id="roomName" required>
                        </div>
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Pojemność</label>
                            <input type="number" class="form-control" id="capacity" required>
                        </div>
                        <div class="mb-3">
                            <label for="equipment" class="form-label">Wyposażenie</label>
                            <textarea class="form-control" id="equipment" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Dodaj</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin.js"></script>
</body>
</html>
