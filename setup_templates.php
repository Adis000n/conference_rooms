<?php
require_once 'db_connect.php';

echo "<h2>Setup Templates Table</h2>";

// Create the reservation_templates table
$sql = "CREATE TABLE IF NOT EXISTS `reservation_templates` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Table 'reservation_templates' created successfully!</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating table: " . $conn->error . "</p>";
}

echo "<p><a href='index.php'>← Wróć do strony głównej</a></p>";
echo "<p><a href='templates.php'>→ Przejdź do szablonów</a></p>";

$conn->close();
?>
