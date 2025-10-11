<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;
use PDO;

final class InvoiceRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getPdo();
    }

    public function listRecent(int $limit = 50): array
    {
        $sql = 'SELECT id, invoice_number AS invoiceNumber, job_id AS jobId, total_amount AS totalAmount, status, created_at AS createdAt FROM invoices ORDER BY created_at DESC LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
