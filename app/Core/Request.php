<?php
declare(strict_types=1);
namespace App\Core;
use DomainException;
final class Request
{
    public function __construct(private readonly array $get, private readonly array $post, public readonly string $method) {}
    public function input(string $key): mixed { return $this->post[$key] ?? null; }
    public function text(string $key, int $max = 200, bool $required = true): string
    {
        $value = $this->input($key) ?? '';
        if (!is_string($value)) throw new DomainException('Invalid input.');
        $value = trim($value);
        if (($required && $value === '') || strlen($value) > $max) throw new DomainException("Invalid $key (maximum $max bytes).");
        return $value;
    }
    public function query(string $key, string $default = ''): string { return is_string($this->get[$key] ?? null) ? $this->get[$key] : $default; }
    public function hasQuery(string $key): bool { return isset($this->get[$key]); }
    public function integer(string $key): int
    {
        $value = filter_var($this->input($key), FILTER_VALIDATE_INT);
        if ($value === false || $value < 1) throw new DomainException("Invalid $key.");
        return $value;
    }
    public function offset(): int { return max(0, (int)$this->query('offset', '0')); }
}
