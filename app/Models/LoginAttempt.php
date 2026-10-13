<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class LoginAttempt
{
    public function __construct(private readonly Database $db) {}
    public function ensure(string $key): void { $this->db->query('INSERT IGNORE INTO login_attempts(email_hash) VALUES(?)', [$key]); }
    public function lock(string $key): array { return $this->db->query('SELECT * FROM login_attempts WHERE email_hash=? FOR UPDATE', [$key])->fetch(); }
    public function fail(string $key, int $failures): void { $this->db->query('UPDATE login_attempts SET failures=?,blocked_until=? WHERE email_hash=?', [$failures>=5?0:$failures,$failures>=5?time()+900:0,$key]); }
    public function clear(string $key): void { $this->db->query('DELETE FROM login_attempts WHERE email_hash=?', [$key]); }
}
