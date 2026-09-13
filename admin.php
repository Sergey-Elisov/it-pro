<?php
session_start();

// Если уже авторизован — ведем в ЛК
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Функция для безопасной загрузки переменных из файла .env
function loadEnv($path) {
    if (!file_exists($path)) {
        die('Критическая ошибка: файл конфигурации .env не найден.');
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1], " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Загружаем .env из текущей директории
loadEnv(__DIR__ . '/.env');

// 2. Получаем настройки БД из переменных окружения
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

$error = '';


// === ОБРАБОТКА ДЕЙСТВИЙ (POST) ===
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Изменение роли
    if ($_POST['action'] === 'change_role' && isset($_POST['user_id'], $_POST['new_role'])) {
        $user_id = (int)$_POST['user_id'];
        $new_role = $_POST['new_role'];
        
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);
        $message = 'Роль пользователя успешно изменена.';
        $messageType = 'success';
    }
    
    // Ответ на обращение
    if ($_POST['action'] === 'reply_appeal' && isset($_POST['ticket_id'], $_POST['response'])) {
        $ticket_id = (int)$_POST['ticket_id'];
        $response = trim($_POST['response']);
        $status = $_POST['status'] ?? 'in_progress';
        
        $allowed_statuses = ['new', 'in_progress', 'resolved', 'closed'];
        if (!in_array($status, $allowed_statuses)) {
            $status = 'in_progress';
        }
        
        if (!empty($response)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE support_tickets 
                    SET admin_response = ?, 
                        status = ?, 
                        resolved_by_admin_id = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$response, $status, $_SESSION['user_id'], $ticket_id]);
                
                $message = '✅ Ответ отправлен, статус обновлён.';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = '❌ Ошибка: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
    
 // УДАЛЕНИЕ ПОЛЬЗОВАТЕЛЯ С АРХИВАЦИЕЙ
if ($_POST['action'] === 'delete_user' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $reason = trim($_POST['reason'] ?? 'Удалён администратором');
    
    if ($user_id === (int)$_SESSION['user_id']) {
        $message = '❌ Нельзя удалить собственную учётную запись';
        $messageType = 'error';
    } else {
        try {
            // Получаем данные пользователя
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user_to_delete) {
                throw new Exception("Пользователь не найден");
            }
            
            // 🔍 ОТЛАДКА: выводим данные пользователя
            // echo "<pre>"; print_r($user_to_delete); echo "</pre>"; exit;
            
            // Подсчёт статистики
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $courses_count = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $appeals_count = $stmt->fetchColumn();
            
            // СОХРАНЯЕМ В АРХИВ 
            $stmt = $pdo->prepare("
                INSERT INTO deleted_users 
                (username, email, password, role, is_active, created_at,
                 deleted_by, deleted_by_name, reason, courses_count, appeals_count)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_to_delete['id'],
                $user_to_delete['username'],
                $user_to_delete['email'],
                $user_to_delete['password'],
                $user_to_delete['role'],
                $user_to_delete['is_active'] ?? 1,
                $user_to_delete['created_at'] ?? date('Y-m-d H:i:s'),
                $_SESSION['user_id'],
                $current_user['username'],
                $reason,
                (int)$courses_count,
                (int)$appeals_count
            ]);
            
            // ОБНУЛЯЕМ ССЫЛКИ
            $pdo->exec("UPDATE support_tickets SET user_id = NULL WHERE user_id = $user_id");
            $pdo->exec("UPDATE support_tickets SET resolved_by_admin_id = NULL WHERE resolved_by_admin_id = $user_id");
            $pdo->exec("UPDATE courses SET teacher_id = NULL WHERE teacher_id = $user_id");
            $pdo->exec("DELETE FROM enrollments WHERE user_id = $user_id");
            
            // УДАЛЯЕМ ПОЛЬЗОВАТЕЛЯ
            $pdo->exec("DELETE FROM users WHERE id = $user_id");
            
            $message = "✅ Пользователь #{$user_id} ({$user_to_delete['username']}) удалён и архивирован";
            $messageType = 'success';
            
        } catch (Exception $e) {
            // ПОКАЗЫВАЕМ ВСЕ ОШИБКИ
            $message = '❌ Ошибка: ' . $e->getMessage();
            $messageType = 'error';
            error_log("DELETE ERROR: " . $e->getMessage());
        }
    }
}
    
    // ВОССТАНОВЛЕНИЕ ИЗ АРХИВА
    if ($_POST['action'] === 'restore_user' && isset($_POST['deleted_id'])) {
        $deleted_id = (int)$_POST['deleted_id'];
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM deleted_users WHERE id = ?");
            $stmt->execute([$deleted_id]);
            $archived = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$archived) {
                throw new Exception("Запись в архиве не найдена");
            }
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$archived['email']]);
            if ($stmt->fetch()) {
                throw new Exception("Email уже используется другим пользователем");
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $archived['username'],
                $archived['email'],
                $archived['password'],
                $archived['role'],
                $archived['is_active'],
                $archived['created_at']
            ]);
            
            $pdo->prepare("DELETE FROM deleted_users WHERE id = ?")->execute([$deleted_id]);
            
            $message = "✅ Пользователь {$archived['username']} восстановлен";
            $messageType = 'success';
            
        } catch (Exception $e) {
            $message = '❌ Ошибка: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // ОКОНЧАТЕЛЬНОЕ УДАЛЕНИЕ ИЗ АРХИВА
    if ($_POST['action'] === 'purge_user' && isset($_POST['deleted_id'])) {
        $deleted_id = (int)$_POST['deleted_id'];
        
        $stmt = $pdo->prepare("SELECT username FROM deleted_users WHERE id = ?");
        $stmt->execute([$deleted_id]);
        $archived = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $pdo->prepare("DELETE FROM deleted_users WHERE id = ?")->execute([$deleted_id]);
        
        $message = "🗑️ Запись {$archived['username']} окончательно удалена из архива";
        $messageType = 'success';
    }
    
    // Публикация курса
    if ($_POST['action'] === 'toggle_course' && isset($_POST['course_id'])) {
        $course_id = (int)$_POST['course_id'];
        $stmt = $pdo->prepare("UPDATE courses SET is_published = NOT is_published WHERE id = ?");
        $stmt->execute([$course_id]);
        $message = 'Статус курса изменён.';
        $messageType = 'success';
    }
}

