<?php
session_start();
require_once 'db_connect.php';

if (isset($_POST['register'])) {
    $imie = filter_var($_POST['imie'], FILTER_SANITIZE_STRING);
    $nazwisko = filter_var($_POST['nazwisko'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
      $stmt = $conn->prepare("SELECT id FROM users WHERE `e-mail` = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO users (imie, nazwisko, `e-mail`, haslo, rola) VALUES (?, ?, ?, ?, 'Gość')");
        $stmt->bind_param("ssss", $imie, $nazwisko, $email, $password);
        
        if ($stmt->execute()) {
            header("Location: login.php");
            exit();
        }
    } else {
        $error = "Email już istnieje w bazie";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rejestracja</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4">
                    <h2 class="text-center mb-4">Rejestracja</h2>
                    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <input type="text" class="form-control" name="imie" placeholder="Imię" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" name="nazwisko" placeholder="Nazwisko" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="Email" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" name="password" placeholder="Hasło" required>
                        </div>
                        <button type="submit" name="register" class="btn btn-tropical w-100">Zarejestruj</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">Logowanie</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
