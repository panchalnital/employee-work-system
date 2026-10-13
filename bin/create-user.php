<?php
require __DIR__.'/../app/bootstrap.php';
if ($argc!==4 || !in_array($argv[3],['manager','employee'],true) || !filter_var($argv[2],FILTER_VALIDATE_EMAIL) || trim($argv[1])==='' || strlen($argv[1])>100 || strlen($argv[2])>190) {
 fwrite(STDERR,"Usage: php bin/create-user.php 'Full Name' email@example.com manager|employee\nPassword is read from stdin.\n"); exit(1);
}
$password=rtrim(stream_get_contents(STDIN),"\r\n");
if (strlen($password)<12 || strlen($password)>72 || str_contains($password,"\0")) { fwrite(STDERR,"Password must be 12–72 bytes.\n"); exit(1); }
$users->create(trim($argv[1]),strtolower($argv[2]),password_hash($password,PASSWORD_BCRYPT),$argv[3]);
echo "User created.\n";
