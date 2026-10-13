<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Models\Task;
final class DashboardController extends Controller
{
    public function __construct(private readonly Task $tasks) {}
    public function index(array $user): array { return ['stats'=>$this->tasks->statistics($user['role']==='manager'?null:(int)$user['id'])]; }
}
