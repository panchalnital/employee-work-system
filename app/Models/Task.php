<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class Task
{
    public function __construct(private readonly Database $db) {}
    public function statistics(?int $employeeId): array
    {
        return $this->db->query("SELECT COUNT(*) total,COALESCE(SUM(status='completed'),0) completed,COALESCE(SUM(status='pending'),0) pending,COALESCE(SUM(status<>'completed' AND due_date<CURDATE()),0) overdue FROM tasks" . ($employeeId === null ? '' : ' WHERE employee_id=?'), $employeeId === null ? [] : [$employeeId])->fetch();
    }
    public function listing(?int $employeeId, string $status, int $offset): array
    {
        $clauses = []; $params = []; $offset = max(0,$offset);
        if ($employeeId !== null) { $clauses[] = 't.employee_id=?'; $params[] = $employeeId; }
        if ($status === 'overdue') $clauses[] = "t.status<>'completed' AND t.due_date<CURDATE()";
        elseif (in_array($status,['todo','in_progress','pending','completed'],true)) { $clauses[]='t.status=?'; $params[]=$status; }
        return $this->db->query('SELECT t.*,u.name FROM tasks t JOIN users u ON u.id=t.employee_id' . ($clauses ? ' WHERE '.implode(' AND ',$clauses) : '') . " ORDER BY t.due_date,t.id LIMIT 51 OFFSET $offset", $params)->fetchAll();
    }
    public function create(int $employee, int $manager, string $title, string $description, string $priority, string $due): int
    {
        $this->db->query('INSERT INTO tasks(employee_id,manager_id,title,description,priority,due_date) VALUES(?,?,?,?,?,?)', [$employee,$manager,$title,$description,$priority,$due]);
        return $this->db->lastId();
    }
    public function lock(int $id): ?array { return $this->db->query('SELECT * FROM tasks WHERE id=? FOR UPDATE', [$id])->fetch() ?: null; }
    public function updateStatus(int $id, string $status): void
    {
        $this->db->query('UPDATE tasks SET status=?,completed_at=?,version=version+1 WHERE id=?', [$status,$status==='completed'?date('Y-m-d H:i:s'):null,$id]);
    }
    public function history(int $id, int $actor, ?string $old, string $new): void
    {
        $this->db->query('INSERT INTO task_history(task_id,actor_id,old_status,new_status) VALUES(?,?,?,?)', [$id,$actor,$old,$new]);
    }
}
