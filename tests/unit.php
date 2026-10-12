<?php
require __DIR__.'/../app/bootstrap.php';
function check(bool $ok,string $name): void { if(!$ok) throw new RuntimeException($name); echo "PASS: $name\n"; }
check(score(0,0,0)===null,'No tasks is N/A');
check(score(10,8,6)===72.0,'Weighted score');
check(score(3,3,3)===100.0,'Perfect delivery');
check(score(2,1,0)===30.0,'Late completion gets partial credit');
check(validDate('2024-02-29')==='2024-02-29','Leap day');
try { validDate('2025-02-29'); throw new RuntimeException('Invalid date accepted'); } catch(DomainException $e) { echo "PASS: invalid date rejected\n"; }
check(e('<script>')==='&lt;script&gt;','HTML escaping');
echo "All unit checks passed.\n";
