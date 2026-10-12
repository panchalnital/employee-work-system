<?php
function handlePost(?array $user): void
{
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) throw new DomainException('Your session changed. Reload and try again.');
    $action = field('action', 30);
    if ($action === 'register') {
        if ($user) throw new DomainException('Sign out before creating another account.');
        $name = field('name', 100);
        $email = strtolower(field('email', 190));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('Enter a valid email address.');
        $password = $_POST['password'] ?? null;
        $confirmation = $_POST['password_confirmation'] ?? null;
        if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")) throw new DomainException('Password must be 12–72 bytes and contain no null characters.');
        if (!is_string($confirmation) || $password !== $confirmation) throw new DomainException('Passwords do not match.');
        try {
            // Public registration never accepts a role supplied by the browser.
            query("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'employee')", [$name, $email, password_hash($password, PASSWORD_BCRYPT)]);
        } catch (PDOException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) throw new DomainException('This email is already registered. Please sign in.');
            throw $e;
        }
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        $_SESSION['flash'] = 'Account created successfully. Sign in with your email and password.';
        redirect('login');
    }
    if ($action === 'login') {
        $email = strtolower(field('email', 190));
        $password = $_POST['password'] ?? null;
        if (!is_string($password) || $password === '' || strlen($password) > 200 || str_contains($password, "\0")) throw new DomainException('Invalid email or password.');
        $key = hash('sha256', $email);
        query('INSERT IGNORE INTO login_attempts(email_hash) VALUES(?)', [$key]);
        db()->beginTransaction();
        $attempt = query('SELECT * FROM login_attempts WHERE email_hash=? FOR UPDATE', [$key])->fetch();
        if ((int)$attempt['blocked_until'] > time()) {
            db()->commit();
            throw new DomainException('Too many attempts. Try again in 15 minutes.');
        }
        $u = query('SELECT * FROM users WHERE email=?', [$email])->fetch();
        if (!$u || !password_verify($password, $u['password_hash'])) {
            $fail = (int)$attempt['failures'] + 1;
            query('UPDATE login_attempts SET failures=?, blocked_until=? WHERE email_hash=?', [$fail >= 5 ? 0 : $fail, $fail >= 5 ? time() + 900 : 0, $key]);
            db()->commit();
            throw new DomainException('Invalid email or password.');
        }
        query('DELETE FROM login_attempts WHERE email_hash=?', [$key]);
        db()->commit();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        redirect();
    }
    if (!$user) throw new DomainException('Please sign in.');
    if ($action === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        redirect('login');
    }
    if ($action === 'create_task') {
        if ($user['role'] !== 'manager') throw new DomainException('Manager access required.');
        $employee = filter_var($_POST['employee_id'] ?? '', FILTER_VALIDATE_INT);
        if (!$employee || !query("SELECT id FROM users WHERE id=? AND role='employee'", [$employee])->fetch()) throw new DomainException('Choose an employee.');
        $title = field('title');
        $description = field('description', 5000, false);
        $due = validDate(field('due_date', 10));
        if ($due < date('Y-m-d')) throw new DomainException('Deadline must be today or later.');
        $priority = field('priority', 10);
        if (!in_array($priority, ['low', 'medium', 'high'], true)) throw new DomainException('Invalid priority.');
        db()->beginTransaction();
        query('INSERT INTO tasks(employee_id,manager_id,title,description,priority,due_date) VALUES(?,?,?,?,?,?)', [$employee, $user['id'], $title, $description, $priority, $due]);
        $id = db()->lastInsertId();
        query("INSERT INTO task_history(task_id,actor_id,new_status) VALUES(?,?,'todo')", [$id, $user['id']]);
        db()->commit();
    } elseif ($action === 'status') {
        $status = field('status', 20);
        if (!in_array($status, ['todo', 'in_progress', 'pending', 'completed'], true)) throw new DomainException('Invalid status.');
        db()->beginTransaction();
        $task = query('SELECT * FROM tasks WHERE id=? FOR UPDATE', [(int)($_POST['task_id'] ?? 0)])->fetch();
        if (!$task || ($user['role'] !== 'manager' && (int)$task['employee_id'] !== (int)$user['id'])) throw new DomainException('Task unavailable.');
        if ((int)$task['version'] !== (int)($_POST['version'] ?? 0)) throw new DomainException('This task changed. Reload before updating.');
        if ($task['status'] === 'completed' && $status !== 'completed' && $user['role'] !== 'manager') throw new DomainException('Ask a manager to reopen completed work.');
        if ($task['status'] !== $status) {
            query('UPDATE tasks SET status=?,completed_at=?,version=version+1 WHERE id=?', [$status, $status === 'completed' ? date('Y-m-d H:i:s') : null, $task['id']]);
            query('INSERT INTO task_history(task_id,actor_id,old_status,new_status) VALUES(?,?,?,?)', [$task['id'], $user['id'], $task['status'], $status]);
        }
        db()->commit();
    } elseif ($action === 'daily') {
        if ($user['role'] !== 'employee') throw new DomainException('Employee access required.');
        // Today only: employees cannot retroactively rewrite prior daily reports.
        query('INSERT INTO daily_updates(employee_id,work_date,accomplished,todo,pending,blockers) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE accomplished=VALUES(accomplished),todo=VALUES(todo),pending=VALUES(pending),blockers=VALUES(blockers)', [$user['id'], date('Y-m-d'), field('accomplished', 5000), field('todo', 5000, false), field('pending', 5000, false), field('blockers', 5000, false)]);
    } else {
        throw new DomainException('Unknown action.');
    }
    $_SESSION['flash'] = 'Saved successfully.';
    redirect($action === 'daily' ? 'updates' : 'tasks');
}
