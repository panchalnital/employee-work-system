<?php
declare(strict_types=1);
namespace App\Services;
final class PerformanceService
{
    public function score(int $total, int $completed, int $onTime): ?float
    {
        return $total === 0 ? null : round(100 * (0.6*$completed + 0.4*$onTime) / $total, 1);
    }
    public function evaluate(array $rows): array
    {
        foreach ($rows as &$row) $row['score'] = $this->score((int)$row['total'], (int)$row['completed'], (int)$row['on_time']);
        return $rows;
    }
}
