<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
final class TaskController extends Controller
{
    public function __construct(private readonly Task $tasks, private readonly User $users, private readonly TaskService $service, private readonly Session $session) {}
    public function index(Request $request, array $user): array
    {
        $status = $request->query('status'); $offset = $request->offset();
        $rows = $this->tasks->listing($user['role']==='manager'?null:(int)$user['id'],$status,$offset);
        return ['status'=>$status,'offset'=>$offset,'tasks'=>array_slice($rows,0,50),'more'=>count($rows)>50,'employees'=>$user['role']==='manager'?$this->users->employees():[]];
    }
    public function store(Request $request, array $user): never
    {
        $this->service->allocate($user,$request->integer('employee_id'),$request->text('title'),$request->text('description',5000,false),$request->text('priority',10),$request->text('due_date',10));
        $this->session->flash('Saved successfully.'); $this->redirect('tasks');
    }
    public function status(Request $request, array $user): never
    {
        $this->service->changeStatus($user,$request->integer('task_id'),$request->integer('version'),$request->text('status',20));
        $this->session->flash('Saved successfully.'); $this->redirect('tasks');
    }
}
