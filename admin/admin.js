document.addEventListener('DOMContentLoaded', function() {
    // Add Room
    document.getElementById('addRoomForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('name', document.getElementById('roomName').value);
        formData.append('capacity', document.getElementById('capacity').value);
        formData.append('equipment', document.getElementById('equipment').value);

        fetch('add_room.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Błąd podczas dodawania sali: ' + data.message);
            }
        });
    });

    // Delete Room
    document.querySelectorAll('.delete-room').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Czy na pewno chcesz usunąć tę salę?')) {
                const roomId = this.dataset.roomId;
                fetch('delete_room.php', {
                    method: 'POST',
                    body: JSON.stringify({ id: roomId }),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Błąd podczas usuwania sali: ' + data.message);
                    }
                });
            }
        });
    });

    // Delete Reservation
    document.querySelectorAll('.delete-reservation').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Czy na pewno chcesz usunąć tę rezerwację?')) {
                const reservationId = this.dataset.reservationId;
                fetch('delete_reservation.php', {
                    method: 'POST',
                    body: JSON.stringify({ id: reservationId }),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Błąd podczas usuwania rezerwacji: ' + data.message);
                    }
                });
            }
        });
    });
});
