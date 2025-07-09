<?php
session_start();
require_once 'db_connect.php';

if (isset($_POST['login'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
      $stmt = $conn->prepare("SELECT id, haslo, rola FROM users WHERE `e-mail` = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['haslo']) || $password == $row['haslo']) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_role'] = $row['rola'];
            header("Location: index.php");
            exit();
        }
    }
    $error = "Nieprawidłowy email lub hasło";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Logowanie</title>
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
                    <h2 class="text-center mb-4">Logowanie</h2>
                    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="Email" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" class="form-control" name="password" placeholder="Hasło" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-tropical w-100">Zaloguj</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="register.php" class="text-decoration-none">Rejestracja</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
