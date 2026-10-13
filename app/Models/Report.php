<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class Report
{
    public function __construct(private readonly Database $db) {}
    public function performance(string $from, string $to): array
    {
        return $this->db->query("SELECT u.name,COUNT(t.id) total,
        COALESCE(SUM(t.status='completed' AND DATE(t.completed_at)<=?),0) completed,
        COALESCE(SUM(t.status='completed' AND DATE(t.completed_at)<=t.due_date AND DATE(t.completed_at)<=?),0) on_time
        FROM users u LEFT JOIN tasks t ON t.employee_id=u.id AND t.due_date BETWEEN ? AND ? AND t.due_date<?
        WHERE u.role='employee' GROUP BY u.id,u.name ORDER BY u.name", [$to,$to,$from,$to,date('Y-m-d')])->fetchAll();
    }
}
