-- Dodaj przykładowe sale konferencyjne
INSERT INTO `rooms` (`nazwa`, `pojemnosc`, `wyposazenie`, `dostepnosc`) VALUES
('Sala Konferencyjna A', 20, 'Projektor, ekran, klimatyzacja, flipchart, system audio', 1),
('Sala Konferencyjna B', 15, 'Projektor, ekran, klimatyzacja, tablica', 1),
('Sala Konferencyjna C', 30, 'Projektor, ekran, klimatyzacja, system audio-video, mikrofony', 1),
('Sala Szkoleniowa', 25, 'Projektor, ekran, klimatyzacja, flipchart, komputer', 1),
('Sala Wykonawcza', 10, 'Monitor, klimatyzacja, tablica', 1);

-- Dodaj kilka przykładowych rezerwacji dla testowania
INSERT INTO `reservations` (`id_uzytkownika`, `id_sali`, `czas_start`, `czas_stop`, `status`, `opis`) VALUES
(4, 1, '2025-06-12 10:00:00', '2025-06-12 12:00:00', 'approved', 'Spotkanie zespołu projektowego'),
(5, 2, '2025-06-12 14:00:00', '2025-06-12 16:00:00', 'pending', 'Prezentacja dla klienta'),
(6, 1, '2025-06-13 09:00:00', '2025-06-13 11:00:00', 'approved', 'Szkolenie pracowników');
