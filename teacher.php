<?php
session_start();

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

// Получаем настройки БД из переменных окружения
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    // Используем utf8mb4 для полной поддержки Unicode
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Упрощает код
    
    // Проверка роли 
    $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
    
    if (!$current_user || ($current_user['role'] != 2 && $current_user['role'] !== '2')) {
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("DB Connection Error (teacher.php): " . $e->getMessage());
    die("Ошибка подключения к базе данных.");
}

$teacher_id = (int)$_SESSION['user_id'];
$message = '';
$messageType = '';

// === ОБРАБОТКА ДЕЙСТВИЙ ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Ответ на обращение
    if ($_POST['action'] === 'reply_appeal' && isset($_POST['ticket_id'], $_POST['response'])) {
        $ticket_id = (int)$_POST['ticket_id'];
        $response = trim($_POST['response']);
        $status = $_POST['status'] ?? 'in_progress';
        
        $allowed_statuses = ['new', 'in_progress', 'resolved', 'closed'];
        if (!in_array($status, $allowed_statuses, true)) {
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
                $stmt->execute([$response, $status, $teacher_id, $ticket_id]);
                
                // Используем PRG для предотвращения повторной отправки формы
                header("Location: teacher.php?tab=appeals&msg=replied");
                exit();
            } catch (PDOException $e) {
                error_log("Reply error: " . $e->getMessage());
                $message = '❌ Ошибка при отправке ответа.';
                $messageType = 'error';
            }
        }
    }
    
    // Публикация/снятие курса 
    if ($_POST['action'] === 'toggle_course' && isset($_POST['course_id'])) {
        $course_id = (int)$_POST['course_id'];
        
        // Проверяем, что курс принадлежит этому преподавателю
        $check = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
        $check->execute([$course_id, $teacher_id]);
        
        if ($check->fetch()) {
            try {
                $stmt = $pdo->prepare("UPDATE courses SET is_published = NOT is_published WHERE id = ?");
                $stmt->execute([$course_id]);
                header("Location: teacher.php?tab=courses&msg=toggled");
                exit();
            } catch (PDOException $e) {
                error_log("Toggle course error: " . $e->getMessage());
                $message = '❌ Ошибка при изменении статуса курса.';
                $messageType = 'error';
            }
        }
    }
}

// Показываем сообщения после редиректа
if (isset($_GET['msg']) && empty($message)) {
    if ($_GET['msg'] === 'replied') {
        $message = '✅ Ответ отправлен, статус обновлён.';
        $messageType = 'success';
    } elseif ($_GET['msg'] === 'toggled') {
        $message = '✅ Статус курса успешно изменён.';
        $messageType = 'success';
    }
}

// === СТАТИСТИКА ===
$stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$stats_courses = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.user_id) FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.teacher_id = ?");
$stmt->execute([$teacher_id]);
$stats_students = $stmt->fetchColumn();

$stats_appeals_new = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'new'")->fetchColumn();
$stats_appeals_total = $pdo->query("SELECT COUNT(*) FROM support_tickets")->fetchColumn();

$stats = [
    'courses' => $stats_courses,
    'students' => $stats_students,
    'appeals_new' => $stats_appeals_new,
    'appeals_total' => $stats_appeals_total,
];

// === СПИСКИ ДАННЫХ ===
$stmt_courses = $pdo->prepare("SELECT id, title, is_published, created_at FROM courses WHERE teacher_id = ? ORDER BY created_at DESC LIMIT 30");
$stmt_courses->execute([$teacher_id]);
$courses_list = $stmt_courses->fetchAll();

