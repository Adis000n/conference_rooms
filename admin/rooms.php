<?php
session_start();
require_once('../db_connect.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Menedzer') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Handle room actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nazwa = filter_var($_POST['nazwa'], FILTER_SANITIZE_STRING);
                $pojemnosc = (int)$_POST['pojemnosc'];
                $wyposazenie = filter_var($_POST['wyposazenie'], FILTER_SANITIZE_STRING);
                $dostepnosc = isset($_POST['dostepnosc']) ? 1 : 0;
                
                $stmt = $conn->prepare("INSERT INTO rooms (nazwa, pojemnosc, wyposazenie, dostepnosc) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sisi", $nazwa, $pojemnosc, $wyposazenie, $dostepnosc);
                
                if ($stmt->execute()) {
                    $message = "Sala została dodana pomyślnie";
                } else {
                    $message = "Błąd podczas dodawania sali";
                }
                break;
                
            case 'edit':
                $room_id = (int)$_POST['room_id'];
                $nazwa = filter_var($_POST['nazwa'], FILTER_SANITIZE_STRING);
                $pojemnosc = (int)$_POST['pojemnosc'];
                $wyposazenie = filter_var($_POST['wyposazenie'], FILTER_SANITIZE_STRING);
                $dostepnosc = isset($_POST['dostepnosc']) ? 1 : 0;
                
                $stmt = $conn->prepare("UPDATE rooms SET nazwa = ?, pojemnosc = ?, wyposazenie = ?, dostepnosc = ? WHERE id = ?");
                $stmt->bind_param("sisii", $nazwa, $pojemnosc, $wyposazenie, $dostepnosc, $room_id);
                
                if ($stmt->execute()) {
                    $message = "Dane sali zostały zaktualizowane";
                } else {
                    $message = "Błąd podczas aktualizacji danych sali";
                }
                break;
                
            case 'delete':
                $room_id = (int)$_POST['room_id'];
                
                // Sprawdź czy sala ma aktywne rezerwacje
                $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM reservations WHERE id_sali = ? AND status = 'approved' AND czas_stop > NOW()");
                $check_stmt->bind_param("i", $room_id);
                $check_stmt->execute();
                $active_reservations = $check_stmt->get_result()->fetch_assoc()['count'];
                
                if ($active_reservations > 0) {
                    $message = "Nie można usunąć sali z aktywnymi rezerwacjami";
                } else {
                    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
                    $stmt->bind_param("i", $room_id);
                    
                    if ($stmt->execute()) {
                        $message = "Sala została usunięta";
                    } else {
                        $message = "Błąd podczas usuwania sali";
                    }
                }
                break;
                
            case 'toggle_availability':
                $room_id = (int)$_POST['room_id'];
                
                $stmt = $conn->prepare("UPDATE rooms SET dostepnosc = NOT dostepnosc WHERE id = ?");
                $stmt->bind_param("i", $room_id);
                
                if ($stmt->execute()) {
                    $message = "Status dostępności sali został zmieniony";
                } else {
                    $message = "Błąd podczas zmiany statusu dostępności";
                }
                break;
        }
    }
}

