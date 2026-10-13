<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuthService;
use DomainException;
final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth, private readonly Session $session) {}
    public function login(Request $request): never
    {
        $user = $this->auth->authenticate(strtolower($request->text('email',190)), $request->input('password'));
        $this->session->login((int)$user['id']);
        $this->redirect();
    }
    public function register(Request $request, ?array $user): never
    {
        if ($user) throw new DomainException('Sign out before creating another account.');
        $this->auth->register($request->text('name',100),strtolower($request->text('email',190)),$request->input('password'),$request->input('password_confirmation'));
        $this->session->rotateToken();
        $this->session->flash('Account created successfully. Sign in with your email and password.');
        $this->redirect('login');
    }
    public function logout(): never { $this->session->logout(); $this->redirect('login'); }
    public function form(Request $request): array
    {
        return ['oldName'=>is_string($request->input('name'))?$request->input('name'):'', 'oldEmail'=>is_string($request->input('email'))?$request->input('email'):''];
    }
}