$stmt_students = $pdo->prepare("
    SELECT u.id, u.username, u.email, c.title as course, e.status, e.enrolled_at
    FROM enrollments e 
    JOIN users u ON e.user_id = u.id 
    JOIN courses c ON e.course_id = c.id 
    WHERE c.teacher_id = ? 
    ORDER BY e.enrolled_at DESC LIMIT 50
");
$stmt_students->execute([$teacher_id]);
$students_list = $stmt_students->fetchAll();

// Для обращений используем prepare для единообразия, хотя параметров нет
$stmt_appeals = $pdo->prepare("
    SELECT t.id, t.subject, t.message_text, t.status, t.created_at, u.username 
    FROM support_tickets t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC LIMIT 30
");
$stmt_appeals->execute();
$appeals = $stmt_appeals->fetchAll();

$tab = $_GET['tab'] ?? 'dashboard';
$tab_titles = [
    'dashboard' => '📊 Обзор',
    'courses' => '📚 Мои курсы',
    'students' => '👥 Студенты',
    'appeals' => '💬 Обращения'
];
$current_title = $tab_titles[$tab] ?? 'Панель преподавателя';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кабинет преподавателя | IT-pro</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --t-primary: #0891b2; --t-success: #16a34a; --t-warning: #f59e0b;
            --t-danger: #dc2626; --t-bg: #f0f9ff; --t-card: #ffffff;
            --t-text: #0f172a; --t-border: #e2e8f0;
        }
        .t-wrapper { display: flex; min-height: 100vh; background: var(--t-bg); }
        .t-sidebar { width: 240px; background: var(--t-card); border-right: 1px solid var(--t-border); padding: 20px 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; flex-shrink: 0; }
        .t-logo { padding: 0 20px 20px; border-bottom: 1px solid var(--t-border); margin-bottom: 20px; }
        .t-logo h2 { margin: 0; color: var(--t-primary); font-size: 1.3rem; display: flex; align-items: center; gap: 8px; }
        .t-nav { list-style: none; padding: 0; margin: 0; }
        .t-nav li { margin: 4px 0; }
        .t-nav a { display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: var(--t-text); text-decoration: none; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; }
        .t-nav a:hover, .t-nav a.active { background: #f0fdfa; color: var(--t-primary); border-left-color: var(--t-primary); }
        .t-main { flex-grow: 1; padding: 25px; overflow-y: auto; }
        .t-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid var(--t-border); }
        .t-header h1 { margin: 0; color: var(--t-text); font-size: 1.5rem; }
        .t-user { display: flex; align-items: center; gap: 10px; color: var(--t-text); font-size: 0.95rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--t-card); padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--t-border); }
        .stat-card h3 { margin: 0 0 8px; font-size: 0.9rem; color: #64748b; font-weight: 500; }
        .stat-card .value { font-size: 1.8rem; font-weight: 700; color: var(--t-text); }
        .t-card { background: var(--t-card); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid var(--t-border); margin-bottom: 25px; overflow: hidden; }
        .t-card-header { padding: 15px 20px; border-bottom: 1px solid var(--t-border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
        .t-card-header h3 { margin: 0; font-size: 1.1rem; color: var(--t-text); }
        .t-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
        .t-table th, .t-table td { padding: 12px 20px; text-align: left; border-bottom: 1px solid var(--t-border); }
        .t-table th { background: #f8fafc; font-weight: 600; color: #475569; font-size: 0.85rem; text-transform: uppercase; }
        .t-table tr:hover { background: #f8fafc; }
        .btn-t { padding: 6px 12px; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s; }
        .btn-primary { background: var(--t-primary); color: white; }
        .btn-primary:hover { background: #0e7490; }
        .btn-success { background: var(--t-success); color: white; }
        .btn-warning { background: var(--t-warning); color: #1e293b; }
        .btn-danger { background: var(--t-danger); color: white; }
        .btn-sm { padding: 4px 8px; font-size: 0.8rem; }
        .t-message { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .t-message.success { background: #f0fdf4; color: #166534; border-left: 4px solid var(--t-success); }
        .t-message.error { background: #fef2f2; color: #dc2626; border-left: 4px solid var(--t-danger); }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 500; display: inline-block; }
        .badge-new { background: #fff7ed; color: #c2410c; }
        .badge-in_progress { background: #eff6ff; color: #1d4ed8; }
        .badge-resolved { background: #f0fdf4; color: #166534; }
        .badge-published { background: #dcfce7; color: #166534; }
        .badge-draft { background: #f1f5f9; color: #64748b; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; padding: 25px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--t-border); }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b; }
        .modal-close:hover { color: var(--t-text); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #334155; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid var(--t-border); border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        @media (max-width: 992px) {
            .t-wrapper { flex-direction: column; }
            .t-sidebar { width: 100%; height: auto; position: static; border-right: none; border-bottom: 1px solid var(--t-border); }
            .t-nav { display: flex; flex-wrap: wrap; gap: 4px; padding: 0 10px; }
            .t-nav a { padding: 8px 12px; border-left: none; border-bottom: 3px solid transparent; }
            .t-nav a.active { border-left: none; border-bottom-color: var(--t-primary); }
        }
        @media (max-width: 600px) {
            .t-main { padding: 15px; }
            .t-table { font-size: 0.85rem; }
            .t-table th, .t-table td { padding: 8px 12px; }
            .btn-t { padding: 4px 8px; font-size: 0.75rem; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="t-wrapper">
    <aside class="t-sidebar">
        <div class="t-logo">
            <h2>👨‍🏫 Преподаватель</h2>
        </div>
        <ul class="t-nav">
            <li><a href="?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>">📊 Обзор</a></li>
            <li><a href="?tab=courses" class="<?= $tab === 'courses' ? 'active' : '' ?>">📚 Мои курсы</a></li>
            <li><a href="?tab=students" class="<?= $tab === 'students' ? 'active' : '' ?>">👥 Студенты</a></li>
            <li><a href="?tab=appeals" class="<?= $tab === 'appeals' ? 'active' : '' ?>">💬 Обращения</a></li>
            <li><a href="dashboard.php" style="color: var(--t-danger); margin-top: 20px;">← Вернуться на сайт</a></li>
        </ul>
    </aside>

    <main class="t-main">
        <div class="t-header">
            <h1><?= htmlspecialchars($current_title) ?></h1>
            <div class="t-user">
                <span>👤 <?= htmlspecialchars($current_user['username']) ?></span>
                <a href="logout.php" class="btn-t btn-danger btn-sm">Выйти</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="t-message <?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- ВКЛАДКА: ДАШБОРД -->
        <?php if ($tab === 'dashboard'): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Мои курсы</h3>
                    <div class="value"><?= (int)$stats['courses'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Студентов</h3>
                    <div class="value"><?= (int)$stats['students'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Новых обращений</h3>
                    <div class="value" style="color: var(--t-danger);"><?= (int)$stats['appeals_new'] ?></div>
                </div>
            </div>

            <div class="t-card">
                <div class="t-card-header">
                    <h3>🔔 Последние обращения</h3>
                    <a href="?tab=appeals" class="btn-t btn-primary btn-sm">Все обращения</a>
                </div>
                <div style="overflow-x: auto;">
                    <table class="t-table">
                        <thead>
                            <tr><th>ID</th><th>Пользователь</th><th>Тема</th><th>Статус</th><th>Дата</th><th>Действие</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($appeals, 0, 5) as $appeal): ?>
                            <tr>
                                <td>#<?= (int)$appeal['id'] ?></td>
                                <td><?= htmlspecialchars($appeal['username']) ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($appeal['subject'], 0, 40, '…')) ?></td>
                                <td><span class="badge badge-<?= htmlspecialchars($appeal['status']) ?>"><?= htmlspecialchars($appeal['status']) ?></span></td>
                                <td><?= date('d.m.Y', strtotime($appeal['created_at'])) ?></td>
                                <td>
                                    <!-- БЕЗОПАСНАЯ ПЕРЕДАЧА ДАННЫХ ЧЕРЕЗ DATA-АТРИБУТЫ -->
                                    <button class="btn-t btn-primary btn-sm btn-reply" 
                                            data-id="<?= (int)$appeal['id'] ?>" 
                                            data-subject="<?= htmlspecialchars($appeal['subject'], ENT_QUOTES, 'UTF-8') ?>">
                                        ✉️ Ответить
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- КУРСЫ -->
        <?php if ($tab === 'courses'): ?>
            <div class="t-card">
                <div class="t-card-header">
                    <h3>Мои курсы (<?= count($courses_list) ?>)</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="t-table">
                        <thead>
                            <tr><th>ID</th><th>Название</th><th>Статус</th><th>Дата</th><th>Действия</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses_list as $course): ?>
                            <tr>
                                <td>#<?= (int)$course['id'] ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($course['title'], 0, 50, '…')) ?></td>
                                <td>
                                    <span class="badge badge-<?= $course['is_published'] ? 'published' : 'draft' ?>">
                                        <?= $course['is_published'] ? 'Опубликован' : 'Черновик' ?>
                                    </span>
                                </td>
                                <td><?= date('d.m.Y', strtotime($course['created_at'])) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_course">
                                        <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
                                        <button type="submit" class="btn-t btn-<?= $course['is_published'] ? 'warning' : 'success' ?> btn-sm">
                                            <?= $course['is_published'] ? '🔒 Скрыть' : '🔓 Опубликовать' ?>
                                        </button>
                                    </form>
                                    <a href="course_edit.php?id=<?= (int)$course['id'] ?>" class="btn-t btn-primary btn-sm">✏️</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- СТУДЕНТЫ -->
        <?php if ($tab === 'students'): ?>
            <div class="t-card">
                <div class="t-card-header">
                    <h3>Студенты на моих курсах (<?= count($students_list) ?>)</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="t-table">
                        <thead>
                            <tr><th>ID</th><th>Имя</th><th>Email</th><th>Курс</th><th>Статус</th><th>Дата</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students_list as $student): ?>
                            <tr>
                                <td>#<?= (int)$student['id'] ?></td>
                                <td><?= htmlspecialchars($student['username']) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($student['email']) ?>" style="color: var(--t-primary); text-decoration: none;"><?= htmlspecialchars($student['email']) ?></a></td>
                                <td><?= htmlspecialchars(mb_strimwidth($student['course'], 0, 30, '…')) ?></td>
                                <td><span class="badge badge-<?= htmlspecialchars($student['status']) ?>"><?= htmlspecialchars($student['status']) ?></span></td>
                                <td><?= date('d.m.Y', strtotime($student['enrolled_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ОБРАЩЕНИЯ -->
        <?php if ($tab === 'appeals'): ?>
            <div class="t-card">
                <div class="t-card-header"><h3>Все обращения (<?= count($appeals) ?>)</h3></div>
                <div style="overflow-x: auto;">
                    <table class="t-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Пользователь</th>
                                <th>Тема</th>
                                <th>Сообщение</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th>Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appeals as $a): ?>
                            <tr>
                                <td>#<?= (int)$a['id'] ?></td>
                                <td><?= htmlspecialchars($a['username']) ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($a['subject'], 0, 30, '…')) ?></td>
                                <td style="max-width:250px;">
                                    <span title="<?= htmlspecialchars($a['message_text'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(mb_strimwidth($a['message_text'], 0, 100, '…')) ?>
                                    </span>
                                </td>
                                <td><span class="badge badge-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span></td>
                                <td><?= date('d.m.Y H:i', strtotime($a['created_at'])) ?></td>
                                <td>
                                    <!-- БЕЗОПАСНАЯ ПЕРЕДАЧА ДАННЫХ ЧЕРЕЗ DATA-АТРИБУТЫ -->
                                    <button class="btn-t btn-primary btn-sm btn-reply" 
                                            data-id="<?= (int)$a['id'] ?>" 
                                            data-subject="<?= htmlspecialchars($a['subject'], ENT_QUOTES, 'UTF-8') ?>">
                                        ✉️ Ответить
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- Окно для ответа на обращение -->
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
                    <option value="new">🟡 Не менять</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn-t btn-warning" onclick="closeReplyModal()">Отмена</button>
                <button type="submit" class="btn-t btn-success">Отправить ответ</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.btn-reply').forEach(button => {
        button.addEventListener('click', function() {
            const ticketId = this.getAttribute('data-id');
            const subject = this.getAttribute('data-subject');
            
            document.getElementById('modalTicketId').textContent = ticketId;
            document.getElementById('modalTicketIdInput').value = ticketId;
            document.getElementById('modalSubject').textContent = subject;
            document.getElementById('replyModal').classList.add('active');
        });
    });

    function closeReplyModal() {
        document.getElementById('replyModal').classList.remove('active');
        document.getElementById('modalResponse').value = '';
    }
    
    document.getElementById('replyModal').addEventListener('click', function(e) {
        if (e.target === this) closeReplyModal();
    });
</script>

<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>