<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$query = "SELECT r.*, 
    (SELECT COUNT(*) FROM reservations res 
WHERE res.id_sali = r.id 
AND NOW() BETWEEN res.czas_start AND res.czas_stop) as is_reserved,
    COALESCE(r.srednia_ocena, 0) as srednia_ocena,
    COALESCE(r.liczba_ocen, 0) as liczba_ocen
    FROM rooms r";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sale konferencyjne</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Sale konferencyjne</a>            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Menedzer'): ?>
                    <a href="admin/admin_dashboard.php" class="nav-link">Panel Administracyjny</a>
                <?php endif; ?>                <a class="nav-link" href="profile.php">Mój profil</a>
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
                <a class="nav-link" href="logout.php">Wyloguj</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row g-4">
            <?php while ($room = $result->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card h-100 room-card" data-room-id="<?php echo $room['id']; ?>">
                        <div class="card-body position-relative">
                            <div class="position-absolute top-0 end-0 mt-2 me-2">
                                <i class="fas fa-circle <?php echo $room['is_reserved'] ? 'text-danger' : 'text-success'; ?>"></i>
                            </div>
                            <h3 class="card-title"><?php echo htmlspecialchars($room['nazwa']); ?></h3>
                            <p class="card-text">Pojemność: <?php echo $room['pojemnosc']; ?> osób</p>
                            <p class="card-text">Wyposażenie: <?php echo htmlspecialchars($room['wyposazenie']); ?></p>
                            
                            <!-- Rating display -->
                            <div class="rating-display mb-2">
                                <?php if ($room['liczba_ocen'] > 0): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="stars me-2">
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
                                        <span class="text-muted">
                                            <?php echo number_format($avgRating, 1); ?> 
                                            (<?php echo $room['liczba_ocen']; ?> ocen)
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Brak ocen</span>
                                <?php endif; ?>
                            </div>
                            
                            <button class="btn btn-primary btn-sm me-2" onclick="event.stopPropagation(); openReservationModal(<?php echo $room['id']; ?>)">
                                Zarezerwuj
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="event.stopPropagation(); openRatingsModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['nazwa']); ?>')">
                                Zobacz oceny
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="modal fade" id="reservationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rezerwacja sali</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="reservationStatus"></div>                    <form id="reservationForm" class="d-none">
                        <div class="mb-3">
                            <label for="bookingDate" class="form-label">Data rezerwacji</label>
                            <input type="date" class="form-control" name="bookingDate" id="bookingDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="startTime" class="form-label">Godzina rozpoczęcia</label>
                            <input type="time" class="form-control" name="startTime" id="startTime" required>
                        </div>
                        <div class="mb-3">
                            <label for="endTime" class="form-label">Godzina zakończenia</label>
                            <input type="time" class="form-control" name="endTime" id="endTime" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Opis rezerwacji (opcjonalnie)</label>
                            <textarea class="form-control" name="description" id="description" rows="3" placeholder="Cel spotkania, liczba uczestników itp."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Zarezerwuj</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Ratings Modal -->
    <div class="modal fade" id="ratingsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Oceny sali: <span id="roomNameInModal"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Rating Summary -->
                    <div id="ratingSummary" class="mb-4"></div>
                    
                    <!-- Add/Edit Rating Form -->
                    <div class="border-top pt-3 mb-4">
                        <h6>Oceń tę salę:</h6>
                        <form id="ratingForm">
                            <input type="hidden" id="ratingRoomId" name="room_id">
                            <div class="mb-3">
                                <label class="form-label">Twoja ocena:</label>
                                <div class="rating-input">
                                    <span class="rating-star" data-rating="1">⭐</span>
                                    <span class="rating-star" data-rating="2">⭐</span>
                                    <span class="rating-star" data-rating="3">⭐</span>
                                    <span class="rating-star" data-rating="4">⭐</span>
                                    <span class="rating-star" data-rating="5">⭐</span>
                                </div>
                                <input type="hidden" id="selectedRating" name="rating" required>
                            </div>
                            <div class="mb-3">
                                <label for="ratingComment" class="form-label">Komentarz (opcjonalnie):</label>
                                <textarea class="form-control" id="ratingComment" name="comment" rows="3" 
                                    placeholder="Podziel się swoimi wrażeniami o tej sali..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Zapisz ocenę</button>
                        </form>
                    </div>
                    
                    <!-- Existing Ratings -->
                    <div class="border-top pt-3">
                        <h6>Wszystkie oceny:</h6>
                        <div id="ratingsList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/reservations.js"></script>
    <script>
        function updateRoomStatuses() {
            fetch('check_rooms_status.php')
                .then(response => response.json())
                .then(statuses => {
                    Object.entries(statuses).forEach(([roomId, isReserved]) => {
                        const statusIcon = document.querySelector(`.room-card[data-room-id="${roomId}"] .fas.fa-circle`);
                        if (statusIcon) {
                            statusIcon.classList.remove('text-success', 'text-danger');
                            statusIcon.classList.add(isReserved ? 'text-danger' : 'text-success');
                        }
                    });
                });
        }

        setInterval(updateRoomStatuses, 1000);

        // Rating system JavaScript
        let currentRoomId = null;
        let selectedRating = 0;

        function openRatingsModal(roomId, roomName) {
            currentRoomId = roomId;
            document.getElementById('roomNameInModal').textContent = roomName;
            document.getElementById('ratingRoomId').value = roomId;
            
            // Load ratings
            loadRatings(roomId);
            loadUserRating(roomId);
            
            const ratingsModal = new bootstrap.Modal(document.getElementById('ratingsModal'));
            ratingsModal.show();
        }

        function openReservationModal(roomId) {
            // Trigger the existing room card click functionality
            const roomCard = document.querySelector(`[data-room-id="${roomId}"]`);
            if (roomCard) {
                roomCard.click();
            }
        }

        async function loadRatings(roomId) {
            try {
                const response = await fetch('ratings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'get_ratings',
                        room_id: roomId
                    })
                });
                
                const data = await response.json();
                displayRatings(data);
            } catch (error) {
                console.error('Error loading ratings:', error);
            }
        }

        async function loadUserRating(roomId) {
            try {
                const response = await fetch('ratings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'get_user_rating',
                        room_id: roomId
                    })
                });
                
                const data = await response.json();
                if (data) {
                    // Set user's existing rating
                    setRatingStars(data.ocena);
                    document.getElementById('ratingComment').value = data.komentarz || '';
                }
            } catch (error) {
                console.error('Error loading user rating:', error);
            }
        }

        function displayRatings(data) {
            // Display summary
            const summaryDiv = document.getElementById('ratingSummary');
            if (data.count > 0) {
                summaryDiv.innerHTML = `
                    <div class="text-center">
                        <h4>${data.average}</h4>
                        <div class="stars fs-5 mb-2">
                            ${generateStarsHTML(data.average)}
                        </div>
                        <p class="text-muted">Na podstawie ${data.count} ocen</p>
                    </div>
                `;
            } else {
                summaryDiv.innerHTML = '<p class="text-center text-muted">Brak ocen dla tej sali</p>';
            }

            // Display individual ratings
            const ratingsList = document.getElementById('ratingsList');
            if (data.ratings.length > 0) {
                ratingsList.innerHTML = data.ratings.map(rating => `
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stars mb-1">
                                    ${generateStarsHTML(rating.ocena)}
                                </div>
                                <strong>${rating.nazwa_uzytkownika}</strong>
                                <small class="text-muted ms-2">${new Date(rating.data_utworzenia).toLocaleDateString('pl-PL')}</small>
                            </div>
                        </div>
                        ${rating.komentarz ? `<p class="mt-2 mb-0">${rating.komentarz}</p>` : ''}
                    </div>
                `).join('');
            } else {
                ratingsList.innerHTML = '<p class="text-muted">Brak komentarzy</p>';
            }
        }

        function generateStarsHTML(rating) {
            let html = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= rating) {
                    html += '<i class="fas fa-star text-warning"></i>';
                } else if (i - 0.5 <= rating) {
                    html += '<i class="fas fa-star-half-alt text-warning"></i>';
                } else {
                    html += '<i class="far fa-star text-warning"></i>';
                }
            }
            return html;
        }

        // Rating input handling
        document.querySelectorAll('.rating-star').forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                setRatingStars(rating);
            });
            
            star.addEventListener('mouseover', function() {
                const rating = parseInt(this.dataset.rating);
                highlightStars(rating);
            });
        });

        document.querySelector('.rating-input').addEventListener('mouseleave', function() {
            highlightStars(selectedRating);
        });

        function setRatingStars(rating) {
            selectedRating = rating;
            document.getElementById('selectedRating').value = rating;
            highlightStars(rating);
        }

        function highlightStars(rating) {
            document.querySelectorAll('.rating-star').forEach((star, index) => {
                if (index < rating) {
                    star.style.color = '#ffc107';
                    star.style.filter = 'brightness(1)';
                } else {
                    star.style.color = '#dee2e6';
                    star.style.filter = 'brightness(0.5)';
                }
            });
        }

        // Rating form submission
        document.getElementById('ratingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (selectedRating === 0) {
                alert('Proszę wybrać ocenę');
                return;
            }
            
            const formData = new FormData(this);
            const ratingData = {
                action: 'add_rating',
                room_id: parseInt(formData.get('room_id')),
                rating: parseInt(formData.get('rating')),
                comment: formData.get('comment')
            };
            
            try {
                const response = await fetch('ratings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(ratingData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Reload ratings
                    loadRatings(currentRoomId);
                    // Reload page to update room card ratings
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert(result.error || 'Błąd podczas zapisywania oceny');
                }
            } catch (error) {
                console.error('Error submitting rating:', error);
                alert('Błąd podczas zapisywania oceny');
            }
        });

        // Initialize star ratings
        highlightStars(0);
    </script>
</body>
</html>
