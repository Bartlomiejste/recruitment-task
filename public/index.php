<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Services\ReportService;

// Wyświetlenie sekcji head z CSS
echo "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Raporty</title>
    <link rel='stylesheet' href='./style.css'>
</head>
<body>
<h1>Raporty</h1>
";

// Funkcja renderująca tabelę
function renderTableWithFilter(
    string $title,
    string $reportType,
    callable $dataCallback,
    array $columns,
    string $defaultSortColumn = '',
    string $defaultSortOrder = 'asc'
) {
    $filterValue = $_GET["filter_{$reportType}"] ?? '';
    $currentSortColumn = $_GET["sort_{$reportType}"] ?? $defaultSortColumn;
    $currentSortOrder = $_GET["order_{$reportType}"] ?? $defaultSortOrder;
    $nextSortOrder = $currentSortOrder === 'asc' ? 'desc' : 'asc';

    // Pobranie danych z filtrowaniem
    $data = $dataCallback($filterValue, $currentSortColumn, $currentSortOrder);

    echo "<h2>{$title}</h2>";

    // Formularz filtrowania
    echo "<form method='GET'>";
    echo "<input type='hidden' name='sort_{$reportType}' value='{$currentSortColumn}'>";
    echo "<input type='hidden' name='order_{$reportType}' value='{$currentSortOrder}'>";
    echo "<label for='filter_{$reportType}'>Filtr:</label>";
    echo "<input type='text' id='filter_{$reportType}' name='filter_{$reportType}' value='{$filterValue}'>";
    echo "<button type='submit'>Filtruj</button>";
    echo "</form>";

    // Renderowanie tabeli
    if (empty($data)) {
        echo "<p>Brak danych do wyświetlenia.</p>";
        return;
    }

    echo "<table>";
    echo "<thead>";
    echo "<tr>";
    foreach ($columns as $column) {
        $sortUrl = "?sort_{$reportType}={$column}&order_{$reportType}={$nextSortOrder}&filter_{$reportType}={$filterValue}";
        echo "<th><a href='{$sortUrl}'>{$column}</a></th>";
    }
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>{$cell}</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
}

// Generowanie raportów z filtrowaniem
renderTableWithFilter(
    "Nadpłaty na koncie klienta",
    'overpayments',
    function ($filter, $sortColumn, $sortOrder) {
        return ReportService::getOverpayments($filter, $sortColumn, $sortOrder);
    },
    ['client_name', 'invoice_number', 'overpayment'],
    'client_name',
    'asc'
);

renderTableWithFilter(
    "Niedopłaty za faktury",
    'underpayments',
    function ($filter, $sortColumn, $sortOrder) {
        return ReportService::getUnderpayments($filter, $sortColumn, $sortOrder);
    },
    ['client_name', 'invoice_number', 'underpayment'],
    'underpayment',
    'desc'
);

renderTableWithFilter(
    "Nierozliczone faktury po terminie płatności",
    'overdueInvoices',
    function ($filter, $sortColumn, $sortOrder) {
        return ReportService::getOverdueInvoices($filter, $sortColumn, $sortOrder);
    },
    ['client_name', 'invoice_number', 'due_date', 'unpaid_amount'],
    'due_date',
    'desc'
);

echo "</body></html>";