<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;
use PDO;

final class JobRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getPdo();
    }

    /**
     * Returns latest jobs for a provider, limited.
     * In absence of schema specifics, returns generic fields.
     */
    public function listRecent(int $limit = 50): array
    {
        $sql = 'SELECT id, title, scheduled_at AS scheduledAt, status, customer_id AS customerId FROM jobs ORDER BY scheduled_at DESC LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