// === СТАТИСТИКА ===
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'courses' => $pdo->query("SELECT COUNT(*) FROM courses WHERE is_published = 1")->fetchColumn(),
    'appeals_new' => $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'new'")->fetchColumn(),
    'appeals_total' => $pdo->query("SELECT COUNT(*) FROM support_tickets")->fetchColumn(),
    'deleted_users' => $pdo->query("SELECT COUNT(*) FROM deleted_users")->fetchColumn(),
];

// === ДАННЫЕ ДЛЯ ТАБЛИЦ ===
$users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 50")->fetchAll();
$appeals = $pdo->query("
    SELECT t.id, t.subject, t.message_text, t.admin_response, t.status, t.created_at, u.username 
    FROM support_tickets t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC LIMIT 30
")->fetchAll();
$courses = $pdo->query("SELECT id, title, is_published, created_at FROM courses ORDER BY created_at DESC LIMIT 30")->fetchAll();

// Загрузка удалённых пользователей
$deleted_users = $pdo->query("
    SELECT du.*, u.username AS deleted_by_username
    FROM deleted_users du
    LEFT JOIN users u ON du.deleted_by = u.id
    ORDER BY du.deleted_at DESC 
    LIMIT 50
")->fetchAll();

$tab = $_GET['tab'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | IT-pro</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --admin-primary: #1e40af;
            --admin-success: #16a34a;
            --admin-warning: #f59e0b;
            --admin-danger: #dc2626;
            --admin-bg: #f1f5f9;
            --admin-card: #ffffff;
            --admin-text: #0f172a;
            --admin-border: #e2e8f0;
        }
        
        .admin-wrapper { display: flex; min-height: 100vh; background: var(--admin-bg); }
        
        .admin-sidebar {
            width: 240px; background: var(--admin-card); border-right: 1px solid var(--admin-border);
            padding: 20px 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; flex-shrink: 0;
        }
        .admin-logo { padding: 0 20px 20px; border-bottom: 1px solid var(--admin-border); margin-bottom: 20px; }
        .admin-logo h2 { margin: 0; color: var(--admin-primary); font-size: 1.3rem; display: flex; align-items: center; gap: 8px; }
        
        .admin-nav { list-style: none; padding: 0; margin: 0; }
        .admin-nav li { margin: 4px 0; }
        .admin-nav a {
            display: flex; align-items: center; gap: 10px; padding: 12px 20px;
            color: var(--admin-text); text-decoration: none; font-weight: 500;
            transition: all 0.2s; border-left: 3px solid transparent;
        }
        .admin-nav a:hover, .admin-nav a.active {
            background: #eff6ff; color: var(--admin-primary); border-left-color: var(--admin-primary);
        }
        .admin-nav .badge {
            margin-left: auto; background: var(--admin-danger); color: white;
            padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 600;
        }
        
        .admin-main { flex-grow: 1; padding: 25px; overflow-y: auto; }
        .admin-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid var(--admin-border);
        }
        .admin-header h1 { margin: 0; color: var(--admin-text); font-size: 1.5rem; }
        .admin-user { display: flex; align-items: center; gap: 10px; color: var(--admin-text); font-size: 0.95rem; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--admin-card); padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--admin-border); }
        .stat-card h3 { margin: 0 0 8px; font-size: 0.9rem; color: #64748b; font-weight: 500; }
        .stat-card .value { font-size: 1.8rem; font-weight: 700; color: var(--admin-text); }
        .stat-card.new .value { color: var(--admin-danger); }
        .stat-card.published .value { color: var(--admin-success); }
        
        .admin-card { background: var(--admin-card); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--admin-border); margin-bottom: 25px; overflow: hidden; }
        .admin-card-header { padding: 15px 20px; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
        .admin-card-header h3 { margin: 0; font-size: 1.1rem; color: var(--admin-text); }
        
        .admin-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        .admin-table th, .admin-table td { padding: 12px 20px; text-align: left; border-bottom: 1px solid var(--admin-border); }
        .admin-table th { background: #f8fafc; font-weight: 600; color: #475569; font-size: 0.85rem; text-transform: uppercase; }
        .admin-table tr:hover { background: #f8fafc; }
        
        .btn-admin {
            padding: 6px 12px; border: none; border-radius: 6px; font-size: 0.85rem;
            font-weight: 500; cursor: pointer; transition: all 0.2s; text-decoration: none;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .btn-primary { background: var(--admin-primary); color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: var(--admin-success); color: white; }
        .btn-warning { background: var(--admin-warning); color: #1e293b; }
        .btn-danger { background: var(--admin-danger); color: white; }
        .btn-sm { padding: 4px 8px; font-size: 0.8rem; }
        
        select.admin-select { padding: 6px 10px; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.9rem; background: white; min-width: 120px; }
        
        .admin-message { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .admin-message.success { background: #f0fdf4; color: #166534; border-left: 4px solid var(--admin-success); }
        .admin-message.error { background: #fef2f2; color: #dc2626; border-left: 4px solid var(--admin-danger); }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 25px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--admin-border); }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b; }
        .modal-close:hover { color: var(--admin-text); }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #334155; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 10px 12px; border: 1px solid var(--admin-border);
            border-radius: 8px; font-size: 1rem; box-sizing: border-box;
        }
        .form-group textarea { min-height: 100px; resize: vertical; }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; display: inline-block; }
        .status-new { background: #fff7ed; color: #c2410c; }
        .status-in_progress { background: #eff6ff; color: #1d4ed8; }
        .status-resolved { background: #f0fdf4; color: #166534; }
        .status-published { background: #f0fdf4; color: #166534; }
        .status-draft { background: #f1f5f9; color: #64748b; }
        
        @media (max-width: 992px) {
            .admin-wrapper { flex-direction: column; }
            .admin-sidebar { width: 100%; height: auto; position: static; border-right: none; border-bottom: 1px solid var(--admin-border); }
            .admin-nav { display: flex; flex-wrap: wrap; gap: 4px; padding: 0 10px; }
            .admin-nav a { padding: 8px 12px; font-size: 0.9rem; border-left: none; border-bottom: 3px solid transparent; }
            .admin-nav a.active { border-left: none; border-bottom-color: var(--admin-primary); }
            .admin-nav .badge { display: none; }
        }
        
        @media (max-width: 600px) {
            .admin-main { padding: 15px; }
            .admin-table { font-size: 0.85rem; }
            .admin-table th, .admin-table td { padding: 8px 12px; }
            .btn-admin { padding: 4px 8px; font-size: 0.75rem; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <h2>⚙️ Админ-панель</h2>
        </div>
        <ul class="admin-nav">
            <li><a href="?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>">📊 Дашборд</a></li>
            <li><a href="?tab=users" class="<?= $tab === 'users' ? 'active' : '' ?>">👥 Пользователи</a></li>
            <li><a href="?tab=courses" class="<?= $tab === 'courses' ? 'active' : '' ?>">📚 Курсы</a></li>
            <li>
                <a href="?tab=appeals" class="<?= $tab === 'appeals' ? 'active' : '' ?>">
                    💬 Обращения
                    <?php if ($stats['appeals_new'] > 0): ?>
                        <span class="badge"><?= $stats['appeals_new'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="?tab=deleted" class="<?= $tab === 'deleted' ? 'active' : '' ?>">
                    🗑️ Удалённые
                    <?php if ($stats['deleted_users'] > 0): ?>
                        <span class="badge"><?= $stats['deleted_users'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="dashboard.php" style="color: var(--admin-danger);">← Вернуться на сайт</a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>
                <?php 
                $titles = [
                    'dashboard' => '📊 Панель управления',
                    'users'     => '👥 Управление пользователями',
                    'courses'   => '📚 Управление курсами',
                    'appeals'   => '💬 Обращения в поддержку',
                    'deleted'   => '🗑️ Удалённые пользователи'
                ];
                echo $titles[$tab] ?? 'Админ-панель';
                ?>
            </h1>
            <div class="admin-user">
                <span>👤 <?= htmlspecialchars($current_user['username']) ?></span>
                <a href="logout.php" class="btn-admin btn-danger btn-sm">Выйти</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="admin-message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- ДАШБОРД -->
        <?php if ($tab === 'dashboard'): ?>
            <div class="stats-grid">
                <div class="stat-card"><h3>Пользователи</h3><div class="value"><?= $stats['users'] ?></div></div>
                <div class="stat-card published"><h3>Опубликованные курсы</h3><div class="value"><?= $stats['courses'] ?></div></div>
                <div class="stat-card new"><h3>Новые обращения</h3><div class="value"><?= $stats['appeals_new'] ?></div></div>
                <div class="stat-card"><h3>Всего обращений</h3><div class="value"><?= $stats['appeals_total'] ?></div></div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>🔔 Последние обращения</h3>
                    <a href="?tab=appeals" class="btn-admin btn-primary btn-sm">Все обращения</a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>Пользователь</th><th>Тема</th><th>Сообщение</th><th>Статус</th><th>Дата</th><th>Действие</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($appeals, 0, 5) as $appeal): ?>
                        <tr>
                            <td>#<?= $appeal['id'] ?></td>
                            <td><?= htmlspecialchars($appeal['username']) ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($appeal['subject'], 0, 30, '…')) ?></td>
                            <td style="max-width:200px;">
                                <span title="<?= htmlspecialchars($appeal['message_text']) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($appeal['message_text'], 0, 80, '…')) ?>
                                </span>
                            </td>
                            <td><span class="status-badge status-<?= $appeal['status'] ?>"><?= $appeal['status'] ?></span></td>
                            <td><?= date('d.m.Y', strtotime($appeal['created_at'])) ?></td>
                            <td>
                                <button class="btn-admin btn-primary btn-sm" onclick="openReplyModal(<?= $appeal['id'] ?>, '<?= addslashes($appeal['subject']) ?>')">✉️ Ответить</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- ПОЛЬЗОВАТЕЛИ -->
        <?php if ($tab === 'users'): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Список пользователей (<?= count($users) ?>)</h3>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>Имя</th><th>Email</th><th>Роль</th><th>Дата регистрации</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <form method="POST" style="display:inline-flex; gap:5px; align-items:center;">
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <select name="new_role" class="admin-select" onchange="this.form.submit()">
                                        <option value="3" <?= (int)$user['role'] === 3 ? 'selected' : '' ?>>Студент</option>
                                        <option value="2" <?= (int)$user['role'] === 2 ? 'selected' : '' ?>>Преподаватель</option>
                                        <option value="1" <?= (int)$user['role'] === 1 ? 'selected' : '' ?>>Администратор</option>
                                    </select>
                                </form>
                            </td>
                            <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <a href="#" class="btn-admin btn-warning btn-sm">✏️</a>
                                <!-- КНОПКА УДАЛЕНИЯ -->
                                <button type="button" 
                                        class="btn-admin btn-danger btn-sm" 
                                        onclick="openDeleteModal(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['username'])) ?>')"
                                        title="Удалить пользователя"
                                        <?= $user['id'] == $_SESSION['user_id'] ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
                                    🗑️
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- КУРСЫ -->
        <?php if ($tab === 'courses'): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Управление курсами (<?= count($courses) ?>)</h3>
                    <a href="course_create.php" class="btn-admin btn-success btn-sm">➕ Создать курс</a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>Название</th><th>Статус</th><th>Дата</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                        <tr>
                            <td>#<?= $course['id'] ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($course['title'], 0, 50, '…')) ?></td>
                            <td>
                                <span class="status-badge status-<?= $course['is_published'] ? 'published' : 'draft' ?>">
                                    <?= $course['is_published'] ? 'Опубликован' : 'Черновик' ?>
                                </span>
                            </td>
                            <td><?= date('d.m.Y', strtotime($course['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_course">
                                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                    <button type="submit" class="btn-admin btn-<?= $course['is_published'] ? 'warning' : 'success' ?> btn-sm">
                                        <?= $course['is_published'] ? '🔒 Скрыть' : '🔓 Опубликовать' ?>
                                    </button>
                                </form>
                                <a href="course_edit.php?id=<?= $course['id'] ?>" class="btn-admin btn-primary btn-sm">✏️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- ОБРАЩЕНИЯ -->
        <?php if ($tab === 'appeals'): ?>
            <div class="admin-card">
                <div class="admin-card-header"><h3>Все обращения (<?= count($appeals) ?>)</h3></div>
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>Пользователь</th><th>Тема</th><th>Сообщение</th><th>Статус</th><th>Дата</th><th>Действие</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appeals as $appeal): ?>
                        <tr>
                            <td>#<?= $appeal['id'] ?></td>
                            <td><?= htmlspecialchars($appeal['username']) ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth($appeal['subject'], 0, 30, '…')) ?></td>
                            <td style="max-width:250px;">
                                <span title="<?= htmlspecialchars($appeal['message_text']) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($appeal['message_text'], 0, 100, '…')) ?>
                                </span>
                            </td>
                            <td><span class="status-badge status-<?= $appeal['status'] ?>"><?= $appeal['status'] ?></span></td>
                            <td><?= date('d.m.Y H:i', strtotime($appeal['created_at'])) ?></td>
                            <td>
                                <button class="btn-admin btn-primary btn-sm" onclick="openReplyModal(<?= $appeal['id'] ?>, '<?= addslashes($appeal['subject']) ?>')">✉️ Ответить</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- ВКЛАДКА УДАЛЁННЫЕ ПОЛЬЗОВАТЕЛИ -->
        <?php if ($tab === 'deleted'): ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Архив удалённых пользователей (<?= count($deleted_users) ?>)</h3>
                    <span style="font-size:0.85rem; color:#64748b;">ℹ️ Данные хранятся для восстановления и аудита</span>
                </div>
                
                <?php if (empty($deleted_users)): ?>
                    <div style="padding:40px; text-align:center; color:#64748b;">
                        <p style="font-size:1.1rem;">📭 Архив пуст</p>
                        <p style="font-size:0.9rem;">Удалённые пользователи будут отображаться здесь</p>
                    </div>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя</th>
                                <th>Email</th>
                                <th>Была роль</th>
                                <th>Курсов</th>
                                <th>Обращений</th>
                                <th>Удалил</th>
                                <th>Причина</th>
                                <th>Дата удаления</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deleted_users as $du): ?>
                            <tr>
                                <td>#<?= $du['id'] ?></td>
                                <td><?= htmlspecialchars($du['username']) ?></td>
                                <td><?= htmlspecialchars($du['email']) ?></td>
                                <td>
                                    <?php 
                                    $role_names = [1 => 'Админ', 2 => 'Преподаватель', 3 => 'Студент'];
                                    $role_name = $role_names[$du['role']] ?? 'Роль ' . $du['role'];
                                    ?>
                                    <span class="status-badge status-<?= $du['role'] == 1 ? 'resolved' : 'in_progress' ?>">
                                        <?= $role_name ?>
                                    </span>
                                </td>
                                <td><?= $du['courses_count'] ?></td>
                                <td><?= $du['appeals_count'] ?></td>
                                <td><?= htmlspecialchars($du['deleted_by_username'] ?? 'ID ' . $du['deleted_by']) ?></td>
                                <td style="max-width:200px;" title="<?= htmlspecialchars($du['reason']) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($du['reason'] ?? '—', 0, 40, '…')) ?>
                                </td>
                                <td><?= date('d.m.Y H:i', strtotime($du['deleted_at'])) ?></td>
                                <td style="white-space:nowrap;">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Восстановить пользователя <?= htmlspecialchars(addslashes($du['username'])) ?>?')">
                                        <input type="hidden" name="action" value="restore_user">
                                        <input type="hidden" name="deleted_id" value="<?= $du['id'] ?>">
                                        <button type="submit" class="btn-admin btn-success btn-sm" title="Восстановить">♻️</button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('⚠️ ОКОНЧАТЕЛЬНО удалить запись из архива?\nЭто действие необратимо!')">
                                        <input type="hidden" name="action" value="purge_user">
                                        <input type="hidden" name="deleted_id" value="<?= $du['id'] ?>">
                                        <button type="submit" class="btn-admin btn-danger btn-sm" title="Удалить навсегда">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- Модальное окно ответа на обращение -->
<div class="modal" id="replyModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>✉️ Ответ на обращение #<span id="modalTicketId"></span></h3>
            <button class="modal-close" onclick="closeReplyModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="reply_appeal">
            <input type="hidden" name="ticket_id" id="modalTicketIdInput">
            
            <div class="form-group">
                <label>Тема: <strong id="modalSubject"></strong></label>
            </div>
            <div class="form-group">
                <label for="response">Ваш ответ *</label>
                <textarea name="response" id="modalResponse" required placeholder="Напишите ответ пользователю..."></textarea>
            </div>
            <div class="form-group">
                <label for="status">Статус после ответа</label>
                <select name="status" id="modalStatus">
                    <option value="in_progress">🔵 В работе</option>
                    <option value="resolved">🟢 Решено</option>
                    <option value="new">🟡 Новое (не менять)</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-admin btn-warning" onclick="closeReplyModal()">Отмена</button>
                <button type="submit" class="btn-admin btn-success">Отправить ответ</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно удаления пользователя -->
<div class="modal" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>🗑️ Удаление пользователя #<span id="deleteUserId"></span></h3>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        
        <div style="background:#fef2f2; border-left:4px solid #dc2626; padding:12px; border-radius:6px; margin-bottom:20px;">
            <strong>⚠️ Внимание!</strong> Пользователь <strong id="deleteUserName"></strong> будет удалён и перемещён в архив.
            <br><small>Все его записи на курсы будут аннулированы, обращения сохранены (без привязки к пользователю).</small>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" id="deleteUserIdInput">
            
            <div class="form-group">
                <label for="reason">Причина удаления *</label>
                <textarea name="reason" id="reason" required 
                          placeholder="Укажите причину: нарушение правил, дубликат аккаунта, запрос пользователя и т.д."
                          style="min-height:80px;"></textarea>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-admin btn-warning" onclick="closeDeleteModal()">Отмена</button>
                <button type="submit" class="btn-admin btn-danger">Удалить и архивировать</button>
            </div>
        </form>
    </div>
</div>

<script>
    // === Модальное окно ответа на обращение ===
    function openReplyModal(ticketId, subject) {
        document.getElementById('modalTicketId').textContent = ticketId;
        document.getElementById('modalTicketIdInput').value = ticketId;
        document.getElementById('modalSubject').textContent = subject;
        document.getElementById('replyModal').classList.add('active');
    }
    
    function closeReplyModal() {
        document.getElementById('replyModal').classList.remove('active');
        document.getElementById('modalResponse').value = '';
    }
    
    document.getElementById('replyModal').addEventListener('click', function(e) {
        if (e.target === this) closeReplyModal();
    });
    
    // Модальное окно удаления пользователя
    function openDeleteModal(userId, userName) {
        document.getElementById('deleteUserId').textContent = userId;
        document.getElementById('deleteUserIdInput').value = userId;
        document.getElementById('deleteUserName').textContent = userName;
        document.getElementById('reason').value = '';
        document.getElementById('deleteModal').classList.add('active');
        document.getElementById('reason').focus();
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
    }
    
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
</script>

<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>