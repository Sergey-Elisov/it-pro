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
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    // Используем utf8mb4 для полной поддержки Unicode
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 🔐 Проверка прав администратора
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Проверяем, что роль равна 1 (или строке '1', или 'admin')
    if (!$user || ($user['role'] != 1 && $user['role'] !== 'admin')) {
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    die("Ошибка подключения к базе данных.");
}

// 3. Получаем и строго типизируем ID курса
$course_id = (int)($_GET['id'] ?? 0);
if ($course_id <= 0) {
    header("Location: admin.php?tab=courses");
    exit();
}

// Загружаем данные курса
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header("Location: admin.php?tab=courses");
    exit();
}

// Загружаем список категорий
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$msg = '';
$msgType = '';

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $desc_short = trim($_POST['description_short'] ?? '');
    $desc_full = trim($_POST['description_full'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $cat_id = (int)($_POST['category_id'] ?? 0);
    $image = trim($_POST['image_url'] ?? '');
    $published = isset($_POST['is_published']) ? 1 : 0;

    if (empty($title) || empty($slug)) {
        $msg = '❌ Заполните название и слаг!';
        $msgType = 'error';
    } else {
        // Проверка уникальности слага (исключая текущий курс)
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $course_id]);
        if ($stmt->fetch()) {
            $msg = '❌ Такой слаг уже занят другим курсом!';
            $msgType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE courses 
                    SET title = ?, slug = ?, description_short = ?, description_full = ?, 
                        price = ?, category_id = ?, image_url = ?, is_published = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$title, $slug, $desc_short, $desc_full, $price, $cat_id, $image, $published, $course_id]);
                
                $msg = '✅ Курс успешно обновлен!';
                $msgType = 'success';
                
                // Обновляем данные в форме, чтобы отобразить актуальное состояние
                $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
                $stmt->execute([$course_id]);
                $course = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Update course error: " . $e->getMessage());
                $msg = '❌ Ошибка БД при сохранении. Попробуйте позже.';
                $msgType = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование курса | Админка</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Дополнительные стили для аккуратного отображения формы */
        .admin-wrapper { display: flex; min-height: 100vh; background: #f1f5f9; }
        .admin-sidebar { width: 260px; background: #0f172a; color: #fff; padding: 20px; }
        .admin-sidebar h2 { margin-top: 0; font-size: 1.2rem; }
        .admin-nav { list-style: none; padding: 0; }
        .admin-nav li { margin-bottom: 10px; }
        .admin-nav a { color: #cbd5e1; text-decoration: none; display: block; padding: 10px; border-radius: 6px; transition: 0.2s; }
        .admin-nav a:hover, .admin-nav a.active { background: #1e293b; color: #fff; }
        .admin-main { flex: 1; padding: 30px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .admin-card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #334155; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; 
            font-size: 1rem; box-sizing: border-box; font-family: inherit;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { 
            outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); 
        }
        .admin-message { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .admin-message.error { background: #fef2f2; color: #dc2626; border-left: 4px solid #dc2626; }
        .admin-message.success { background: #f0fdf4; color: #16a34a; border-left: 4px solid #16a34a; }
        .btn-admin { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; border: none; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-success:hover { background: #15803d; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="admin-logo"><h2>⚙️ Админ-панель</h2></div>
        <ul class="admin-nav">
            <li><a href="admin.php?tab=dashboard">📊 Дашборд</a></li>
            <li><a href="admin.php?tab=users">👥 Пользователи</a></li>
            <li><a href="admin.php?tab=courses" class="active">📚 Курсы</a></li>
            <li><a href="admin.php?tab=appeals">📬 Обращения</a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>✏️ Редактирование курса #<?= $course_id ?></h1>
            <a href="admin.php?tab=courses" class="btn-admin btn-primary">← Назад к списку</a>
        </div>

        <?php if ($msg): ?>
            <div class="admin-message <?= htmlspecialchars($msgType) ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <form method="POST" style="display: grid; gap: 18px; max-width: 850px;">
                
                <div class="form-group">
                    <label for="title">Название курса *</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($course['title']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="slug">Слаг (URL) *</label>
                    <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($course['slug']) ?>" required placeholder="naprimer-html-css">
                    <small style="color:#64748b; display: block; margin-top: 4px;">Только латиница, цифры и дефисы. Используется в ссылке на курс.</small>
                </div>

                <div class="form-group">
                    <label for="description_short">Краткое описание</label>
                    <textarea id="description_short" name="description_short" rows="2"><?= htmlspecialchars($course['description_short']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="description_full">Полное описание</label>
                    <textarea id="description_full" name="description_full" rows="6"><?= htmlspecialchars($course['description_full']) ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="price">Цена (₽)</label>
                        <input type="number" id="price" step="0.01" min="0" name="price" value="<?= htmlspecialchars($course['price']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="category_id">Категория</label>
                        <select id="category_id" name="category_id" class="admin-select">
                            <option value="0">Без категории</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $course['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="image_url">Ссылка на обложку</label>
                    <input type="text" id="image_url" name="image_url" value="<?= htmlspecialchars($course['image_url']) ?>" placeholder="images/courses/my-course.jpg">
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 12px; border-radius: 8px;">
                    <input type="checkbox" name="is_published" id="is_pub" value="1" <?= $course['is_published'] ? 'checked' : '' ?> style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="is_pub" style="margin: 0; font-weight: 600; cursor: pointer;">✅ Опубликовано на сайте</label>
                </div>

                <button type="submit" class="btn-admin btn-success" style="justify-self: start; padding: 10px 24px; font-size: 1rem;">
                    💾 Сохранить изменения
                </button>
            </form>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>
</body>
</html>