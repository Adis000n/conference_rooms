<?php
session_start();
require_once('../db_connect.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Menedzer') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $imie = filter_var($_POST['imie'], FILTER_SANITIZE_STRING);
                $nazwisko = filter_var($_POST['nazwisko'], FILTER_SANITIZE_STRING);
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                $rola = filter_var($_POST['rola'], FILTER_SANITIZE_STRING);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                // Sprawdź czy email już istnieje
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE `e-mail` = ?");
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows === 0) {
                    $stmt = $conn->prepare("INSERT INTO users (imie, nazwisko, `e-mail`, haslo, rola) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $imie, $nazwisko, $email, $password, $rola);
                    
                    if ($stmt->execute()) {
                        $message = "Użytkownik został dodany pomyślnie";
                    } else {
                        $message = "Błąd podczas dodawania użytkownika";
                    }
                } else {
                    $message = "Użytkownik z tym adresem email już istnieje";
                }
                break;
                
            case 'edit':
                $user_id = (int)$_POST['user_id'];
                $imie = filter_var($_POST['imie'], FILTER_SANITIZE_STRING);
                $nazwisko = filter_var($_POST['nazwisko'], FILTER_SANITIZE_STRING);
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                $rola = filter_var($_POST['rola'], FILTER_SANITIZE_STRING);
                
                $stmt = $conn->prepare("UPDATE users SET imie = ?, nazwisko = ?, `e-mail` = ?, rola = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $imie, $nazwisko, $email, $rola, $user_id);
                
                if ($stmt->execute()) {
                    $message = "Dane użytkownika zostały zaktualizowane";
                } else {
                    $message = "Błąd podczas aktualizacji danych użytkownika";
                }
                break;
                
            case 'delete':
                $user_id = (int)$_POST['user_id'];
                
                // Nie można usunąć siebie
                if ($user_id != $_SESSION['user_id']) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    
                    if ($stmt->execute()) {
                        $message = "Użytkownik został usunięty";
                    } else {
                        $message = "Błąd podczas usuwania użytkownika";
                    }
                } else {
                    $message = "Nie możesz usunąć swojego konta";
                }
                break;
                
            case 'reset_password':
                $user_id = (int)$_POST['user_id'];
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE users SET haslo = ? WHERE id = ?");
                $stmt->bind_param("si", $new_password, $user_id);
                
                if ($stmt->execute()) {
                    $message = "Hasło zostało zresetowane";
                } else {
                    $message = "Błąd podczas resetowania hasła";
                }
                break;
        }
    }
}

// Get users list with statistics
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM reservations r WHERE r.id_uzytkownika = u.id) as reservation_count,
          (SELECT COUNT(*) FROM reservations r WHERE r.id_uzytkownika = u.id AND r.status = 'approved') as approved_reservations
          FROM users u 
          ORDER BY u.id DESC";
$result = $conn->query($query);

// Get available roles
$roles = ['Gość', 'Menedzer', 'Administrator'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Zarządzanie Użytkownikami</title>
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
        <h2 class="mb-4">Zarządzanie Użytkownikami</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lista Użytkowników</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus"></i> Dodaj Użytkownika
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imię i Nazwisko</th>
                                <th>Email</th>
                                <th>Rola</th>
                                <th>Rezerwacje</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['imie'] . ' ' . $user['nazwisko']); ?></td>
                                    <td><?php echo htmlspecialchars($user['e-mail']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['rola'] === 'Menedzer' ? 'primary' : 'secondary'; ?>">
                                            <?php echo htmlspecialchars($user['rola']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            Łącznie: <?php echo $user['reservation_count']; ?><br>
                                            Zatwierdzone: <?php echo $user['approved_reservations']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editUserModal"
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#resetPasswordModal"
                                                    onclick="setResetUserId(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-outline-danger"
                                                            onclick="return confirm('Czy na pewno chcesz usunąć tego użytkownika? Ta akcja jest nieodwracalna.')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
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

    <!-- Modal dodawania użytkownika -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dodaj Użytkownika</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="imie" class="form-label">Imię</label>
                            <input type="text" class="form-control" name="imie" required>
                        </div>
                        <div class="mb-3">
                            <label for="nazwisko" class="form-label">Nazwisko</label>
                            <input type="text" class="form-control" name="nazwisko" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Hasło</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="rola" class="form-label">Rola</label>
                            <select class="form-control" name="rola" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role; ?>"><?php echo $role; ?></option>
                                <?php endforeach; ?>
                            </select>
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

    <!-- Modal edycji użytkownika -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edytuj Użytkownika</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label for="edit_imie" class="form-label">Imię</label>
                            <input type="text" class="form-control" name="imie" id="edit_imie" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_nazwisko" class="form-label">Nazwisko</label>
                            <input type="text" class="form-control" name="nazwisko" id="edit_nazwisko" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_rola" class="form-label">Rola</label>
                            <select class="form-control" name="rola" id="edit_rola" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role; ?>"><?php echo $role; ?></option>
                                <?php endforeach; ?>
                            </select>
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

    <!-- Modal resetowania hasła -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Resetuj Hasło</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" id="reset_user_id">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nowe hasło</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-warning">Resetuj Hasło</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_imie').value = user.imie;
            document.getElementById('edit_nazwisko').value = user.nazwisko;
            document.getElementById('edit_email').value = user['e-mail'];
            document.getElementById('edit_rola').value = user.rola;
        }
        
        function setResetUserId(userId) {
            document.getElementById('reset_user_id').value = userId;
        }
    </script>
</body>
</html>
                </tr>
                <?php while ($user = $result->fetch_assoc()): ?>                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo $user['imie'] . ' ' . $user['nazwisko']; ?></td>
                    <td><?php echo $user['e-mail']; ?></td>
                    <td><?php echo $user['rola']; ?></td>
                    <td>
                        <button onclick="editUser(<?php echo $user['id']; ?>)">Edytuj</button>
                        <button onclick="deleteUser(<?php echo $user['id']; ?>)">Usuń</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>
