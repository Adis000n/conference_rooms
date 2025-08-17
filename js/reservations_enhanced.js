document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('reservationModal'));
    const reservationForm = document.getElementById('reservationForm');
    const reservationStatus = document.getElementById('reservationStatus');
    const templateSelection = document.getElementById('templateSelection');
    const templateSelect = document.getElementById('templateSelect');
    const clearFormBtn = document.getElementById('clearFormBtn');
    let currentRoomId = null;
    let userTemplates = [];

    // Load user templates
    async function loadUserTemplates() {
        try {
            const response = await fetch('get_user_templates.php');
            if (response.ok) {
                userTemplates = await response.json();
            }
        } catch (error) {
            console.error('Error loading templates:', error);
        }
    }

    function formatDateTime(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleString('pl-PL');
    }

    function populateTemplateSelect(roomId) {
        templateSelect.innerHTML = '<option value="">Wybierz szablon lub utwórz rezerwację ręcznie</option>';
        
        const roomTemplates = userTemplates.filter(template => template.id_sali == roomId);
        
        if (roomTemplates.length > 0) {
            roomTemplates.forEach(template => {
                const option = document.createElement('option');
                option.value = template.id;
                option.textContent = `${template.nazwa_szablonu} (${template.czas_trwania} min)`;
                option.dataset.duration = template.czas_trwania;
                option.dataset.description = template.opis || '';
                templateSelect.appendChild(option);
            });
            templateSelection.classList.remove('d-none');
        } else {
            templateSelection.classList.add('d-none');
        }
    }

    function applyTemplate(templateId) {
        const template = userTemplates.find(t => t.id == templateId);
        if (!template) return;

        const now = new Date();
        const startTime = now.getHours().toString().padStart(2, '0') + ':' + 
                         Math.ceil(now.getMinutes() / 15) * 15;
        
        const endDate = new Date();
        endDate.setMinutes(endDate.getMinutes() + parseInt(template.czas_trwania));
        const endTime = endDate.getHours().toString().padStart(2, '0') + ':' + 
                       endDate.getMinutes().toString().padStart(2, '0');

        // Fill form with template data
        document.getElementById('startTime').value = startTime;
        document.getElementById('endTime').value = endTime;
        document.getElementById('description').value = template.opis || '';
        
        // Set today as default date if empty
        if (!document.getElementById('bookingDate').value) {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('bookingDate').value = today;
        }
    }

    function clearForm() {
        reservationForm.reset();
        templateSelect.value = '';
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('bookingDate').value = today;
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
            
            // Populate template selection for this room
            populateTemplateSelect(roomId);

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
            templateSelection.classList.add('d-none');
        }
    }

    // Template selection handler
    templateSelect.addEventListener('change', function() {
        if (this.value) {
            applyTemplate(this.value);
        }
    });

    // Clear form button handler
    clearFormBtn.addEventListener('click', clearForm);

    // Obsługa kliknięcia na kartę sali
    document.querySelectorAll('.room-card').forEach(card => {
        card.addEventListener('click', function() {
            currentRoomId = this.dataset.roomId;
            const roomName = this.querySelector('.card-title').textContent;
            document.querySelector('#reservationModal .modal-title').textContent = `Rezerwacja sali: ${roomName}`;
            
            // Clear previous data
            clearForm();
            
            // Wyświetl loading state
            reservationStatus.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Sprawdzanie dostępności...</div>';
            reservationForm.classList.add('d-none');
            templateSelection.classList.add('d-none');
            
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
        
        if (!date || !startTime || !endTime) {
            alert('Proszę wypełnić wszystkie wymagane pola');
            return;
        }
        
        const startDateTime = `${date} ${startTime}`;
        const endDateTime = `${date} ${endTime}`;
        
        const reservationData = {
            room_id: parseInt(currentRoomId),
            start_datetime: startDateTime,
            end_datetime: endDateTime,
            description: description
        };
        
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rezerwuję...';
        
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
                alert(result.message);
                modal.hide();
                this.reset();
                templateSelect.value = '';
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
        dateInput.value = today;
    }

    // Load templates on page load
    loadUserTemplates();
});
