<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch current user data
$stmt = $conn->prepare("SELECT imie, nazwisko, `e-mail` FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $imie = filter_var($_POST['imie'], FILTER_SANITIZE_STRING);
    $nazwisko = filter_var($_POST['nazwisko'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
      $stmt = $conn->prepare("UPDATE users SET imie = ?, nazwisko = ?, `e-mail` = ? WHERE id = ?");
    $stmt->bind_param("sssi", $imie, $nazwisko, $email, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success_message = "Dane zostały zaktualizowane";
        $user['imie'] = $imie;
        $user['nazwisko'] = $nazwisko;
        $user['e-mail'] = $email;
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
      $stmt = $conn->prepare("SELECT haslo FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $current_pass = $stmt->get_result()->fetch_assoc()['haslo'];
    
    if (password_verify($old_password, $current_pass)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET haslo = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $password_message = "Hasło zostało zmienione";
        }
    } else {
        $password_error = "Nieprawidłowe obecne hasło";
    }
}

// Fetch user reservations
$reservations_query = "SELECT r.*, rm.nazwa as room_name 
                      FROM reservations r 
                      JOIN rooms rm ON r.id_sali = rm.id 
                      WHERE r.id_uzytkownika = ? 
                      ORDER BY r.czas_start DESC";
$stmt = $conn->prepare($reservations_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$reservations_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <a class="nav-link active" href="profile.php">Mój profil</a>
                <a class="nav-link" href="notifications.php">Powiadomienia</a>
                <a class="nav-link" href="calendar.php">Kalendarz</a>
                <a class="nav-link" href="templates.php">Szablony</a>
                <a class="nav-link" href="reservations.php">Moje rezerwacje</a>
                <a class="nav-link" href="logout.php">Wyloguj</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h1 class="card-title text-center mb-4">Profil</h1>
                        <?php if (isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>
                        
                        <h2 class="h4 mb-3">Aktualizacja danych</h2>
                        <form method="POST" class="mb-4">                            <div class="mb-3">
                                <input type="text" class="form-control" name="imie" value="<?php echo htmlspecialchars($user['imie']); ?>" placeholder="Imię" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control" name="nazwisko" value="<?php echo htmlspecialchars($user['nazwisko']); ?>" placeholder="Nazwisko" required>
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['e-mail']); ?>" placeholder="Email" required>
                            </div>
                            <button type="submit" class="btn btn-tropical" name="update_profile">Aktualizuj dane</button>
                        </form>

                        <h2 class="h4 mb-3">Zmiana hasła</h2>
                        <?php 
                        if (isset($password_message)) echo "<div class='alert alert-success'>$password_message</div>";
                        if (isset($password_error)) echo "<div class='alert alert-danger'>$password_error</div>";
                        ?>
                        <form method="POST">
                            <div class="mb-3">
                                <input type="password" class="form-control" name="old_password" placeholder="Obecne hasło" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" name="new_password" placeholder="Nowe hasło" required>
                            </div>                            <button type="submit" class="btn btn-tropical" name="change_password">Zmień hasło</button>
                        </form>
                    </div>
                </div>

                <!-- Moje rezerwacje -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Moje Rezerwacje</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($reservations_result->num_rows === 0): ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                <p>Nie masz jeszcze żadnych rezerwacji</p>
                                <a href="index.php" class="btn btn-primary">Zarezerwuj salę</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Sala</th>
                                            <th>Data rozpoczęcia</th>
                                            <th>Data zakończenia</th>
                                            <th>Status</th>
                                            <th>Opis</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
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
                                        
                                        while ($reservation = $reservations_result->fetch_assoc()): 
                                        ?>
                                            <tr>
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
                                                        <span class="text-truncate" style="max-width: 200px; display: inline-block;" 
                                                              title="<?php echo htmlspecialchars($reservation['opis']); ?>">
                                                            <?php echo htmlspecialchars($reservation['opis']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Brak opisu</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>