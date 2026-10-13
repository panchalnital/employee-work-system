<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\DailyUpdate;
use DomainException;
final class DailyUpdateController extends Controller
{
    public function __construct(private readonly DailyUpdate $updates, private readonly Session $session) {}
    public function index(Request $request, array $user): array
    {
        $employee = $user['role']==='manager'?null:(int)$user['id'];
        $rows = $this->updates->listing($employee,$request->offset());
        return ['daily'=>$employee===null?null:$this->updates->today($employee),'updates'=>array_slice($rows,0,50),'more'=>count($rows)>50,'offset'=>$request->offset()];
    }
    public function store(Request $request, array $user): never
    {
        if ($user['role'] !== 'employee') throw new DomainException('Employee access required.');
        $this->updates->saveToday((int)$user['id'],$request->text('accomplished',5000),$request->text('todo',5000,false),$request->text('pending',5000,false),$request->text('blockers',5000,false));
        $this->session->flash('Saved successfully.'); $this->redirect('updates');
    }
}
