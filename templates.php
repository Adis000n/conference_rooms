<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $nazwa_szablonu = trim($_POST['nazwa_szablonu']);
                $id_sali = (int)$_POST['id_sali'];
                $czas_trwania = (int)$_POST['czas_trwania'];
                $opis = trim($_POST['opis']);
                
                if (!empty($nazwa_szablonu) && $id_sali > 0 && $czas_trwania > 0) {
                    $stmt = $conn->prepare("INSERT INTO reservation_templates (id_uzytkownika, nazwa_szablonu, id_sali, czas_trwania, opis) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("issis", $user_id, $nazwa_szablonu, $id_sali, $czas_trwania, $opis);
                    if ($stmt->execute()) {
                        $message = '<div class="alert alert-success">Szablon został utworzony pomyślnie!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Błąd podczas tworzenia szablonu.</div>';
                    }
                }
                break;
                
            case 'delete':
                $template_id = (int)$_POST['template_id'];
                $stmt = $conn->prepare("DELETE FROM reservation_templates WHERE id = ? AND id_uzytkownika = ?");
                $stmt->bind_param("ii", $template_id, $user_id);
                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Szablon został usunięty!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Błąd podczas usuwania szablonu.</div>';
                }
                break;
        }
    }
}

// Get user's templates
$templates_query = "SELECT rt.*, r.nazwa as room_name FROM reservation_templates rt 
                   JOIN rooms r ON rt.id_sali = r.id 
                   WHERE rt.id_uzytkownika = ? 
                   ORDER BY rt.data_utworzenia DESC";
