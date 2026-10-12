<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/actions.php';
set_exception_handler(function (Throwable $e) {
    error_log((string)$e);
    http_response_code(500);
    echo 'Unable to process request. Please try again.';
});
$user = isset($_SESSION['user_id']) ? query('SELECT id,name,role FROM users WHERE id=?', [$_SESSION['user_id']])->fetch() : null;
$user = $user ?: null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        handlePost($user);
    } catch (DomainException $e) {
        if (db()->inTransaction()) db()->rollBack();
        $error = $e->getMessage();
    }
}
$page = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'dashboard';
if (!$user && !in_array($page, ['login', 'register'], true)) $page = 'login';
if ($user && in_array($page, ['login', 'register'], true)) redirect();
if (!in_array($page, ['login', 'register', 'dashboard', 'tasks', 'updates', 'reports'], true)) {
    http_response_code(404);
    exit('Page not found.');
}
if ($user && $page === 'reports' && $user['role'] !== 'manager') {
    http_response_code(403);
    exit('Manager access required.');
}
$manager = $user && $user['role'] === 'manager';
$employees = $manager ? query("SELECT id,name FROM users WHERE role='employee' ORDER BY name")->fetchAll() : [];
$today = date('Y-m-d');
$reports = [];
if ($page === 'reports') {
    try {
        $from = validDate(is_string($_GET['from'] ?? null) ? $_GET['from'] : date('Y-m-01'));
        $to = validDate(is_string($_GET['to'] ?? null) ? $_GET['to'] : $today);
        if ($from > $to) throw new DomainException('Start date must precede end date.');
    } catch (DomainException $e) {
        http_response_code(422);
        exit(e($e->getMessage()));
    }
    // Evaluate matured deadlines only; completion is measured as of selected end date.
    $reports = query("SELECT u.name,COUNT(t.id) total,
 COALESCE(SUM(t.status='completed' AND DATE(t.completed_at)<=?),0) completed,
 COALESCE(SUM(t.status='completed' AND DATE(t.completed_at)<=t.due_date AND DATE(t.completed_at)<=?),0) on_time
 FROM users u LEFT JOIN tasks t ON t.employee_id=u.id AND t.due_date BETWEEN ? AND ? AND t.due_date<?
 WHERE u.role='employee' GROUP BY u.id,u.name ORDER BY u.name", [$to, $to, $from, $to, $today])->fetchAll();
    if (isset($_GET['csv'])) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="performance.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Employee', 'Due tasks', 'Completed', 'On time', 'Score'], ',', '"', '');
        foreach ($reports as $r) {
            $name = preg_match('/^[=+@\-\t\r\n]/', $r['name']) ? "'" . $r['name'] : $r['name'];
            fputcsv($out, [$name, $r['total'], $r['completed'], $r['on_time'], score((int)$r['total'], (int)$r['completed'], (int)$r['on_time']) ?? 'N/A'], ',', '"', '');
        }
        exit;
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Workday • Employee work updates</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header><a class="brand" href="?page=dashboard">▦ Workday</a><span>Work updates & performance</span><?php if ($user): ?><div><?= e($user['name']) ?> · <?= e($user['role']) ?></div>
            <form method="post"><?= csrf() ?><input type="hidden" name="action" value="logout"><button class="secondary">Sign out</button></form><?php endif ?>
    </header>
    <?php if ($user): ?><nav><?php foreach (['dashboard' => 'Overview', 'tasks' => 'Tasks', 'updates' => 'Daily updates'] + ($manager ? ['reports' => 'Performance'] : []) as $key => $label): ?><a class="<?= $page === $key ? 'active' : '' ?>" href="?page=<?= $key ?>"><?= $label ?></a><?php endforeach ?></nav><?php endif ?>
    <main><?php if ($error): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif ?><?php if (isset($_SESSION['flash'])): ?><p class="notice"><?= e($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']);
                                                                                                                                                                                endif ?>
        <?php if ($page === 'login'): ?>
            <section class="login">
                <p class="eyebrow">YOUR TEAM, IN FOCUS</p>
                <h1>Welcome back.</h1>
                <p>Sign in to keep your work moving.</p>
                <form method="post"><?= csrf() ?><input type="hidden" name="action" value="login"><label>Email<input type="email" name="email" maxlength="190" autocomplete="username" required></label><label>Password<input type="password" name="password" maxlength="200" autocomplete="current-password" required></label><button>Sign in</button></form>
                <p>New employee? <a href="?page=register">Create an account</a></p>
            </section>
        <?php elseif ($page === 'register'): ?>
            <section class="login">
                <p class="eyebrow">JOIN YOUR TEAM</p>
                <h1>Create an account.</h1>
                <p>Register as an employee to track tasks and share daily updates.</p>
                <form method="post" action="?page=register">
                    <?= csrf() ?><input type="hidden" name="action" value="register">
                    <label>Full name<input name="name" maxlength="100" autocomplete="name" value="<?= e(is_string($_POST['name'] ?? null) ? $_POST['name'] : '') ?>" required></label>
                    <label>Email<input type="email" name="email" maxlength="190" autocomplete="username" value="<?= e(is_string($_POST['email'] ?? null) ? $_POST['email'] : '') ?>" required></label>
                    <label>Password<input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="password-help" required></label>
                    <p id="password-help">Use 12–72 characters for an ASCII password. Non-ASCII characters may use multiple bytes; the limit is 72 bytes.</p>
                    <label>Confirm password<input type="password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required></label>
                    <button>Create employee account</button>
                </form>
                <p>Already registered? <a href="?page=login">Sign in</a></p>
            </section>
        <?php elseif ($page === 'dashboard'):
            $where = $manager ? '' : ' WHERE employee_id=?';
            $params = $manager ? [] : [$user['id']];
            $stats = query("SELECT COUNT(*) total,COALESCE(SUM(status='completed'),0) completed,COALESCE(SUM(status='pending'),0) pending,COALESCE(SUM(status<>'completed' AND due_date<CURDATE()),0) overdue FROM tasks" . $where, $params)->fetch(); ?>
            <p class="eyebrow"><?= e(date('l, d F Y')) ?></p>
            <h1><?= $manager ? 'Your team at a glance.' : 'Make today count.' ?></h1>
            <p>Track progress, surface blockers, and close the loop.</p>
            <div class="stats"><?php foreach ($stats as $label => $value): ?><section><span><?= e(ucfirst($label)) ?></span><strong><?= e($value) ?></strong></section><?php endforeach ?></div>
            <section>
                <h2>What needs attention</h2>
                <p>Overdue means the deadline has passed and the task is still open. Tasks due today remain on time until the end of the day.</p><a class="button" href="?page=tasks&status=overdue">View overdue tasks</a>
            </section>
        <?php elseif ($page === 'tasks'):
            $status = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
            $clauses = [];
            $params = [];
            if (!$manager) {
                $clauses[] = 't.employee_id=?';
                $params[] = $user['id'];
            }
            if ($status === 'overdue') $clauses[] = "t.status<>'completed' AND t.due_date<CURDATE()";
            elseif (in_array($status, ['todo', 'in_progress', 'pending', 'completed'], true)) {
                $clauses[] = 't.status=?';
                $params[] = $status;
            }
            $offset = max(0, (int)($_GET['offset'] ?? 0));
            $tasks = query('SELECT t.*,u.name FROM tasks t JOIN users u ON u.id=t.employee_id' . ($clauses ? ' WHERE ' . implode(' AND ', $clauses) : '') . " ORDER BY t.due_date,t.id LIMIT 51 OFFSET $offset", $params)->fetchAll();
            $more = count($tasks) > 50;
            $tasks = array_slice($tasks, 0, 50); ?>
            <h1>Tasks</h1>
            <p>Clear ownership. Visible progress.</p>
            <?php if ($manager): ?><details>
                    <summary>+ Allocate a task</summary>
                    <form method="post" class="grid"><?= csrf() ?><input type="hidden" name="action" value="create_task"><label>Task title<input name="title" maxlength="200" required></label><label>Employee<select name="employee_id" required>
                                <option value="">Select employee</option><?php foreach ($employees as $emp): ?><option value="<?= e($emp['id']) ?>"><?= e($emp['name']) ?></option><?php endforeach ?>
                            </select></label><label>Due date<input type="date" name="due_date" min="<?= e($today) ?>" required></label><label>Priority<select name="priority">
                                <option>medium</option>
                                <option>high</option>
                                <option>low</option>
                            </select></label><label>Description<textarea name="description" maxlength="5000"></textarea></label>
                        <div><button>Allocate task</button></div>
                    </form>
                </details><?php endif ?>
            <form method="get" class="filter"><input type="hidden" name="page" value="tasks"><label>Filter<select name="status"><?php foreach (['' => 'All tasks', 'todo' => 'To do', 'in_progress' => 'In progress', 'pending' => 'Pending', 'completed' => 'Completed', 'overdue' => 'Overdue'] as $v => $l): ?><option value="<?= $v ?>" <?= $status === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></label><button>Apply</button></form>
            <?php if (!$tasks): ?><section>No tasks match this filter.</section><?php endif ?>
            <?php foreach ($tasks as $t): ?><section class="task">
                    <div><span class="tag"><?= e($t['priority']) ?></span><?php if ($t['status'] !== 'completed' && $t['due_date'] < $today): ?><span class="overdue">Overdue</span><?php endif ?><h2><?= e($t['title']) ?></h2>
                        <p class="preserve"><?= e($t['description']) ?></p><small><?= e($t['name']) ?> · Due <?= e($t['due_date']) ?></small>
                    </div>
                    <form method="post"><?= csrf() ?><input type="hidden" name="action" value="status"><input type="hidden" name="task_id" value="<?= e($t['id']) ?>"><input type="hidden" name="version" value="<?= e($t['version']) ?>"><label>Status<select name="status"><?php foreach (['todo', 'in_progress', 'pending', 'completed'] as $s): ?><option <?= $s === $t['status'] ? 'selected' : '' ?>><?= $s ?></option><?php endforeach ?></select></label><button>Update</button></form>
                </section><?php endforeach ?>
            <div class="filter"><?php if ($offset): ?><a href="?page=tasks&status=<?= e($status) ?>&offset=<?= max(0, $offset - 50) ?>">Previous</a><?php endif ?><?php if ($more): ?><a href="?page=tasks&status=<?= e($status) ?>&offset=<?= $offset + 50 ?>">Next</a><?php endif ?></div>
        <?php elseif ($page === 'updates'): ?>
            <h1>Daily updates</h1>
            <p>Record achievements, next steps, and work that needs support.</p>
            <?php if (!$manager): $daily = query('SELECT * FROM daily_updates WHERE employee_id=? AND work_date=?', [$user['id'], $today])->fetch(); ?><section>
                    <h2>Today · <?= e($today) ?></h2>
                    <form method="post" class="grid"><?= csrf() ?><input type="hidden" name="action" value="daily"><?php foreach (['accomplished' => 'Accomplished', 'todo' => 'To do next', 'pending' => 'Pending work', 'blockers' => 'Blockers / help needed'] as $key => $label): ?><label><?= $label ?><textarea name="<?= $key ?>" maxlength="5000" <?= $key === 'accomplished' ? 'required' : '' ?>><?= e($daily[$key] ?? '') ?></textarea></label><?php endforeach ?><button>Save today's update</button></form>
                </section><?php endif ?>
            <?php $offset = max(0, (int)($_GET['offset'] ?? 0));
            $updates = query('SELECT d.*,u.name FROM daily_updates d JOIN users u ON u.id=d.employee_id' . ($manager ? '' : ' WHERE d.employee_id=?') . " ORDER BY d.work_date DESC,d.id DESC LIMIT 51 OFFSET $offset", $manager ? [] : [$user['id']])->fetchAll();
            $more = count($updates) > 50;
            $updates = array_slice($updates, 0, 50);
            if (!$updates): ?><section>No daily updates yet.</section><?php endif ?>
            <?php foreach ($updates as $d): ?><section>
                    <h2><?= e($d['name']) ?> <small><?= e($d['work_date']) ?></small></h2>
                    <div class="grid"><?php foreach (['accomplished', 'todo', 'pending', 'blockers'] as $k): ?><div><b><?= e(ucfirst($k)) ?></b>
                                <p class="preserve"><?= e($d[$k] ?: '—') ?></p>
                            </div><?php endforeach ?></div>
                </section><?php endforeach ?>
            <?php if ($offset): ?><a href="?page=updates&offset=<?= max(0, $offset - 50) ?>">Previous</a><?php endif ?> <?php if ($more): ?><a href="?page=updates&offset=<?= $offset + 50 ?>">Next</a><?php endif ?>
        <?php elseif ($page === 'reports'): ?>
            <h1>Performance overview</h1>
            <p>A transparent delivery indicator for manager review.</p>
            <form method="get" class="filter"><input type="hidden" name="page" value="reports"><label>Deadlines from<input type="date" name="from" value="<?= e($from) ?>" required></label><label>To<input type="date" name="to" value="<?= e($to) ?>" required></label><button>Apply</button><button name="csv" value="1" class="secondary">Export CSV</button></form>
            <section class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Due tasks</th>
                            <th>Completed</th>
                            <th>On time</th>
                            <th>Score / 100</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($reports as $r): ?><tr>
                                <td><?= e($r['name']) ?></td>
                                <td><?= e($r['total']) ?></td>
                                <td><?= e($r['completed']) ?></td>
                                <td><?= e($r['on_time']) ?></td>
                                <td><?= e(score((int)$r['total'], (int)$r['completed'], (int)$r['on_time']) ?? 'N/A') ?></td>
                            </tr><?php endforeach ?></tbody>
                </table>
            </section>
            <section>
                <h2>How the score works</h2>
                <p>Score = 60 × completed ÷ due tasks + 40 × on-time completions ÷ due tasks. Only deadlines in the selected range and before today count. Completions after the selected end date do not count. No eligible tasks means N/A.</p>
                <p>This measures delivery, not work quality or task difficulty. Discuss blockers and workload before drawing conclusions. Reports use current task state; reopening a task changes its score, including earlier periods.</p>
            </section>
        <?php endif ?>
    </main>
    <footer>Workday · Built for clear, accountable teamwork</footer>
</body>

</html>