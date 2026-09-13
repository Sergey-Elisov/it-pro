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

// Загружаем .env 
loadEnv(__DIR__ . '/.env');

// Получаем настройки БД из переменных окружения
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$username_db = $_ENV['DB_USER'];
$password_db = $_ENV['DB_PASS'];

try {
    // Используем utf8mb4 для полной поддержки Unicode
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Проверка роли 
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || ($user['role'] != 1 && $user['role'] !== '1' && $user['role'] !== 'admin')) {
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("DB Connection Error (manage_users.php): " . $e->getMessage());
    die("Ошибка подключения к базе данных.");
}

$message = '';
$messageType = '';
$current_user_id = (int)$_SESSION['user_id'];

// Удаление пользователя 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $user_id_to_delete = (int)$_POST['delete_user_id']; // Строгая типизация
    
    // Запрещаем админу удалять самого себя
    if ($user_id_to_delete === $current_user_id) {
        $message = '❌ Вы не можете удалить свой собственный аккаунт.';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id_to_delete]);
            
            // Перенаправляем, чтобы очистить POST-данные 
            header("Location: manage_users.php?msg=deleted");
            exit();
        } catch (PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            $message = '❌ Ошибка при удалении пользователя. Возможно, на него есть ссылки в других таблицах.';
            $messageType = 'error';
        }
    }
}

// Показываем сообщение, если оно пришло через GET после успешного редиректа
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted' && empty($message)) {
    $message = '✅ Пользователь успешно удален.';
    $messageType = 'success';
}

// Получаем всех пользователей
$stmt = $pdo->query("
    SELECT id, username, email, role, created_at 
    FROM users 
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями | Админ-панель</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        /* Стили для админской таблицы */
        .admin-container { max-width: 1200px; margin: 30px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .admin-header h1 { margin: 0; color: #1e40af; font-size: 1.5rem; }
        
        .message { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .message.success { background: #f0fdf4; color: #166534; border-left: 4px solid #16a34a; }
        .message.error { background: #fef2f2; color: #dc2626; border-left: 4px solid #dc2626; }

        .table-responsive { overflow-x: auto; }
        .admin-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .admin-table th, .admin-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .admin-table th { background: #f8fafc; font-weight: 600; color: #334155; white-space: nowrap; }
        .admin-table tr:hover { background: #f9fafb; }
        .admin-table td { color: #475569; }
        
        .role-badge {
            display: inline-block; padding: 4px 10px; border-radius: 12px;
            font-size: 0.8rem; font-weight: 600; text-transform: uppercase;
        }
        .role-admin { background: #fee2e2; color: #dc2626; }
        .role-teacher { background: #e0f2fe; color: #0284c7; }
        .role-student { background: #f0fdf4; color: #16a34a; }
        
        .btn-delete {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 6px 12px; background: #fee2e2; color: #dc2626;
            border: 1px solid #fecaca; border-radius: 6px;
            font-size: 0.85rem; font-weight: 500; cursor: pointer;
            transition: all 0.2s; text-decoration: none;
        }
        .btn-delete:hover { background: #dc2626; color: #fff; border-color: #dc2626; }
        .btn-delete:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .empty-state { text-align: center; padding: 40px; color: #64748b; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="admin-container">
    <div class="admin-header">
        <h1>👥 Управление пользователями</h1>
        <div>
            <a href="create_user.php" style="color: #16a34a; text-decoration: none; font-size: 0.9rem; margin-right: 15px; font-weight: 600;">+ Создать пользователя</a>
            <a href="admin.php?tab=users" style="color: #2563eb; text-decoration: none; font-size: 0.9rem;">← Назад в админку</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <p>📭 На данный момент пользователей нет.</p>
            </div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя пользователя</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Дата регистрации</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): 
                        $role_num = (int)$u['role'];
                        $role_class = $role_num === 1 ? 'role-admin' : ($role_num === 2 ? 'role-teacher' : 'role-student');
                        $role_text = $role_num === 1 ? 'Админ' : ($role_num === 2 ? 'Преподаватель' : 'Студент');
                        $is_current_user = ($u['id'] == $current_user_id);
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($u['id']) ?></td>
                            <td style="font-weight: 500;"><?= htmlspecialchars($u['username']) ?></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($u['email']) ?>" style="color: #2563eb; text-decoration: none;">
                                    <?= htmlspecialchars($u['email']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="role-badge <?= $role_class ?>">
                                    <?= htmlspecialchars($role_text) ?>
                                </span>
                            </td>
                            <td style="white-space: nowrap;"><?= date('d.m.Y H:i', strtotime($u['created_at'])) ?></td>
                            <td>
                                <!-- УДАЛЕНИЕ ЧЕРЕЗ POST-ФОРМУ -->
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этого пользователя? Это действие нельзя отменить.');">
                                    <input type="hidden" name="delete_user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn-delete" <?= $is_current_user ? 'disabled title="Нельзя удалить себя"' : '' ?>>
                                        🗑️ Удалить
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="/theme.js"></script>
</body>
</html>