<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Menedzer') {
    header("Location: ../index.php");
    exit();
}

// Pobierz statystyki
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');

// Statystyki ogólne
$total_reservations_query = "SELECT COUNT(*) as total FROM reservations WHERE DATE(czas_start) BETWEEN ? AND ?";
$stmt = $conn->prepare($total_reservations_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$total_reservations = $stmt->get_result()->fetch_assoc()['total'];

// Najpopularniejsze sale
$popular_rooms_query = "SELECT r.nazwa, COUNT(res.id) as reservation_count 
                       FROM rooms r 
                       LEFT JOIN reservations res ON r.id = res.id_sali 
                       WHERE DATE(res.czas_start) BETWEEN ? AND ?
                       GROUP BY r.id, r.nazwa 
                       ORDER BY reservation_count DESC";
$stmt = $conn->prepare($popular_rooms_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$popular_rooms = $stmt->get_result();

// Statystyki według statusu
$status_stats_query = "SELECT status, COUNT(*) as count 
                      FROM reservations 
                      WHERE DATE(czas_start) BETWEEN ? AND ?
                      GROUP BY status";
$stmt = $conn->prepare($status_stats_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$status_stats = $stmt->get_result();

// Wykres wykorzystania w czasie
$daily_usage_query = "SELECT DATE(czas_start) as date, COUNT(*) as reservations 
                     FROM reservations 
                     WHERE DATE(czas_start) BETWEEN ? AND ?
                     GROUP BY DATE(czas_start) 
                     ORDER BY date";
$stmt = $conn->prepare($daily_usage_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$daily_usage = $stmt->get_result();

// Średni czas rezerwacji
$avg_duration_query = "SELECT AVG(TIMESTAMPDIFF(MINUTE, czas_start, czas_stop)) as avg_minutes 
                      FROM reservations 
                      WHERE DATE(czas_start) BETWEEN ? AND ?";
$stmt = $conn->prepare($avg_duration_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$avg_duration = $stmt->get_result()->fetch_assoc()['avg_minutes'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Raporty i Statystyki</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Sale konferencyjne</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin_dashboard.php">Panel Admin</a>
                <a class="nav-link" href="../index.php">Strona główna</a>
                <a class="nav-link" href="../logout.php">Wyloguj</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2 class="mb-4">Raporty i Statystyki</h2>
        
        <!-- Filtr dat -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="date_from" class="form-label">Data od</label>
                        <input type="date" class="form-control" name="date_from" id="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="date_to" class="form-label">Data do</label>
                        <input type="date" class="form-control" name="date_to" id="date_to" value="<?php echo $date_to; ?>">
                    </div>                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Filtruj</button>
                            <div class="dropdown">
                                <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-download"></i> Eksport
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="export_csv.php?type=reservations&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                        <i class="fas fa-table"></i> Rezerwacje (CSV)
                                    </a></li>
                                    <li><a class="dropdown-item" href="export_csv.php?type=rooms_usage&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                        <i class="fas fa-chart-bar"></i> Wykorzystanie sal (CSV)
                                    </a></li>
                                    <li><a class="dropdown-item" href="export_csv.php?type=users_activity&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                        <i class="fas fa-users"></i> Aktywność użytkowników (CSV)
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statystyki ogólne -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Łączne rezerwacje</h5>
                        <h3 class="text-primary"><?php echo $total_reservations; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Średni czas rezerwacji</h5>
                        <h3 class="text-success"><?php echo round($avg_duration ?? 0); ?> min</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Okres</h5>
                        <h3 class="text-info"><?php echo date('d.m', strtotime($date_from)) . ' - ' . date('d.m', strtotime($date_to)); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Status</h5>
                        <h3 class="text-warning">Aktywne</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wykresy -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Wykorzystanie sal w czasie</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyUsageChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Status rezerwacji</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Najpopularniejsze sale -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Najpopularniejsze sale</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nazwa sali</th>
                                <th>Liczba rezerwacji</th>
                                <th>Procent wykorzystania</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $popular_rooms->data_seek(0);
                            while ($room = $popular_rooms->fetch_assoc()): 
                                $percentage = $total_reservations > 0 ? round(($room['reservation_count'] / $total_reservations) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($room['nazwa']); ?></td>
                                    <td><?php echo $room['reservation_count']; ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%">
                                                <?php echo $percentage; ?>%
                                            </div>
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
        // Wykres wykorzystania dziennego
        const dailyCtx = document.getElementById('dailyUsageChart').getContext('2d');
        const dailyData = [
            <?php 
            $daily_usage->data_seek(0);
            $daily_labels = [];
            $daily_values = [];
            while ($day = $daily_usage->fetch_assoc()) {
                $daily_labels[] = "'" . date('d.m', strtotime($day['date'])) . "'";
                $daily_values[] = $day['reservations'];
            }
            ?>
        ];
        
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', $daily_labels); ?>],
                datasets: [{
                    label: 'Liczba rezerwacji',
                    data: [<?php echo implode(',', $daily_values); ?>],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Wykres statusów
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        <?php 
        $status_stats->data_seek(0);
        $status_labels = [];
        $status_values = [];
        $status_colors = [
            'pending' => '#ffc107',
            'approved' => '#28a745',
            'rejected' => '#dc3545',
            'cancelled' => '#6c757d'
        ];
        $chart_colors = [];
        while ($status = $status_stats->fetch_assoc()) {
            $status_labels[] = "'" . ucfirst($status['status']) . "'";
            $status_values[] = $status['count'];
            $chart_colors[] = "'" . ($status_colors[$status['status']] ?? '#007bff') . "'";
        }
        ?>
        
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', $status_labels); ?>],
                datasets: [{
                    data: [<?php echo implode(',', $status_values); ?>],
                    backgroundColor: [<?php echo implode(',', $chart_colors); ?>]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>