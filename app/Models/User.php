<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class User
{
    public function __construct(private readonly Database $db) {}
    public function find(int $id): ?array { return $this->db->query('SELECT id,name,role FROM users WHERE id=?', [$id])->fetch() ?: null; }
    public function byEmail(string $email): ?array { return $this->db->query('SELECT * FROM users WHERE email=?', [$email])->fetch() ?: null; }
    public function employees(): array { return $this->db->query("SELECT id,name FROM users WHERE role='employee' ORDER BY name")->fetchAll(); }
    public function isEmployee(int $id): bool { return (bool)$this->db->query("SELECT id FROM users WHERE id=? AND role='employee'", [$id])->fetch(); }
    public function create(string $name, string $email, string $hash, string $role): int
    {
        $this->db->query('INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,?)', [$name,$email,$hash,$role]);
        return $this->db->lastId();
    }
}
