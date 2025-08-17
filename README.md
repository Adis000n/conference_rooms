# System Rezerwacji Sal Konferencyjnych

Kompletny system do zarządzania salami konferencyjnymi z możliwością rezerwacji, administracji i generowania raportów.

## 🚀 Funkcjonalności

### Moduł Użytkownika
- ✅ Rejestracja i logowanie użytkowników
- ✅ Edycja profilu i zmiana hasła  
- ✅ Przeglądanie dostępnych sal konferencyjnych
- ✅ System rezerwacji z kalendarzem
- ✅ Powiadomienia o statusie rezerwacji
- ✅ Historia własnych rezerwacji
- 🆕 **Szablony rezerwacji** - tworzenie predefiniowanych konfiguracji
- 🆕 **Szybka rezerwacja** - używanie szablonów do błyskawicznego rezerwowania
- 🆕 **Anulowanie rezerwacji** - możliwość anulowania własnych rezerwacji

### Moduł Administratora
- ✅ Panel administracyjny z pełnym dostępem
- ✅ Zarządzanie użytkownikami (dodawanie, edycja, usuwanie, role)
- ✅ Zarządzanie salami (dodawanie, edycja, parametry, dostępność)
- ✅ Zarządzanie rezerwacjami (zatwierdzanie, odrzucanie, anulowanie)
- ✅ Generowanie raportów i statystyk
- ✅ Eksport danych do CSV

### Moduł Rezerwacji
- ✅ Sprawdzanie dostępności sal w czasie rzeczywistym
- ✅ Widok kalendarza z FullCalendar.js
- ✅ Walidacja konfliktów rezerwacji
- ✅ System statusów (oczekujące, zatwierdzone, odrzucone, anulowane)
- ✅ Opisy rezerwacji
- 🆕 **Integracja z szablonami** - wybór szablonu podczas rezerwacji
- 🆕 **Automatyczne wypełnianie** - formularze wypełniane na podstawie szablonów

### 🆕 Nowy Moduł Szablonów
- ✅ Tworzenie nazwanych szablonów rezerwacji
- ✅ Definiowanie czasu trwania i opisu
- ✅ Zarządzanie własnymi szablonami
- ✅ Używanie szablonów przy nowych rezerwacjach
- ✅ Tworzenie szablonów z istniejących rezerwacji

### Bezpieczeństwo
- ✅ Hasła szyfrowane z password_hash()
- ✅ Walidacja danych po stronie serwera i klienta  
- ✅ System autoryzacji (administrator/użytkownik)
- ✅ Ochrona przed SQL injection
- ✅ Sesje użytkowników

## 📁 Struktura Bazy Danych

```sql
- users: dane użytkowników (ID, imię, e-mail, hasło, rola)
- rooms: informacje o salach (ID, nazwa, pojemność, wyposażenie, dostępność)  
- reservations: dane rezerwacji (ID, użytkownik, sala, czas, status, opis)
- notifications: powiadomienia (ID, użytkownik, wiadomość, typ, status)
- reservation_templates: 🆕 szablony rezerwacji (ID, użytkownik, nazwa, sala, czas_trwania, opis)
```

## 🛠️ Instalacja

### Wymagania
- XAMPP (Apache + MySQL + PHP 7.4+)
- Przeglądarka internetowa

### 🆕 Aktualizacja dla szablonów
1. Uruchom `setup_templates.php` aby utworzyć nową tabelę `reservation_templates`
2. Nowe pliki zostały automatycznie dodane:
   - `templates.php` - zarządzanie szablonami
   - `reservations.php` - historia rezerwacji i szybka rezerwacja
   - `get_user_templates.php` - API szablonów
   - `cancel_reservation.php` - anulowanie rezerwacji

### Kroki instalacji

1. **Uruchom XAMPP**
   ```
   Uruchom Apache i MySQL w panelu kontrolnym XAMPP
   ```

2. **Skopiuj pliki**
   ```
   Skopiuj folder projektu do: d:\xampp\htdocs\projekty\sale_konf\
   ```

3. **Utwórz bazę danych**
   ```
   - Otwórz phpMyAdmin (http://localhost/phpmyadmin)
   - Utwórz bazę: system_rezerwacji_sal_konferencyjnych
   - Importuj plik: system_rezerwacji_sal_konferencyjnych.sql
   ```

4. **Inicjalizacja systemu**
   ```
   Otwórz: http://localhost/projekty/sale_konf/setup.php
   ```

