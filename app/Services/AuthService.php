<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Database;
use App\Core\Validator;
use App\Models\User;
use App\Models\LoginAttempt;
use DomainException;
use PDOException;
final class AuthService
{
    public function __construct(private readonly Database $db, private readonly User $users, private readonly LoginAttempt $attempts) {}
    public function register(string $name, string $email, mixed $password, mixed $confirmation): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('Enter a valid email address.');
        $password = Validator::password($password);
        if (!is_string($confirmation) || $password !== $confirmation) throw new DomainException('Passwords do not match.');
        try { $this->users->create($name, $email, password_hash($password, PASSWORD_BCRYPT), 'employee'); }
        catch (PDOException $error) {
            if ((int)($error->errorInfo[1]??0) === 1062) throw new DomainException('This email is already registered. Please sign in.');
            throw $error;
        }
    }
    public function authenticate(string $email, mixed $password): array
    {
        if (!is_string($password) || $password === '' || strlen($password)>200 || str_contains($password,"\0")) throw new DomainException('Invalid email or password.');
        $key = hash('sha256',$email);
        $this->attempts->ensure($key);
        // Return failure, then throw AFTER commit so failed-attempt counts persist.
        $result = $this->db->transaction(function () use ($key,$email,$password): array {
            $attempt = $this->attempts->lock($key);
            if ((int)$attempt['blocked_until'] > time()) return ['error'=>'Too many attempts. Try again in 15 minutes.'];
            $user = $this->users->byEmail($email);
            if (!$user || !password_verify($password,$user['password_hash'])) {
                $this->attempts->fail($key,(int)$attempt['failures']+1);
                return ['error'=>'Invalid email or password.'];
            }
            $this->attempts->clear($key);
            return ['user'=>$user];
        });
        if (isset($result['error'])) throw new DomainException($result['error']);
        return $result['user'];
    }
}
