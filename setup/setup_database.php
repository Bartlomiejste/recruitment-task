<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Database;

$pdo = Database::getConnection();

// Wyczyszczenie istniejących danych
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("TRUNCATE TABLE payments");
$pdo->exec("TRUNCATE TABLE invoice_items");
$pdo->exec("TRUNCATE TABLE invoices");
$pdo->exec("TRUNCATE TABLE clients");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// Dodanie klientów
$pdo->exec("
    INSERT INTO clients (name, bank_account, nip) VALUES
    ('Firma A', '1234567890123456', '1234567890'),
    ('Firma B', '9876543210987654', '9876543210'),
    ('Firma C', '1111222233334444', '1111222233')
");

// Dodanie faktur
$pdo->exec("
    INSERT INTO invoices (client_id, number, issue_date, due_date, total) VALUES
    (1, 'FV001', '2024-01-01', '2024-01-31', 1200.00), -- Nadpłata
    (2, 'FV002', '2024-02-01', '2024-02-28', 1500.00), -- Niedopłata
    (3, 'FV003', '2024-03-01', '2024-03-15', 1000.00)  -- Przeterminowana
");

// Dodanie pozycji faktur
$pdo->exec("
    INSERT INTO invoice_items (invoice_id, product_name, quantity, price) VALUES
    (1, 'Produkt A', 2, 300.00),
    (1, 'Produkt B', 2, 300.00),
    (2, 'Produkt C', 3, 500.00),
    (3, 'Produkt D', 2, 500.00)
");

// Dodanie płatności
$pdo->exec("
    INSERT INTO payments (invoice_id, title, amount, payment_date, bank_account) VALUES
    (1, 'Zaliczka', 1300.00, '2024-01-15', '1234567890123456'), -- Nadpłata (1300 - 1200 = 100)
    (2, 'Częściowa wpłata', 1000.00, '2024-02-15', '9876543210987654'), -- Niedopłata (1500 - 1000 = 500)
    (3, 'Zaliczka', 500.00, '2024-03-20', '1111222233334444')  -- Przeterminowana (1000 - 500 = 500)
");

echo "Dane zostały dodane!";