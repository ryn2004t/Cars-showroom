<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Connection;
use PDO;

final class AnalyticsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getPdo();
    }

    public function getSummary(): array
    {
        $jobsTotal = (int)$this->pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
        $jobsCompleted = (int)$this->pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'completed'")->fetchColumn();
        $revenueTotal = (float)$this->pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE status = 'paid'")->fetchColumn();
        $avgRating = (float)$this->pdo->query("SELECT COALESCE(AVG(rating),0) FROM reviews")->fetchColumn();

        return [
            'jobsTotal' => $jobsTotal,
            'jobsCompleted' => $jobsCompleted,
            'revenueTotal' => $revenueTotal,
            'avgRating' => round($avgRating, 2),
        ];
    }
}
