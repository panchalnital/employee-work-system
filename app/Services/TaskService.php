<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use App\Core\Validator;
use App\Models\Task;
use App\Models\User;
use DomainException;
final class TaskService
{
    public function __construct(private readonly Database $db, private readonly Task $tasks, private readonly User $users) {}
    public function allocate(array $actor, int $employee, string $title, string $description, string $priority, string $due): void
    {
        if ($actor['role'] !== 'manager') throw new DomainException('Manager access required.');
        if (!$this->users->isEmployee($employee)) throw new DomainException('Choose an employee.');
        Validator::date($due);
        if ($due < date('Y-m-d')) throw new DomainException('Deadline must be today or later.');
        if (!in_array($priority,['low','medium','high'],true)) throw new DomainException('Invalid priority.');
        $this->db->transaction(function () use ($actor,$employee,$title,$description,$priority,$due): void {
            $id = $this->tasks->create($employee,(int)$actor['id'],$title,$description,$priority,$due);
            $this->tasks->history($id,(int)$actor['id'],null,'todo');
        });
    }
    public function changeStatus(array $actor, int $id, int $version, string $status): void
    {
        if (!in_array($status,['todo','in_progress','pending','completed'],true)) throw new DomainException('Invalid status.');
        $this->db->transaction(function () use ($actor,$id,$version,$status): void {
            $task = $this->tasks->lock($id);
            if (!$task || ($actor['role'] !== 'manager' && (int)$task['employee_id'] !== (int)$actor['id'])) throw new DomainException('Task unavailable.');
            if ((int)$task['version'] !== $version) throw new DomainException('This task changed. Reload before updating.');
            if ($task['status'] === 'completed' && $status !== 'completed' && $actor['role'] !== 'manager') throw new DomainException('Ask a manager to reopen completed work.');
            if ($task['status'] !== $status) {
                $this->tasks->updateStatus($id,$status);
                $this->tasks->history($id,(int)$actor['id'],$task['status'],$status);
            }
        });
    }
}
