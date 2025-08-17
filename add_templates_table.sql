-- SQL for reservation templates table
CREATE TABLE `reservation_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_uzytkownika` int(11) NOT NULL,
  `nazwa_szablonu` varchar(100) NOT NULL,
  `id_sali` int(11) NOT NULL,
  `czas_trwania` int(11) NOT NULL COMMENT 'Duration in minutes',
  `opis` TEXT NULL,
  `data_utworzenia` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_uzytkownika`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_sali`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
