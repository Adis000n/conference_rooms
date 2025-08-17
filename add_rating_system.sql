-- SQL script to add rating system to the conference room system

-- Create ratings table
CREATE TABLE `ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_uzytkownika` int(11) NOT NULL,
  `id_sali` int(11) NOT NULL,
  `ocena` int(1) NOT NULL CHECK (ocena >= 1 AND ocena <= 5),
  `komentarz` text DEFAULT NULL,
  `data_utworzenia` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_room_rating` (`id_uzytkownika`, `id_sali`),
  FOREIGN KEY (`id_uzytkownika`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_sali`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
  INDEX `idx_room_rating` (`id_sali`),
  INDEX `idx_user_rating` (`id_uzytkownika`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add average rating and rating count columns to rooms table
ALTER TABLE `rooms` 
ADD COLUMN `srednia_ocena` decimal(2,1) DEFAULT NULL,
ADD COLUMN `liczba_ocen` int(11) DEFAULT 0;
