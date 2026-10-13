<?php
declare(strict_types=1);
namespace App\Core;
use PDO;
use PDOStatement;
use Throwable;
final class Database
{
    private ?PDO $connection = null;
    public function __construct(private readonly array $config) {}
    private function connection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = new PDO($this->config['dsn'], $this->config['user'], $this->config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
            $this->connection->exec("SET time_zone = '" . date('P') . "'");
        }
        return $this->connection;
    }
    public function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->connection()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }
    public function lastId(): int { return (int)$this->connection()->lastInsertId(); }
    public function transaction(callable $operation): mixed
    {
        $pdo = $this->connection();
        $pdo->beginTransaction();
        try {
            $result = $operation();
            $pdo->commit();
            return $result;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }
}
