<?php
declare(strict_types=1);
namespace App\Core;
use DateTimeImmutable;
use DomainException;
final class Validator
{
    public static function date(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) throw new DomainException('Invalid date.');
        return $value;
    }
    public static function password(mixed $value): string
    {
        if (!is_string($value) || strlen($value) < 12 || strlen($value) > 72 || str_contains($value, "\0")) throw new DomainException('Password must be 12–72 bytes and contain no null characters.');
        return $value;
    }
}
