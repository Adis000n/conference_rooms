<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$query = "SELECT r.*, 
    (SELECT COUNT(*) FROM reservations res 
WHERE res.id_sali = r.id 
AND NOW() BETWEEN res.czas_start AND res.czas_stop) as is_reserved
    FROM rooms r";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sale konferencyjne</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Sale konferencyjne</a>            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Menedzer'): ?>
                    <a href="admin/admin_dashboard.php" class="nav-link">Panel Administracyjny</a>
                <?php endif; ?>                <a class="nav-link" href="profile.php">Mój profil</a>
                <a class="nav-link" href="notifications.php">
                    Powiadomienia
                    <?php
                    // Pobierz liczbę nieprzeczytanych powiadomień
                    $unread_query = "SELECT COUNT(*) as count FROM notifications WHERE id_uzytkownika = ? AND przeczytane = 0";
                    $stmt = $conn->prepare($unread_query);
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $unread_count = $stmt->get_result()->fetch_assoc()['count'];
                    if ($unread_count > 0): ?>
                        <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="calendar.php">Kalendarz</a>
                <a class="nav-link" href="logout.php">Wyloguj</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row g-4">
            <?php while ($room = $result->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card h-100 room-card" data-room-id="<?php echo $room['id']; ?>">
                        <div class="card-body position-relative">
                            <div class="position-absolute top-0 end-0 mt-2 me-2">
                                <i class="fas fa-circle <?php echo $room['is_reserved'] ? 'text-danger' : 'text-success'; ?>"></i>
                            </div>
                            <h3 class="card-title"><?php echo htmlspecialchars($room['nazwa']); ?></h3>
                            <p class="card-text">Pojemność: <?php echo $room['pojemnosc']; ?> osób</p>
                            <p class="card-text">Wyposażenie: <?php echo htmlspecialchars($room['wyposazenie']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="modal fade" id="reservationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rezerwacja sali</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="reservationStatus"></div>                    <form id="reservationForm" class="d-none">
                        <div class="mb-3">
                            <label for="bookingDate" class="form-label">Data rezerwacji</label>
                            <input type="date" class="form-control" name="bookingDate" id="bookingDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="startTime" class="form-label">Godzina rozpoczęcia</label>
                            <input type="time" class="form-control" name="startTime" id="startTime" required>
                        </div>
                        <div class="mb-3">
                            <label for="endTime" class="form-label">Godzina zakończenia</label>
                            <input type="time" class="form-control" name="endTime" id="endTime" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Opis rezerwacji (opcjonalnie)</label>
                            <textarea class="form-control" name="description" id="description" rows="3" placeholder="Cel spotkania, liczba uczestników itp."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Zarezerwuj</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/reservations.js"></script>
    <script>
        function updateRoomStatuses() {
            fetch('check_rooms_status.php')
                .then(response => response.json())
                .then(statuses => {
                    Object.entries(statuses).forEach(([roomId, isReserved]) => {
                        const statusIcon = document.querySelector(`.room-card[data-room-id="${roomId}"] .fas.fa-circle`);
                        if (statusIcon) {
                            statusIcon.classList.remove('text-success', 'text-danger');
                            statusIcon.classList.add(isReserved ? 'text-danger' : 'text-success');
                        }
                    });
                });
        }

        setInterval(updateRoomStatuses, 1000);
    </script>
</body>
</html>
