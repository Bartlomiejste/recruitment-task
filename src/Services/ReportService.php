<?php

namespace App\Services;

use App\Database;

class ReportService
{
    private static function applyFilter(string $filter, string $column): string
    {
        return $filter ? " AND {$column} LIKE :filter " : '';
    }

    public static function getOverpayments(string $filter = '', string $sortColumn = 'overpayment', string $sortOrder = 'asc'): array
    {
        $pdo = Database::getConnection();

        $allowedColumns = ['client_name', 'invoice_number', 'overpayment'];
        $sortColumn = in_array($sortColumn, $allowedColumns) ? $sortColumn : 'overpayment';
        $sortOrder = $sortOrder === 'desc' ? 'desc' : 'asc';

        $filterCondition = self::applyFilter($filter, 'c.name');

        $sql = "
            SELECT 
                c.name AS client_name, 
                i.number AS invoice_number, 
                SUM(p.amount) - i.total AS overpayment
            FROM 
                payments p
            JOIN 
                invoices i ON p.invoice_id = i.id
            JOIN 
                clients c ON i.client_id = c.id
            GROUP BY 
                i.id
            HAVING 
                overpayment > 0
                {$filterCondition}
            ORDER BY 
                {$sortColumn} {$sortOrder};
        ";

        $stmt = $pdo->prepare($sql);
        if ($filter) {
            $stmt->bindValue(':filter', "%{$filter}%");
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getUnderpayments(string $filter = '', string $sortColumn = 'underpayment', string $sortOrder = 'asc'): array
    {
        $pdo = Database::getConnection();

        $allowedColumns = ['client_name', 'invoice_number', 'underpayment'];
        $sortColumn = in_array($sortColumn, $allowedColumns) ? $sortColumn : 'underpayment';
        $sortOrder = $sortOrder === 'desc' ? 'desc' : 'asc';

        $filterCondition = self::applyFilter($filter, 'c.name');

        $sql = "
            SELECT 
                c.name AS client_name, 
                i.number AS invoice_number, 
                i.total - COALESCE(SUM(p.amount), 0) AS underpayment
            FROM 
                invoices i
            LEFT JOIN 
                payments p ON i.id = p.invoice_id
            JOIN 
                clients c ON i.client_id = c.id
            GROUP BY 
                i.id
            HAVING 
                underpayment > 0
                {$filterCondition}
            ORDER BY 
                {$sortColumn} {$sortOrder};
        ";

        $stmt = $pdo->prepare($sql);
        if ($filter) {
            $stmt->bindValue(':filter', "%{$filter}%");
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function getOverdueInvoices(string $filter = '', string $sortColumn = 'due_date', string $sortOrder = 'asc'): array
    {
        $pdo = Database::getConnection();

        $allowedColumns = ['client_name', 'invoice_number', 'due_date', 'unpaid_amount'];
        $sortColumn = in_array($sortColumn, $allowedColumns) ? $sortColumn : 'due_date';
        $sortOrder = $sortOrder === 'desc' ? 'desc' : 'asc';

        $filterCondition = self::applyFilter($filter, 'c.name');

        $sql = "
            SELECT 
                c.name AS client_name, 
                i.number AS invoice_number, 
                i.due_date, 
                i.total - COALESCE(SUM(p.amount), 0) AS unpaid_amount
            FROM 
                invoices i
            LEFT JOIN 
                payments p ON i.id = p.invoice_id
            JOIN 
                clients c ON i.client_id = c.id
            WHERE 
                i.due_date < CURDATE()
                {$filterCondition}
            GROUP BY 
                i.id
            HAVING 
                unpaid_amount > 0
            ORDER BY 
                {$sortColumn} {$sortOrder};
        ";

        $stmt = $pdo->prepare($sql);
        if ($filter) {
            $stmt->bindValue(':filter', "%{$filter}%");
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }
}