// Get rooms list with statistics
$query = "SELECT r.*, 
          (SELECT COUNT(*) FROM reservations res WHERE res.id_sali = r.id) as total_reservations,
          (SELECT COUNT(*) FROM reservations res WHERE res.id_sali = r.id AND res.status = 'approved' AND res.czas_stop > NOW()) as active_reservations,
          COALESCE(r.srednia_ocena, 0) as srednia_ocena,
          COALESCE(r.liczba_ocen, 0) as liczba_ocen
          FROM rooms r 
          ORDER BY r.id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Zarządzanie Salami</title>
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
        <h2 class="mb-4">Zarządzanie Salami Konferencyjnymi</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lista Sal</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    <i class="fas fa-plus"></i> Dodaj Salę
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nazwa</th>
                                <th>Pojemność</th>
                                <th>Wyposażenie</th>
                                <th>Status</th>
                                <th>Ocena</th>
                                <th>Rezerwacje</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($room = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $room['id']; ?></td>
                                    <td><?php echo htmlspecialchars($room['nazwa']); ?></td>
                                    <td><?php echo $room['pojemnosc']; ?> osób</td>
                                    <td>
                                        <span class="text-truncate" style="max-width: 200px; display: inline-block;" 
                                              title="<?php echo htmlspecialchars($room['wyposazenie']); ?>">
                                            <?php echo htmlspecialchars($room['wyposazenie']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo (isset($room['dostepnosc']) && $room['dostepnosc']) ? 'success' : 'danger'; ?>">
                                            <?php echo (isset($room['dostepnosc']) && $room['dostepnosc']) ? 'Dostępna' : 'Niedostępna'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($room['liczba_ocen'] > 0): ?>
                                            <div class="text-center">
                                                <div class="stars mb-1">
                                                    <?php 
                                                    $avgRating = $room['srednia_ocena'];
                                                    for ($i = 1; $i <= 5; $i++): 
                                                        if ($i <= $avgRating) {
                                                            echo '<i class="fas fa-star text-warning"></i>';
                                                        } elseif ($i - 0.5 <= $avgRating) {
                                                            echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star text-warning"></i>';
                                                        }
                                                    endfor; 
                                                    ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo number_format($avgRating, 1); ?> 
                                                    (<?php echo $room['liczba_ocen']; ?> ocen)
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <small class="text-muted">Brak ocen</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            Łącznie: <?php echo $room['total_reservations']; ?><br>
                                            Aktywne: <span class="<?php echo $room['active_reservations'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo $room['active_reservations']; ?>
                                            </span>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editRoomModal"
                                                    onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                                <input type="hidden" name="action" value="toggle_availability">
                                                <button type="submit" 
                                                        class="btn btn-outline-<?php echo (isset($room['dostepnosc']) && $room['dostepnosc']) ? 'warning' : 'success'; ?>"
                                                        title="<?php echo (isset($room['dostepnosc']) && $room['dostepnosc']) ? 'Wyłącz dostępność' : 'Włącz dostępność'; ?>">
                                                    <i class="fas fa-<?php echo (isset($room['dostepnosc']) && $room['dostepnosc']) ? 'eye-slash' : 'eye'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-outline-danger"
                                                        onclick="return confirm('Czy na pewno chcesz usunąć tę salę? Ta akcja jest nieodwracalna.')">
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

    <!-- Modal dodawania sali -->
    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dodaj Salę</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="nazwa" class="form-label">Nazwa sali</label>
                            <input type="text" class="form-control" name="nazwa" required>
                        </div>
                        <div class="mb-3">
                            <label for="pojemnosc" class="form-label">Pojemność (liczba osób)</label>
                            <input type="number" class="form-control" name="pojemnosc" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="wyposazenie" class="form-label">Wyposażenie</label>
                            <textarea class="form-control" name="wyposazenie" rows="3" 
                                      placeholder="Opisz wyposażenie sali (projektor, ekran, klimatyzacja, itp.)"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="dostepnosc" id="dostepnosc" checked>
                                <label class="form-check-label" for="dostepnosc">
                                    Sala dostępna do rezerwacji
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Dodaj</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal edycji sali -->
    <div class="modal fade" id="editRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edytuj Salę</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="room_id" id="edit_room_id">
                        <div class="mb-3">
                            <label for="edit_nazwa" class="form-label">Nazwa sali</label>
                            <input type="text" class="form-control" name="nazwa" id="edit_nazwa" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_pojemnosc" class="form-label">Pojemność (liczba osób)</label>
                            <input type="number" class="form-control" name="pojemnosc" id="edit_pojemnosc" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_wyposazenie" class="form-label">Wyposażenie</label>
                            <textarea class="form-control" name="wyposazenie" id="edit_wyposazenie" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="dostepnosc" id="edit_dostepnosc">
                                <label class="form-check-label" for="edit_dostepnosc">
                                    Sala dostępna do rezerwacji
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Zapisz</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editRoom(room) {
            document.getElementById('edit_room_id').value = room.id;
            document.getElementById('edit_nazwa').value = room.nazwa;
            document.getElementById('edit_pojemnosc').value = room.pojemnosc;
            document.getElementById('edit_wyposazenie').value = room.wyposazenie;
            document.getElementById('edit_dostepnosc').checked = room.dostepnosc == 1;
        }
    </script>
</body>
</html>

