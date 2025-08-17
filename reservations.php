<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's reservations
$reservations_query = "SELECT r.*, rm.nazwa as room_name 
                      FROM reservations r 
                      JOIN rooms rm ON r.id_sali = rm.id 
                      WHERE r.id_uzytkownika = ? 
                      ORDER BY r.czas_start DESC";
$stmt = $conn->prepare($reservations_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservations = $stmt->get_result();

// Get all rooms for quick reservation
$rooms_query = "SELECT id, nazwa, pojemnosc FROM rooms WHERE dostepnosc = 1 ORDER BY nazwa";
$rooms = $conn->query($rooms_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Moje rezerwacje - Sale konferencyjne</title>
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
                <a class="nav-link" href="templates.php">Szablony</a>
                <a class="nav-link active" href="reservations.php">Moje rezerwacje</a>
                <a class="nav-link" href="logout.php">Wyloguj</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h1><i class="fas fa-calendar-check"></i> Moje rezerwacje</h1>
                <p class="text-muted">Zarządzaj swoimi rezerwacjami sal konferencyjnych i twórz nowe przy pomocy szablonów.</p>
            </div>
        </div>

        <!-- Quick Reservation with Templates -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-plus-circle"></i> Szybka rezerwacja</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-bookmark text-primary"></i> Użyj szablonu</h6>
                                <div id="templatesContainer">
                                    <p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Ładowanie szablonów...</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-door-open text-success"></i> Lub wybierz salę</h6>
                                <div class="row">
                                    <?php while ($room = $rooms->fetch_assoc()): ?>
                                        <div class="col-12 mb-2">
                                            <button class="btn btn-outline-primary btn-sm w-100 room-quick-btn" 
                                                    data-room-id="<?php echo $room['id']; ?>"
                                                    data-room-name="<?php echo htmlspecialchars($room['nazwa']); ?>">
                                                <i class="fas fa-door-open"></i> 
                                                <?php echo htmlspecialchars($room['nazwa']); ?> 
                                                <small class="text-muted">(<?php echo $room['pojemnosc']; ?> osób)</small>
                                            </button>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Reservations -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list"></i> Historia rezerwacji</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($reservations->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Sala</th>
                                            <th>Data rozpoczęcia</th>
                                            <th>Data zakończenia</th>
                                            <th>Status</th>
                                            <th>Opis</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($reservation = $reservations->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($reservation['room_name']); ?></td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($reservation['czas_start'])); ?></td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($reservation['czas_stop'])); ?></td>
                                                <td>
                                                    <?php
                                                    $status_classes = [
                                                        'pending' => 'bg-warning',
                                                        'approved' => 'bg-success',
                                                        'rejected' => 'bg-danger',
                                                        'cancelled' => 'bg-secondary'
                                                    ];
                                                    $status_labels = [
                                                        'pending' => 'Oczekuje',
                                                        'approved' => 'Zatwierdzona',
                                                        'rejected' => 'Odrzucona',
                                                        'cancelled' => 'Anulowana'
                                                    ];
                                                    $status = $reservation['status'];
                                                    ?>
                                                    <span class="badge <?php echo $status_classes[$status]; ?>">
                                                        <?php echo $status_labels[$status]; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($reservation['opis'])): ?>
                                                        <small><?php echo htmlspecialchars($reservation['opis']); ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">Brak opisu</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($reservation['status'] === 'pending' && strtotime($reservation['czas_start']) > time()): ?>
                                                        <button class="btn btn-sm btn-outline-danger cancel-reservation-btn"
                                                                data-reservation-id="<?php echo $reservation['id']; ?>">
                                                            <i class="fas fa-times"></i> Anuluj
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (strtotime($reservation['czas_start']) > time()): ?>
                                                        <button class="btn btn-sm btn-outline-primary create-template-btn"
                                                                data-room-id="<?php echo $reservation['id_sali']; ?>"
                                                                data-room-name="<?php echo htmlspecialchars($reservation['room_name']); ?>"
                                                                data-start="<?php echo $reservation['czas_start']; ?>"
                                                                data-end="<?php echo $reservation['czas_stop']; ?>"
                                                                data-description="<?php echo htmlspecialchars($reservation['opis']); ?>">
                                                            <i class="fas fa-bookmark"></i> Utwórz szablon
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i>
                                Nie masz jeszcze żadnych rezerwacji. <a href="index.php">Zarezerwuj pierwszą salę</a> lub <a href="templates.php">utwórz szablon</a> dla szybszych rezerwacji w przyszłości.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Reservation Modal -->
    <div class="modal fade" id="quickReservationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Szybka rezerwacja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="quickReservationForm">
                        <div class="mb-3">
                            <label for="quickBookingDate" class="form-label">Data</label>
                            <input type="date" class="form-control" id="quickBookingDate" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="quickStartTime" class="form-label">Godzina rozpoczęcia</label>
                                <input type="time" class="form-control" id="quickStartTime" required>
                            </div>
                            <div class="col-md-6">
                                <label for="quickEndTime" class="form-label">Godzina zakończenia</label>
                                <input type="time" class="form-control" id="quickEndTime" required>
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label for="quickDescription" class="form-label">Opis</label>
                            <textarea class="form-control" id="quickDescription" rows="3"></textarea>
                        </div>
                        <input type="hidden" id="quickRoomId">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-primary" id="confirmQuickReservation">
                        <i class="fas fa-calendar-plus"></i> Utwórz rezerwację
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Template Modal -->
    <div class="modal fade" id="createTemplateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Utwórz szablon z rezerwacji</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createTemplateForm">
                        <div class="mb-3">
                            <label for="templateName" class="form-label">Nazwa szablonu</label>
                            <input type="text" class="form-control" id="templateName" required placeholder="np. Cotygodniowe spotkanie zespołu">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sala</label>
                            <input type="text" class="form-control" id="templateRoomName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Czas trwania</label>
                            <input type="text" class="form-control" id="templateDurationInfo" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="templateDescription" class="form-label">Opis</label>
                            <textarea class="form-control" id="templateDescriptionText" rows="3"></textarea>
                        </div>
                        <input type="hidden" id="templateRoomIdField">
                        <input type="hidden" id="templateDurationField">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-primary" id="confirmCreateTemplate">
                        <i class="fas fa-bookmark"></i> Utwórz szablon
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quickModal = new bootstrap.Modal(document.getElementById('quickReservationModal'));
            const templateModal = new bootstrap.Modal(document.getElementById('createTemplateModal'));
            let userTemplates = [];
            
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('quickBookingDate').min = today;
            document.getElementById('quickBookingDate').value = today;
            
            // Load user templates
            async function loadTemplates() {
                try {
                    const response = await fetch('get_user_templates.php');
                    if (response.ok) {
                        userTemplates = await response.json();
                        displayTemplates();
                    }
                } catch (error) {
                    console.error('Error loading templates:', error);
                    document.getElementById('templatesContainer').innerHTML = '<p class="text-danger">Błąd podczas ładowania szablonów</p>';
                }
            }
            
            function displayTemplates() {
                const container = document.getElementById('templatesContainer');
                if (userTemplates.length === 0) {
                    container.innerHTML = '<p class="text-muted">Nie masz jeszcze żadnych szablonów. <a href="templates.php">Utwórz pierwszy szablon</a></p>';
                    return;
                }
                
                let html = '<div class="row">';
                userTemplates.forEach(template => {
                    html += `
                        <div class="col-12 mb-2">
                            <button class="btn btn-outline-success btn-sm w-100 template-use-btn" 
                                    data-template-id="${template.id}"
                                    data-room-id="${template.id_sali}"
                                    data-duration="${template.czas_trwania}"
                                    data-description="${template.opis || ''}"
                                    data-room-name="${template.room_name}">
                                <i class="fas fa-bookmark"></i> 
                                ${template.nazwa_szablonu} 
                                <small class="text-muted">(${template.room_name}, ${template.czas_trwania} min)</small>
                            </button>
                        </div>`;
                });
                html += '</div>';
                container.innerHTML = html;
                
                // Add event listeners for template buttons
                document.querySelectorAll('.template-use-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const roomId = this.dataset.roomId;
                        const duration = parseInt(this.dataset.duration);
                        const description = this.dataset.description;
                        const roomName = this.dataset.roomName;
                        
                        // Set form values
                        document.getElementById('quickRoomId').value = roomId;
                        document.getElementById('quickDescription').value = description;
                        
                        // Calculate end time based on duration
                        const now = new Date();
                        const startTime = now.getHours().toString().padStart(2, '0') + ':' + 
                                         Math.ceil(now.getMinutes() / 15) * 15;
                        const endDate = new Date();
                        endDate.setMinutes(endDate.getMinutes() + duration);
                        const endTime = endDate.getHours().toString().padStart(2, '0') + ':' + 
                                       endDate.getMinutes().toString().padStart(2, '0');
                        
                        document.getElementById('quickStartTime').value = startTime;
                        document.getElementById('quickEndTime').value = endTime;
                        
                        document.querySelector('#quickReservationModal .modal-title').textContent = `Rezerwacja: ${roomName}`;
                        quickModal.show();
                    });
                });
            }
            
            // Room selection buttons
            document.querySelectorAll('.room-quick-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const roomId = this.dataset.roomId;
                    const roomName = this.dataset.roomName;
                    
                    document.getElementById('quickRoomId').value = roomId;
                    document.getElementById('quickDescription').value = '';
                    document.getElementById('quickStartTime').value = '';
                    document.getElementById('quickEndTime').value = '';
                    document.querySelector('#quickReservationModal .modal-title').textContent = `Rezerwacja: ${roomName}`;
                    quickModal.show();
                });
            });
            
            // Confirm quick reservation
            document.getElementById('confirmQuickReservation').addEventListener('click', async function() {
                const date = document.getElementById('quickBookingDate').value;
                const startTime = document.getElementById('quickStartTime').value;
                const endTime = document.getElementById('quickEndTime').value;
                const roomId = document.getElementById('quickRoomId').value;
                const description = document.getElementById('quickDescription').value;
                
                if (!date || !startTime || !endTime) {
                    alert('Proszę wypełnić wszystkie wymagane pola');
                    return;
                }
                
                const reservationData = {
                    room_id: parseInt(roomId),
                    start_datetime: `${date} ${startTime}`,
                    end_datetime: `${date} ${endTime}`,
                    description: description
                };
                
                try {
                    const response = await fetch('make_reservation.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(reservationData)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Rezerwacja została utworzona pomyślnie!');
                        quickModal.hide();
                        location.reload();
                    } else {
                        alert('Błąd: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Wystąpił błąd podczas tworzenia rezerwacji');
                }
            });
            
            // Create template from reservation
            document.querySelectorAll('.create-template-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const roomId = this.dataset.roomId;
                    const roomName = this.dataset.roomName;
                    const start = new Date(this.dataset.start);
                    const end = new Date(this.dataset.end);
                    const description = this.dataset.description;
                    
                    const duration = Math.round((end - start) / 60000); // minutes
                    
                    document.getElementById('templateRoomIdField').value = roomId;
                    document.getElementById('templateRoomName').value = roomName;
                    document.getElementById('templateDurationField').value = duration;
                    document.getElementById('templateDurationInfo').value = `${duration} minut`;
                    document.getElementById('templateDescriptionText').value = description;
                    document.getElementById('templateName').value = '';
                    
                    templateModal.show();
                });
            });
            
            // Confirm create template
            document.getElementById('confirmCreateTemplate').addEventListener('click', async function() {
                const name = document.getElementById('templateName').value;
                const roomId = document.getElementById('templateRoomIdField').value;
                const duration = document.getElementById('templateDurationField').value;
                const description = document.getElementById('templateDescriptionText').value;
                
                if (!name) {
                    alert('Proszę podać nazwę szablonu');
                    return;
                }
                
                try {
                    const response = await fetch('templates.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=create&nazwa_szablonu=${encodeURIComponent(name)}&id_sali=${roomId}&czas_trwania=${duration}&opis=${encodeURIComponent(description)}`
                    });
                    
                    if (response.ok) {
                        alert('Szablon został utworzony!');
                        templateModal.hide();
                        loadTemplates(); // Reload templates
                    } else {
                        alert('Błąd podczas tworzenia szablonu');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Wystąpił błąd podczas tworzenia szablonu');
                }
            });
            
            // Cancel reservation
            document.querySelectorAll('.cancel-reservation-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    if (!confirm('Czy na pewno chcesz anulować tę rezerwację?')) return;
                    
                    const reservationId = this.dataset.reservationId;
                    
                    try {
                        const response = await fetch('cancel_reservation.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ reservation_id: reservationId })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            alert('Rezerwacja została anulowana');
                            location.reload();
                        } else {
                            alert('Błąd: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Wystąpił błąd podczas anulowania rezerwacji');
                    }
                });
            });
            
            // Load templates on page load
            loadTemplates();
        });
    </script>
</body>
</html>
