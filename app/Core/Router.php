<?php
declare(strict_types=1);
namespace App\Core;
use App\Models\User;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\TaskController;
use App\Controllers\DailyUpdateController;
use App\Controllers\ReportController;
use DomainException;
final class Router
{
    public function __construct(
        private readonly Session $session, private readonly User $users, private readonly View $view,
        private readonly AuthController $auth, private readonly DashboardController $dashboard,
        private readonly TaskController $tasks, private readonly DailyUpdateController $updates,
        private readonly ReportController $reports
    ) {}
    public function dispatch(Request $request): void
    {
        if (!in_array($request->method,['GET','POST'],true)) {
            header('Allow: GET, POST'); http_response_code(405); echo 'Method not allowed.'; return;
        }
        $id = $this->session->userId();
        $user = $id === null ? null : $this->users->find($id);
        $page = $request->query('page','dashboard');
        $error = null;
        if ($request->method === 'POST') {
            try {
                $this->session->validateCsrf($request->input('csrf'));
                $action = $request->text('action',30);
                // Map failed forms back to the page that owns the action.
                $page = match ($action) {
                    'register'=>'register','login'=>'login','create_task','status'=>'tasks','daily'=>'updates','logout'=>'dashboard',
                    default=>throw new DomainException('Unknown action.')
                };
                if ($action === 'register') $this->auth->register($request,$user);
                if ($action === 'login') {
                    if ($user) throw new DomainException('Sign out before signing in again.');
                    $this->auth->login($request);
                }
                if (!$user) throw new DomainException('Please sign in.');
                match ($action) {
                    'logout'=>$this->auth->logout(),
                    'create_task'=>$this->tasks->store($request,$user),
                    'status'=>$this->tasks->status($request,$user),
                    'daily'=>$this->updates->store($request,$user),
                    default=>throw new DomainException('Unknown action.')
                };
            } catch (DomainException $exception) { $error = $exception->getMessage(); http_response_code(422); }
        }
        if (!$user && !in_array($page,['login','register'],true)) $page = 'login';
        if ($user && in_array($page,['login','register'],true)) $page = 'dashboard';
        if (!in_array($page,['login','register','dashboard','tasks','updates','reports'],true)) { http_response_code(404); echo 'Page not found.'; return; }
        if ($page === 'reports' && $user['role'] !== 'manager') { http_response_code(403); echo 'Manager access required.'; return; }
        try {
            $data = match ($page) {
                'login','register'=>$this->auth->form($request),
                'dashboard'=>$this->dashboard->index($user),
                'tasks'=>$this->tasks->index($request,$user),
                'updates'=>$this->updates->index($request,$user),
                'reports'=>$this->reports->index($request,$user)
            };
        } catch (DomainException $exception) {
            http_response_code(422); echo e($exception->getMessage()); return;
        }
        $this->view->render($page, array_merge($data, [
            'user'=>$user, 'manager'=>$user !== null && $user['role']==='manager',
            'today'=>date('Y-m-d'), 'error'=>$error, 'flash'=>$this->session->takeFlash()
        ]));
    }
}
