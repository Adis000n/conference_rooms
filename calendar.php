<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Pobierz wszystkie sale
$rooms_query = "SELECT * FROM rooms WHERE dostepnosc = 1 ORDER BY nazwa";
$rooms_result = $conn->query($rooms_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kalendarz Rezerwacji</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
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
                <a class="nav-link active" href="calendar.php">Kalendarz</a>
                <a class="nav-link" href="templates.php">Szablony</a>
                <a class="nav-link" href="reservations.php">Moje rezerwacje</a>
                <a class="nav-link" href="logout.php">Wyloguj</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Filtry</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Sale konferencyjne</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll" checked>
                                <label class="form-check-label" for="selectAll">
                                    <strong>Wszystkie sale</strong>
                                </label>
                            </div>
                            <hr>
                            <?php while ($room = $rooms_result->fetch_assoc()): ?>
                                <div class="form-check">
                                    <input class="form-check-input room-filter" type="checkbox" 
                                           id="room<?php echo $room['id']; ?>" 
                                           value="<?php echo $room['id']; ?>" checked>
                                    <label class="form-check-label" for="room<?php echo $room['id']; ?>">
                                        <?php echo htmlspecialchars($room['nazwa']); ?>
                                        <small class="text-muted d-block">
                                            <?php echo $room['pojemnosc']; ?> osób
                                        </small>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status rezerwacji</label>
                            <div class="form-check">
                                <input class="form-check-input status-filter" type="checkbox" id="statusApproved" value="approved" checked>
                                <label class="form-check-label" for="statusApproved">
                                    <span class="badge bg-success">Zatwierdzone</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input status-filter" type="checkbox" id="statusPending" value="pending" checked>
                                <label class="form-check-label" for="statusPending">
                                    <span class="badge bg-warning">Oczekujące</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Legenda</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <span class="badge bg-success me-2">■</span> Zatwierdzone
                        </div>
                        <div class="mb-2">
                            <span class="badge bg-warning me-2">■</span> Oczekujące
                        </div>
                        <div class="mb-2">
                            <span class="badge bg-info me-2">■</span> Moje rezerwacje
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Kalendarz Rezerwacji</h5>
                        <button class="btn btn-primary" onclick="showQuickReservation()">
                            <i class="fas fa-plus"></i> Szybka rezerwacja
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal rezerwacji -->
    <div class="modal fade" id="reservationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nowa Rezerwacja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="quickReservationForm">
                        <div class="mb-3">
                            <label for="reservationRoom" class="form-label">Sala</label>
                            <select class="form-control" id="reservationRoom" name="room_id" required>
                                <option value="">Wybierz salę</option>
                                <?php 
                                $rooms_result->data_seek(0);
                                while ($room = $rooms_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $room['id']; ?>">
                                        <?php echo htmlspecialchars($room['nazwa']); ?> (<?php echo $room['pojemnosc']; ?> osób)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reservationDate" class="form-label">Data</label>
                            <input type="date" class="form-control" id="reservationDate" name="date" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reservationStartTime" class="form-label">Godz. rozpoczęcia</label>
                                    <input type="time" class="form-control" id="reservationStartTime" name="start_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="reservationEndTime" class="form-label">Godz. zakończenia</label>
                                    <input type="time" class="form-control" id="reservationEndTime" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="reservationDescription" class="form-label">Opis (opcjonalnie)</label>
                            <textarea class="form-control" id="reservationDescription" name="description" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="button" class="btn btn-primary" onclick="submitQuickReservation()">Zarezerwuj</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales/pl.global.min.js"></script>
    <script>
        let calendar;
        const reservationModal = new bootstrap.Modal(document.getElementById('reservationModal'));
        
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pl',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: function(fetchInfo, successCallback, failureCallback) {
                    loadCalendarEvents(fetchInfo, successCallback, failureCallback);
                },
                eventClick: function(info) {
                    showEventDetails(info.event);
                },
                dateClick: function(info) {
                    showQuickReservation(info.dateStr);
                },
                height: 'auto'
            });
            
            calendar.render();
            
            // Obsługa filtrów
            document.getElementById('selectAll').addEventListener('change', function() {
                const roomFilters = document.querySelectorAll('.room-filter');
                roomFilters.forEach(filter => filter.checked = this.checked);
                calendar.refetchEvents();
            });
            
            document.querySelectorAll('.room-filter, .status-filter').forEach(filter => {
                filter.addEventListener('change', function() {
                    calendar.refetchEvents();
                });
            });
        });
        
        function loadCalendarEvents(fetchInfo, successCallback, failureCallback) {
            const selectedRooms = Array.from(document.querySelectorAll('.room-filter:checked')).map(cb => cb.value);
            const selectedStatuses = Array.from(document.querySelectorAll('.status-filter:checked')).map(cb => cb.value);
            
            if (selectedRooms.length === 0 || selectedStatuses.length === 0) {
                successCallback([]);
                return;
            }
            
            fetch('get_calendar_events.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    start: fetchInfo.startStr,
                    end: fetchInfo.endStr,
                    rooms: selectedRooms,
                    statuses: selectedStatuses
                })
            })
            .then(response => response.json())
            .then(events => successCallback(events))
            .catch(error => {
                console.error('Error loading events:', error);
                failureCallback(error);
            });
        }
        
        function showQuickReservation(dateStr = null) {
            document.getElementById('quickReservationForm').reset();
            if (dateStr) {
                document.getElementById('reservationDate').value = dateStr;
            }
            reservationModal.show();
        }
        
        function submitQuickReservation() {
            const form = document.getElementById('quickReservationForm');
            const formData = new FormData(form);
            
            const data = {
                room_id: formData.get('room_id'),
                start_datetime: formData.get('date') + ' ' + formData.get('start_time') + ':00',
                end_datetime: formData.get('date') + ' ' + formData.get('end_time') + ':00',
                description: formData.get('description')
            };
            
            fetch('make_reservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(result.message);
                    reservationModal.hide();
                    calendar.refetchEvents();
                } else {
                    alert(result.message);
                }
            })
            .catch(error => {
                alert('Wystąpił błąd podczas rezerwacji');
            });
        }
        
        function showEventDetails(event) {
            alert(`Rezerwacja: ${event.title}\nCzas: ${event.start.toLocaleString()} - ${event.end.toLocaleString()}`);
        }
    </script>
</body>
</html>
