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
    // Используем utf8mb4 для полной поддержки Unicode (включая эмодзи в описаниях)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Проверка роли (только админ может создавать курсы)
    $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Строгая проверка: роль должна быть 1 (или строкой '1', или 'admin')
    if (!$current_user || ($current_user['role'] != 1 && $current_user['role'] !== 'admin')) {
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    die("Ошибка подключения к базе данных.");
}

$message = '';
$messageType = '';

// Обработка формы создания курса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description_short = trim($_POST['description_short'] ?? '');
    $description_full = trim($_POST['description_full'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    // Валидация
    if (empty($title)) {
        $message = '❌ Укажите название курса';
        $messageType = 'error';
    } elseif (empty($slug)) {
        $message = '❌ Укажите URL (slug)';
        $messageType = 'error';
    } elseif (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
        $message = '❌ URL может содержать только строчные латинские буквы, цифры и дефисы';
        $messageType = 'error';
    } else {
        try {
            // Проверка уникальности slug
            $stmt = $pdo->prepare("SELECT id FROM courses WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $message = '❌ Курс с таким URL уже существует';
                $messageType = 'error';
            } else {
                // Обработка загрузки изображения
                $image_url = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    // Используем абсолютный путь для надежности
                    $upload_dir = __DIR__ . '/images/courses/';
                    $public_dir = 'images/courses/'; // Путь для сохранения в БД
                    
                    // Создаём папку, если её нет
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file = $_FILES['image'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    
                    if (in_array($ext, $allowed_ext) && $file['size'] <= 2 * 1024 * 1024) {
                        $new_filename = 'course_' . time() . '_' . uniqid() . '.' . $ext;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            $image_url = $public_dir . $new_filename;
                        } else {
                            $message = '❌ Ошибка перемещения загруженного изображения';
                            $messageType = 'error';
                        }
                    } else {
                        $message = '❌ Неверный формат или размер файла (макс. 2 МБ, форматы: JPG, PNG, WebP, GIF)';
                        $messageType = 'error';
                    }
                }
                
                if (empty($message)) {
                    // Создание курса
                    $stmt = $pdo->prepare("
                        INSERT INTO courses 
                        (title, slug, description_short, description_full, price, 
                         category_id, teacher_id, image_url, is_published, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $title,
                        $slug,
                        $description_short,
                        $description_full,
                        $price,
                        $category_id ?: null,
                        $teacher_id ?: null,
                        $image_url,
                        $is_published
                    ]);
                    
                    $new_course_id = $pdo->lastInsertId();
                    
                    // Редирект на страницу редактирования курса
                    header("Location: course_edit.php?id={$new_course_id}&msg=created");
                    exit();
                }
            }
        } catch (PDOException $e) {
            error_log("Course create error: " . $e->getMessage());
            $message = '❌ Ошибка базы данных при создании курса. Попробуйте позже.';
            $messageType = 'error';
        }
    }
}

// Загрузка категорий
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Загрузка преподавателей (role = 2 или '2')
// Используем prepare для единообразия, хотя query тоже безопасен здесь
$stmt_teachers = $pdo->prepare("SELECT id, username FROM users WHERE role = 2 OR role = '2' ORDER BY username ASC");
$stmt_teachers->execute();
$teachers = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание курса | IT-Academy</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .create-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .create-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        .create-header h1 { margin: 0; color: #1e40af; font-size: 1.8rem; }
        .btn-back {
            padding: 10px 20px; background: #64748b; color: white;
            text-decoration: none; border-radius: 8px; font-weight: 500; transition: all 0.2s;
        }
        .btn-back:hover { background: #475569; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 600;
            color: #334155; font-size: 0.95rem;
        }
        .form-group .hint {
            font-size: 0.85rem; color: #64748b; margin-bottom: 6px; font-weight: 400;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select,
        .form-group input[type="file"] {
            width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1;
            border-radius: 8px; font-size: 1rem; font-family: inherit;
            transition: border-color 0.2s; box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none; border-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .checkbox-group {
            display: flex; align-items: center; gap: 10px; padding: 12px;
            background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;
        }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
        .checkbox-group label { margin: 0; cursor: pointer; user-select: none; }
        .form-actions {
            display: flex; gap: 10px; justify-content: flex-end;
            margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;
        }
        .btn-submit {
            padding: 12px 30px; background: #16a34a; color: white; border: none;
            border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .btn-submit:hover { background: #15803d; }
        .btn-cancel {
            padding: 12px 30px; background: #64748b; color: white; border: none;
            border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; transition: all 0.2s;
        }
        .btn-cancel:hover { background: #475569; }
        .message { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .message.success { background: #f0fdf4; color: #166534; border-left: 4px solid #16a34a; }
        .message.error { background: #fef2f2; color: #dc2626; border-left: 4px solid #dc2626; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="create-container">
    <div class="create-header">
        <h1>➕ Создание нового курса</h1>
        <a href="admin.php?tab=courses" class="btn-back">← Назад к курсам</a>
    </div>
    
    <?php if ($message): ?>
        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        
        <div class="form-row">
            <div class="form-group">
                <label for="title">Название курса *</label>
                <input type="text" id="title" name="title" 
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="slug">URL (slug) *</label>
                <div class="hint">Только латинские буквы, цифры и дефисы. Пример: html-css-basics</div>
                <input type="text" id="slug" name="slug" 
                       value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" 
                       pattern="[a-z0-9\-]+" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="description_short">Краткое описание</label>
            <div class="hint">Отображается в каталоге (до 255 символов)</div>
            <textarea id="description_short" name="description_short" 
                      maxlength="255"><?= htmlspecialchars($_POST['description_short'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="description_full">Полное описание</label>
            <div class="hint">Отображается на странице курса</div>
            <textarea id="description_full" name="description_full" 
                      style="min-height: 200px;"><?= htmlspecialchars($_POST['description_full'] ?? '') ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Категория</label>
                <select id="category_id" name="category_id">
                    <option value="0">— Выберите категорию —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" 
                            <?= (isset($_POST['category_id']) && (int)$_POST['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="teacher_id">Преподаватель</label>
                <select id="teacher_id" name="teacher_id">
                    <option value="0">— Выберите преподавателя —</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['id'] ?>" 
                            <?= (isset($_POST['teacher_id']) && (int)$_POST['teacher_id'] === (int)$teacher['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="price">Цена (₽)</label>
                <div class="hint">0 = бесплатно</div>
                <input type="number" id="price" name="price" 
                       value="<?= htmlspecialchars($_POST['price'] ?? '0') ?>" 
                       min="0" step="0.01">
            </div>
            
            <div class="form-group">
                <label for="image">Изображение курса</label>
                <div class="hint">JPG, PNG, WebP, GIF. Макс. 2 МБ. Рекомендуется 800×450 px</div>
                <input type="file" id="image" name="image" accept="image/jpeg, image/png, image/webp, image/gif">
            </div>
        </div>
        
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" id="is_published" name="is_published" value="1"
                       <?= (isset($_POST['is_published']) && $_POST['is_published'] == '1') ? 'checked' : '' ?>>
                <label for="is_published">📢 Опубликовать сразу (виден в каталоге)</label>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="admin.php?tab=courses" class="btn-cancel">Отмена</a>
            <button type="submit" class="btn-submit">💾 Создать курс</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>