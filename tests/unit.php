<?php
declare(strict_types=1);
// No configuration file or database is needed for these pure unit checks.
require __DIR__.'/../app/autoload.php';
require __DIR__.'/../app/helpers.php';
use App\Core\Request;
use App\Core\Validator;
use App\Services\PerformanceService;
function check(bool $ok, string $name): void { if (!$ok) throw new RuntimeException($name); echo "PASS: $name\n"; }
function rejected(callable $operation, string $name): void {
    try { $operation(); } catch (DomainException $e) { echo "PASS: $name\n"; return; }
    throw new RuntimeException($name);
}
$performance = new PerformanceService();
check($performance->score(0,0,0)===null,'No tasks is N/A');
check($performance->score(10,8,6)===72.0,'Weighted delivery score');
check($performance->score(3,3,3)===100.0,'Perfect delivery');
check($performance->score(2,1,0)===30.0,'Late completion gets partial credit');
check(Validator::date('2024-02-29')==='2024-02-29','Leap day');
rejected(fn()=>Validator::date('2025-02-29'),'Invalid date rejected');
rejected(fn()=>Validator::password('short'),'Short password rejected');
rejected(fn()=>Validator::password(str_repeat('x',73)),'Bcrypt length bound');
check(Validator::password('  SpacesMatter123  ')==='  SpacesMatter123  ','Passwords preserve spaces');
rejected(fn()=>Validator::password("123456789012\0"),'Null byte rejected');
$request = new Request(['offset'=>'-10'],['name'=>'  Nital  ','id'=>'2'],'POST');
check($request->text('name',100)==='Nital','Text fields trimmed');
check($request->integer('id')===2,'Positive ID accepted');
check($request->offset()===0,'Negative pagination offset clamped');
rejected(fn()=>(new Request([],['name'=>['bad']],'POST'))->text('name'),'Array input rejected');
check(e('<script>')==='&lt;script&gt;','HTML escaping');
echo "All unit checks passed.\n";
