<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;
use PDO;

final class PromotionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getPdo();
    }

    public function listActive(): array
    {
        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $sql = 'SELECT id, name, description, discount_percent AS discountPercent, active_from AS activeFrom, active_to AS activeTo FROM promotions WHERE (active_from IS NULL OR active_from <= :now) AND (active_to IS NULL OR active_to >= :now) ORDER BY active_from DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['now' => $now]);
        return $stmt->fetchAll();
    }
}
