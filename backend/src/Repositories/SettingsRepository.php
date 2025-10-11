<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;
use PDO;

final class SettingsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getPdo();
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query('SELECT `key`, `value` FROM settings');
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[$row['key']] = $row['value'];
        }
        return $out;
    }

    /** @param array<string,string> $map */
    public function updateAll(array $map): void
    {
        $sql = 'INSERT INTO settings (`key`, `value`) VALUES (:key, :value) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)';
        $stmt = $this->pdo->prepare($sql);
        foreach ($map as $key => $value) {
            $stmt->execute(['key' => $key, 'value' => $value]);
        }
    }
}
