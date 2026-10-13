<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class DailyUpdate
{
    public function __construct(private readonly Database $db) {}
    public function today(int $employee): ?array { return $this->db->query('SELECT * FROM daily_updates WHERE employee_id=? AND work_date=?', [$employee,date('Y-m-d')])->fetch() ?: null; }
    public function listing(?int $employee, int $offset): array
    {
        $offset=max(0,$offset);
        return $this->db->query('SELECT d.*,u.name FROM daily_updates d JOIN users u ON u.id=d.employee_id' . ($employee===null?'':' WHERE d.employee_id=?') . " ORDER BY d.work_date DESC,d.id DESC LIMIT 51 OFFSET $offset", $employee===null?[]:[$employee])->fetchAll();
    }
    public function saveToday(int $employee, string $accomplished, string $todo, string $pending, string $blockers): void
    {
        $this->db->query('INSERT INTO daily_updates(employee_id,work_date,accomplished,todo,pending,blockers) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE accomplished=VALUES(accomplished),todo=VALUES(todo),pending=VALUES(pending),blockers=VALUES(blockers)', [$employee,date('Y-m-d'),$accomplished,$todo,$pending,$blockers]);
    }
}
