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

// 2. Получаем настройки БД из переменных окружения
$servername = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$username_db = $_ENV['DB_USER'];
$password_db = $_ENV['DB_PASS'];

    // Проверка роли 
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || ($user['role'] != 1 && $user['role'] !== '1' && $user['role'] !== 'admin')) {
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("DB Connection Error (manage_appeals.php): " . $e->getMessage());
    die("Ошибка подключения к базе данных.");
}

$message = '';
$messageType = '';

// Удаление обращения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_appeal_id'])) {
    $appeal_id = (int)$_POST['delete_appeal_id']; // Строгая типизация
    
    try {
        $stmt = $pdo->prepare("DELETE FROM appeals WHERE id = ?");
        $stmt->execute([$appeal_id]);
        
        $message = '✅ Обращение успешно удалено.';
        $messageType = 'success';
        
        // Перенаправляем, чтобы очистить POST-данные
        header("Location: manage_appeals.php?msg=deleted");
        exit();
    } catch (PDOException $e) {
        error_log("Delete appeal error: " . $e->getMessage());
        $message = '❌ Ошибка при удалении обращения.';
        $messageType = 'error';
    }
}

// Показываем сообщение, если оно пришло через GET после редиректа
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted' && empty($message)) {
    $message = '✅ Обращение успешно удалено.';
    $messageType = 'success';
}

// 4. Получаем все обращения
$stmt = $pdo->query("
    SELECT a.id, a.name, a.email, a.message, a.created_at, u.username AS user_name
    FROM appeals a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
");
$appeals = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление обращениями | Админ-панель</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        /* Стили для админски */
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
        
        .btn-delete {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 6px 12px; background: #fee2e2; color: #dc2626;
            border: 1px solid #fecaca; border-radius: 6px;
            font-size: 0.85rem; font-weight: 500; cursor: pointer;
            transition: all 0.2s; text-decoration: none;
        }
        .btn-delete:hover { background: #dc2626; color: #fff; border-color: #dc2626; }
        
        .empty-state { text-align: center; padding: 40px; color: #64748b; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="admin-container">
    <div class="admin-header">
        <h1>📬 Управление обращениями</h1>
        <a href="admin.php?tab=appeals" style="color: #2563eb; text-decoration: none; font-size: 0.9rem;">← Назад в админку</a>
    </div>

    <?php if ($message): ?>
        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <?php if (empty($appeals)): ?>
            <div class="empty-state">
                <p>📭 На данный момент обращений нет.</p>
            </div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Сообщение</th>
                        <th>Пользователь</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appeals as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['id']) ?></td>
                            <td><?= htmlspecialchars($a['name']) ?></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($a['email']) ?>" style="color: #2563eb; text-decoration: none;">
                                    <?= htmlspecialchars($a['email']) ?>
                                </a>
                            </td>
                            <td style="max-width: 250px;">
                                <div style="white-space: pre-wrap; word-break: break-word;">
                                    <?= htmlspecialchars(mb_strimwidth($a['message'], 0, 100, '...')) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($a['user_name'] ?? 'Гость') ?></td>
                            <td style="white-space: nowrap;"><?= date('d.m.Y H:i', strtotime($a['created_at'])) ?></td>
                            <td>
                                <!-- УДАЛЕНИЕ ЧЕРЕЗ POST-ФОРМУ -->
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить это обращение? Это действие нельзя отменить.');">
                                    <input type="hidden" name="delete_appeal_id" value="<?= $a['id'] ?>">
                                    <button type="submit" class="btn-delete">🗑️ Удалить</button>
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