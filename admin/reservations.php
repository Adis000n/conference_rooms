<?php
session_start();
require_once('../db_connect.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Menedzer') {
    header('Location: ../login.php');
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['reservation_id'])) {
        $reservation_id = (int)$_POST['reservation_id'];
        $action = $_POST['action'];
        
        switch ($action) {
            case 'approve':
                $stmt = $conn->prepare("UPDATE reservations SET status = 'approved' WHERE id = ?");
                $stmt->bind_param("i", $reservation_id);
                if ($stmt->execute()) {
                    $message = "Rezerwacja została zatwierdzona";
                    
                    // Dodaj powiadomienie dla użytkownika
                    $notify_query = "INSERT INTO notifications (id_uzytkownika, wiadomosc, typ_powiadomienia) 
                                    SELECT id_uzytkownika, 'Twoja rezerwacja została zatwierdzona', 'reservation_approved' 
                                    FROM reservations WHERE id = ?";
                    $stmt = $conn->prepare($notify_query);
                    $stmt->bind_param("i", $reservation_id);
                    $stmt->execute();
                }
                break;
                
            case 'reject':
                $stmt = $conn->prepare("UPDATE reservations SET status = 'rejected' WHERE id = ?");
                $stmt->bind_param("i", $reservation_id);
                if ($stmt->execute()) {
                    $message = "Rezerwacja została odrzucona";
                    
                    // Dodaj powiadomienie dla użytkownika
                    $notify_query = "INSERT INTO notifications (id_uzytkownika, wiadomosc, typ_powiadomienia) 
                                    SELECT id_uzytkownika, 'Twoja rezerwacja została odrzucona', 'reservation_rejected' 
                                    FROM reservations WHERE id = ?";
                    $stmt = $conn->prepare($notify_query);
                    $stmt->bind_param("i", $reservation_id);
                    $stmt->execute();
                }
                break;
                
            case 'cancel':
                $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
                $stmt->bind_param("i", $reservation_id);
                if ($stmt->execute()) {
                    $message = "Rezerwacja została anulowana";
                }
                break;
                
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
                $stmt->bind_param("i", $reservation_id);
                if ($stmt->execute()) {
                    $message = "Rezerwacja została usunięta";
                }
                break;
        }
    }
}

$query = "SELECT r.*, u.`e-mail`, u.imie, u.nazwisko, rm.nazwa as room_name 
          FROM reservations r 
          JOIN users u ON r.id_uzytkownika = u.id 
          JOIN rooms rm ON r.id_sali = rm.id 
          ORDER BY r.czas_start DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Zarządzanie Rezerwacjami</title>
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
                <a class="nav-link" href="admin_dashboard.php">Panel Admin</a>
                <a class="nav-link" href="reports.php">Raporty</a>
                <a class="nav-link" href="../index.php">Strona główna</a>
                <a class="nav-link" href="../logout.php">Wyloguj</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2 class="mb-4">Zarządzanie Rezerwacjami</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Użytkownik</th>
                                <th>Sala</th>
                                <th>Data rozpoczęcia</th>
                                <th>Data zakończenia</th>
                                <th>Status</th>
                                <th>Opis</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($reservation = $result->fetch_assoc()): 
                                $status_class = [
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'cancelled' => 'secondary'
                                ];
                                $status_text = [
                                    'pending' => 'Oczekuje',
                                    'approved' => 'Zatwierdzona',
                                    'rejected' => 'Odrzucona',
                                    'cancelled' => 'Anulowana'
                                ];
                            ?>
                                <tr>
                                    <td><?php echo $reservation['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($reservation['imie'] . ' ' . $reservation['nazwisko']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($reservation['e-mail']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($reservation['room_name']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($reservation['czas_start'])); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($reservation['czas_stop'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_class[$reservation['status']] ?? 'secondary'; ?>">
                                            <?php echo $status_text[$reservation['status']] ?? 'Nieznany'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($reservation['opis'])): ?>
                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" 
                                                    title="<?php echo htmlspecialchars($reservation['opis']); ?>">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($reservation['status'] === 'pending'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success btn-sm" 
                                                            onclick="return confirm('Czy na pewno chcesz zatwierdzić tę rezerwację?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Czy na pewno chcesz odrzucić tę rezerwację?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($reservation['status'] === 'approved'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <button type="submit" class="btn btn-warning btn-sm"
                                                            onclick="return confirm('Czy na pewno chcesz anulować tę rezerwację?')">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                                        onclick="return confirm('Czy na pewno chcesz usunąć tę rezerwację? Ta akcja jest nieodwracalna.')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicjalizuj tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>
                <?php while ($reservation = $result->fetch_assoc()): ?>                <tr>
                    <td><?php echo $reservation['id']; ?></td>
                    <td><?php echo $reservation['e-mail']; ?></td>
                    <td><?php echo $reservation['room_name']; ?></td>
                    <td><?php echo $reservation['czas_start']; ?></td>
                    <td>Aktywna</td>
                    <td>                        <?php if (true): // Changed from status check since we don't have status field ?>
                            <button onclick="approveReservation(<?php echo $reservation['id']; ?>)">Zatwierdź</button>
                            <button onclick="rejectReservation(<?php echo $reservation['id']; ?>)">Odrzuć</button>
                        <?php endif; ?>
                        <button onclick="cancelReservation(<?php echo $reservation['id']; ?>)">Usuń</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>
