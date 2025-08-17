<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obsługuj oznaczanie jako przeczytane
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    $stmt = $conn->prepare("UPDATE notifications SET przeczytane = 1 WHERE id = ? AND id_uzytkownika = ?");
    $stmt->bind_param("ii", $notification_id, $_SESSION['user_id']);
    $stmt->execute();
}

// Obsługuj oznaczanie wszystkich jako przeczytane
if (isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET przeczytane = 1 WHERE id_uzytkownika = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
}

// Pobierz powiadomienia użytkownika
$query = "SELECT * FROM notifications 
          WHERE id_uzytkownika = ? 
          ORDER BY data_utworzenia DESC, przeczytane ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result();

// Pobierz liczbę nieprzeczytanych
$unread_query = "SELECT COUNT(*) as unread_count FROM notifications 
                 WHERE id_uzytkownika = ? AND przeczytane = 0";
$stmt = $conn->prepare($unread_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['unread_count'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Powiadomienia</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">Sale konferencyjne</a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Menedzer'): ?>
                    <a href="admin/admin_dashboard.php" class="nav-link">Panel Administracyjny</a>
                <?php endif; ?>
                <a class="nav-link" href="index.php">Strona główna</a>
                <a class="nav-link" href="profile.php">Mój profil</a>
                <a class="nav-link active" href="notifications.php">
                    Powiadomienia 
                    <?php if ($unread_count > 0): ?>
                        <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="calendar.php">Kalendarz</a>
                <a class="nav-link" href="templates.php">Szablony</a>
                <a class="nav-link" href="reservations.php">Moje rezerwacje</a>
                <a class="nav-link" href="logout.php">Wyloguj</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-bell"></i> Powiadomienia
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger ms-2"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </h5>
                        <?php if ($unread_count > 0): ?>
                            <form method="POST" class="d-inline">
                                <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-check-double"></i> Oznacz wszystkie jako przeczytane
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($notifications->num_rows === 0): ?>
                            <div class="text-center p-4">
                                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Brak powiadomień</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php while ($notification = $notifications->fetch_assoc()): ?>
                                    <div class="list-group-item <?php echo !$notification['przeczytane'] ? 'list-group-item-warning' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <?php
                                                    $icon_class = 'fas fa-info-circle text-info';
                                                    switch ($notification['typ_powiadomienia']) {
                                                        case 'new_reservation':
                                                            $icon_class = 'fas fa-calendar-plus text-primary';
                                                            break;
                                                        case 'reservation_approved':
                                                            $icon_class = 'fas fa-check-circle text-success';
                                                            break;
                                                        case 'reservation_rejected':
                                                            $icon_class = 'fas fa-times-circle text-danger';
                                                            break;
                                                        case 'reservation_cancelled':
                                                            $icon_class = 'fas fa-ban text-warning';
                                                            break;
                                                    }
                                                    ?>
                                                    <i class="<?php echo $icon_class; ?> me-2"></i>
                                                    <?php if (!$notification['przeczytane']): ?>
                                                        <span class="badge bg-primary me-2">NOWE</span>
                                                    <?php endif; ?>
                                                    <small class="text-muted">
                                                        <?php echo date('d.m.Y H:i', strtotime($notification['data_utworzenia'])); ?>
                                                    </small>
                                                </div>
                                                <p class="mb-0"><?php echo htmlspecialchars($notification['wiadomosc']); ?></p>
                                            </div>
                                            <?php if (!$notification['przeczytane']): ?>
                                                <form method="POST" class="ms-2">
                                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                    <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success" 
                                                            title="Oznacz jako przeczytane">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Powrót do strony głównej
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>