5. **Gotowe!**
   ```
   Otwórz: http://localhost/projekty/sale_konf/login.php
   ```

## 👥 Konta Testowe

| Email | Hasło | Rola | Opis |
|-------|-------|------|------|
| karol123@gmail.com | (sprawdź w bazie) | Menedzer | Administrator systemu |
| pawel123@gmail.com | 321 | Gość | Zwykły użytkownik |
| michal123@gmail.com | 231 | Gość | Zwykły użytkownik |
| bartek123@gmail.com | 132 | Gość | Zwykły użytkownik |

## 📋 Instrukcja Użytkowania

### Dla Użytkowników
1. Zaloguj się na swoje konto
2. Przeglądaj dostępne sale na stronie głównej
3. Kliknij na salę, aby zobaczyć dostępność i zarezerwować
4. Wypełnij formularz rezerwacji z datą, godziną i opisem
5. Śledź status swoich rezerwacji w profilu
6. Sprawdzaj powiadomienia o statusie rezerwacji

### Dla Administratorów
1. Zaloguj się kontem administratora
2. Przejdź do Panelu Administracyjnego
3. Zarządzaj salami - dodawaj nowe, edytuj istniejące
4. Przeglądaj rezerwacje - zatwierdzaj lub odrzucaj wnioski
5. Zarządzaj użytkownikami - dodawaj, edytuj, zmieniaj role
6. Generuj raporty i statystyki wykorzystania
7. Eksportuj dane do plików CSV

## 🗓️ Kalendarz
- Widok miesięczny, tygodniowy i dzienny
- Kolorowe oznaczenia statusów rezerwacji
- Filtrowanie według sal i statusów
- Szybka rezerwacja przez kliknięcie w datę

## 📊 Raporty i Statystyki
- Wykorzystanie sal w czasie
- Najpopularniejsze sale  
- Statystyki według statusów rezerwacji
- Aktywność użytkowników
- Eksport do CSV

## 🔧 Rozwiązywanie Problemów

### Błąd "wystąpił błąd" przy kliknięciu na salę
1. Sprawdź czy XAMPP jest uruchomiony
2. Uruchom: http://localhost/projekty/sale_konf/config.php
3. Sprawdź czy baza danych jest poprawnie skonfigurowana
4. Uruchom setup.php aby zainicjować bazę

### Problemy z logowaniem
1. Sprawdź czy używasz poprawnych danych logowania
2. Sprawdź czy hasła są poprawnie zaszyfrowane (uruchom setup.php)
3. Sprawdź konfigurację bazy danych w db_connect.php

### Brak sal do rezerwacji
1. Uruchom setup.php aby dodać przykładowe sale
2. Lub dodaj sale ręcznie przez panel administratora

## 📁 Struktura Plików

```
/
├── index.php              # Strona główna z salami
├── login.php              # Logowanie
├── register.php           # Rejestracja  
├── profile.php            # Profil użytkownika
├── calendar.php           # Kalendarz rezerwacji
├── notifications.php      # Powiadomienia
├── setup.php              # Inicjalizacja systemu
├── config.php             # Konfiguracja i testy
├── db_connect.php         # Połączenie z bazą
├── make_reservation.php   # API rezerwacji
├── check_reservation.php  # API sprawdzania dostępności
├── styles.css             # Style CSS
├── js/
│   └── reservations.js    # JavaScript dla rezerwacji
└── admin/
    ├── admin_dashboard.php # Panel administratora
    ├── users.php          # Zarządzanie użytkownikami  
    ├── rooms.php          # Zarządzanie salami
    ├── reservations.php   # Zarządzanie rezerwacjami
    ├── reports.php        # Raporty i statystyki
    └── export_csv.php     # Eksport do CSV
```

## 🎯 Dodatkowe Funkcje

- Real-time aktualizacja statusu sal
- Responsywny design (Bootstrap 5)
- Walidacja formularzy
- System powiadomień
- Kolorowe oznaczenia statusów
- Intuicyjny interfejs użytkownika
- Bezpieczne API endpoints

## 📞 Wsparcie

W przypadku problemów:
1. Sprawdź logi błędów w php_errors.log
2. Uruchom config.php do diagnostyki
3. Sprawdź czy wszystkie rozszerzenia PHP są zainstalowane
4. Upewnij się że baza danych jest poprawnie skonfigurowana

---

**System Rezerwacji Sal Konferencyjnych** - kompleksowe rozwiązanie do zarządzania rezerwacjami w organizacji.
