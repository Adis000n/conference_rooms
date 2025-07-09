document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('reservationModal'));
    const reservationForm = document.getElementById('reservationForm');
    const reservationStatus = document.getElementById('reservationStatus');
    let currentRoomId = null;

    function formatDateTime(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleString('pl-PL');
    }

    async function checkRoomStatus(roomId) {
        try {
            const response = await fetch(`check_reservation.php?room_id=${roomId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            let statusHtml = '<div class="timeline mb-3">';
            
            if (data.bookings && data.bookings.length > 0) {
                statusHtml += '<h6>Harmonogram rezerwacji:</h6>';
                data.bookings.forEach(booking => {
                    const isCurrentBooking = new Date(booking.start) <= new Date() && new Date(booking.end) >= new Date();
                    const statusBadge = booking.status === 'approved' ? 'success' : 'warning';
                    
                    statusHtml += `
                        <div class="booking-item mb-2 p-2 border rounded ${isCurrentBooking ? 'bg-light' : ''}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-calendar-check text-${statusBadge}"></i>
                                    <strong>${formatDateTime(booking.start)} - ${formatDateTime(booking.end)}</strong>
                                </div>
                                <span class="badge bg-${statusBadge}">${booking.status === 'approved' ? 'Zatwierdzona' : 'Oczekuje'}</span>
                            </div>
                            ${booking.user ? `<small class="text-muted">Użytkownik: ${booking.user}</small>` : ''}
                            ${booking.description ? `<div><small class="text-info">${booking.description}</small></div>` : ''}
                        </div>`;
                });
            } else {
                statusHtml += '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Sala jest całkowicie dostępna</div>';
            }
            
            statusHtml += '</div>';
            reservationStatus.innerHTML = statusHtml;
            reservationForm.classList.remove('d-none');

            // Aktualizuj status ikony na karcie sali
            const roomCard = document.querySelector(`.room-card[data-room-id="${roomId}"]`);
            if (roomCard) {
                const statusIcon = roomCard.querySelector('.fas.fa-circle');
                if (statusIcon) {
                    if (data.is_currently_booked) {
                        statusIcon.classList.remove('text-success');
                        statusIcon.classList.add('text-danger');
                    } else {
                        statusIcon.classList.remove('text-danger');
                        statusIcon.classList.add('text-success');
                    }
                }
            }
        } catch (error) {
            console.error('Error checking room status:', error);
            reservationStatus.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Wystąpił błąd podczas sprawdzania dostępności sali</div>';
            reservationForm.classList.add('d-none');
        }
    }

    // Obsługa kliknięcia na kartę sali
    document.querySelectorAll('.room-card').forEach(card => {
        card.addEventListener('click', function() {
            currentRoomId = this.dataset.roomId;
            const roomName = this.querySelector('.card-title').textContent;
            document.querySelector('#reservationModal .modal-title').textContent = `Rezerwacja sali: ${roomName}`;
            
            // Wyświetl loading state
            reservationStatus.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Sprawdzanie dostępności...</div>';
            reservationForm.classList.add('d-none');
            
            // Pokaż modal
            modal.show();
            
            // Sprawdź status sali
            checkRoomStatus(currentRoomId);
        });
    });

    // Obsługa formularza rezerwacji
    reservationForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const date = formData.get('bookingDate');
        const startTime = formData.get('startTime');
        const endTime = formData.get('endTime');
        const description = formData.get('description') || '';
        
        // Walidacja po stronie klienta
        if (!date || !startTime || !endTime) {
            alert('Proszę wypełnić wszystkie wymagane pola');
            return;
        }
        
        if (startTime >= endTime) {
            alert('Czas zakończenia musi być późniejszy niż czas rozpoczęcia');
            return;
        }
        
        const startDateTime = `${date} ${startTime}:00`;
        const endDateTime = `${date} ${endTime}:00`;
        
        // Sprawdź czy data nie jest w przeszłości
        const bookingDate = new Date(startDateTime);
        const now = new Date();
        if (bookingDate <= now) {
            alert('Nie można rezerwować w przeszłości');
            return;
        }
        
        try {
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rezerwuję...';
            
            const response = await fetch('make_reservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    room_id: currentRoomId,
                    start_datetime: startDateTime,
                    end_datetime: endDateTime,
                    description: description
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                alert(result.message);
                modal.hide();
                this.reset();
                // Odśwież status sali
                checkRoomStatus(currentRoomId);
                // Odśwież stronę po 1 sekundzie
                setTimeout(() => location.reload(), 1000);
            } else {
                alert(result.message);
            }
            
            submitButton.disabled = false;
            submitButton.textContent = originalText;
            
        } catch (error) {
            console.error('Error making reservation:', error);
            alert('Wystąpił błąd podczas rezerwacji. Proszę spróbować ponownie.');
            
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = false;
            submitButton.textContent = 'Zarezerwuj';
        }
    });

    // Ustaw minimalną datę na dzisiaj
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('bookingDate');
    if (dateInput) {
        dateInput.min = today;
    }
});