$stmt = $conn->prepare($templates_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$templates = $stmt->get_result();

// Get all rooms for the form
$rooms_query = "SELECT id, nazwa FROM rooms WHERE dostepnosc = 1 ORDER BY nazwa";
$rooms = $conn->query($rooms_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Szablony rezerwacji - Sale konferencyjne</title>
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
                <a class="nav-link active" href="templates.php">Szablony</a>
                <a class="nav-link" href="reservations.php">Moje rezerwacje</a>
                <a class="nav-link" href="logout.php">Wyloguj</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1><i class="fas fa-templates"></i> Szablony rezerwacji</h1>
                <p class="text-muted">Stwórz szablony dla często wykorzystywanych konfiguracji rezerwacji, aby przyspieszyć proces rezerwowania sal.</p>
                <?php echo $message; ?>
            </div>
        </div>

        <!-- Create Template Form -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus"></i> Utwórz nowy szablon</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nazwa_szablonu" class="form-label">Nazwa szablonu</label>
                                    <input type="text" class="form-control" id="nazwa_szablonu" name="nazwa_szablonu" required placeholder="np. Cotygodniowe spotkanie zespołu">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="id_sali" class="form-label">Sala</label>
                                    <select class="form-select" id="id_sali" name="id_sali" required>
                                        <option value="">Wybierz salę</option>
                                        <?php while ($room = $rooms->fetch_assoc()): ?>
                                            <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['nazwa']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="czas_trwania" class="form-label">Czas trwania (w minutach)</label>
                                    <select class="form-select" id="czas_trwania" name="czas_trwania" required>
                                        <option value="">Wybierz czas trwania</option>
                                        <option value="30">30 minut</option>
                                        <option value="60">1 godzina</option>
                                        <option value="90">1,5 godziny</option>
                                        <option value="120">2 godziny</option>
                                        <option value="180">3 godziny</option>
                                        <option value="240">4 godziny</option>
                                        <option value="480">8 godzin</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="opis" class="form-label">Opis (opcjonalny)</label>
                                    <textarea class="form-control" id="opis" name="opis" rows="3" placeholder="Opis rezerwacji..."></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Utwórz szablon
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Templates List -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> Twoje szablony</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($templates->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($template = $templates->fetch_assoc()): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card template-card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="fas fa-bookmark text-primary"></i> 
                                                    <?php echo htmlspecialchars($template['nazwa_szablonu']); ?>
                                                </h6>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($template['room_name']); ?><br>
                                                        <i class="fas fa-clock"></i> <?php echo $template['czas_trwania']; ?> minut
                                                        <?php if (!empty($template['opis'])): ?>
                                                            <br><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($template['opis']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </p>
                                            </div>
                                            <div class="card-footer bg-transparent d-flex justify-content-between">
                                                <button class="btn btn-sm btn-success use-template-btn" 
                                                        data-template-id="<?php echo $template['id']; ?>"
                                                        data-room-id="<?php echo $template['id_sali']; ?>"
                                                        data-duration="<?php echo $template['czas_trwania']; ?>"
                                                        data-description="<?php echo htmlspecialchars($template['opis']); ?>"
                                                        data-room-name="<?php echo htmlspecialchars($template['room_name']); ?>">
                                                    <i class="fas fa-play"></i> Użyj
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Czy na pewno chcesz usunąć ten szablon?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i>
                                Nie masz jeszcze żadnych szablonów. Utwórz pierwszy szablon, aby przyspieszyć przyszłe rezerwacje!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Reservation Modal -->
    <div class="modal fade" id="templateReservationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rezerwacja z szablonu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="templateReservationForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="templateBookingDate" class="form-label">Data</label>
                                <input type="date" class="form-control" id="templateBookingDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="templateStartTime" class="form-label">Godzina rozpoczęcia</label>
                                <input type="time" class="form-control" id="templateStartTime" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Czas trwania</label>
                                <input type="text" class="form-control" id="templateDuration" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="templateDescription" class="form-label">Opis</label>
                            <textarea class="form-control" id="templateDescription" rows="3"></textarea>
                        </div>
                        <input type="hidden" id="templateRoomId">
                        <input type="hidden" id="templateDurationMinutes">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-primary" id="confirmTemplateReservation">
                        <i class="fas fa-calendar-plus"></i> Utwórz rezerwację
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const templateModal = new bootstrap.Modal(document.getElementById('templateReservationModal'));
            
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('templateBookingDate').min = today;
            
            // Handle template usage
            document.querySelectorAll('.use-template-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const roomId = this.dataset.roomId;
                    const duration = this.dataset.duration;
                    const description = this.dataset.description;
                    const roomName = this.dataset.roomName;
                    
                    document.getElementById('templateRoomId').value = roomId;
                    document.getElementById('templateDurationMinutes').value = duration;
                    document.getElementById('templateDuration').value = `${duration} minut (${roomName})`;
                    document.getElementById('templateDescription').value = description;
                    
                    document.querySelector('#templateReservationModal .modal-title').textContent = `Rezerwacja z szablonu - ${roomName}`;
                    
                    templateModal.show();
                });
            });
            
            // Handle template reservation confirmation
            document.getElementById('confirmTemplateReservation').addEventListener('click', async function() {
                const date = document.getElementById('templateBookingDate').value;
                const startTime = document.getElementById('templateStartTime').value;
                const roomId = document.getElementById('templateRoomId').value;
                const durationMinutes = parseInt(document.getElementById('templateDurationMinutes').value);
                const description = document.getElementById('templateDescription').value;
                
                if (!date || !startTime) {
                    alert('Proszę wypełnić wszystkie wymagane pola');
                    return;
                }
                
                // Calculate end time
                const startDateTime = new Date(`${date}T${startTime}`);
                const endDateTime = new Date(startDateTime.getTime() + (durationMinutes * 60000));
                
                const reservationData = {
                    room_id: parseInt(roomId),
                    start_datetime: startDateTime.toISOString().slice(0, 19).replace('T', ' '),
                    end_datetime: endDateTime.toISOString().slice(0, 19).replace('T', ' '),
                    description: description
                };
                
                try {
                    const response = await fetch('make_reservation.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(reservationData)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Rezerwacja została utworzona pomyślnie!');
                        templateModal.hide();
                        // Optionally redirect to calendar or reservations page
                        window.location.href = 'calendar.php';
                    } else {
                        alert('Błąd: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Wystąpił błąd podczas tworzenia rezerwacji');
                }
            });
        });
    </script>
</body>
</html>
