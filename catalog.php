<?php
session_start();

// 1. Загрузка .env
function loadEnv($path) {
    if (!file_exists($path)) die('Критическая ошибка: файл .env не найден.');
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1], " \t\n\r\0\x0B\"'");
        }
    }
}
loadEnv(__DIR__ . '/.env');

// 2. Подключение к БД
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("DB Connection Error (catalog.php): " . $e->getMessage());
    die("Ошибка подключения к базе данных.");
}

// 3. Получаем все опубликованные курсы
$stmt = $pdo->query("
    SELECT c.id, c.title, c.slug, c.description_short, c.price, c.image_url, c.created_at, cat.name as category_name
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE c.is_published = 1
    ORDER BY c.created_at DESC
");
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог курсов | IT-pro</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root { --primary: #2563eb; --bg-light: #f8fafc; --text-main: #0f172a; --border: #e2e8f0; }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: var(--bg-light); color: var(--text-main); }
        .container { max-width: 1140px; margin: 0 auto; padding: 40px 20px; }
        .page-title { font-size: 2rem; margin-bottom: 30px; text-align: center; }
        
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .course-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }
        .course-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        
        .course-image {
            height: 180px;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 3rem;
        }
        .course-image img { width: 100%; height: 100%; object-fit: cover; }
        
        .course-content { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .course-category { font-size: 0.8rem; color: #2563eb; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
        .course-title { font-size: 1.2rem; margin: 0 0 10px; line-height: 1.3; }
        .course-desc { font-size: 0.9rem; color: #64748b; margin-bottom: 20px; flex-grow: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        
        .course-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 15px; border-top: 1px solid var(--border); }
        .course-price { font-size: 1.3rem; font-weight: 700; color: var(--text-main); }
        .course-price.free { color: #10b981; }
        
        .btn {
            display: inline-block; padding: 10px 20px; background: var(--primary); color: #fff;
            text-decoration: none; border-radius: 8px; font-weight: 600; transition: 0.2s;
        }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <h1 class="page-title">📚 Каталог курсов</h1>
    
    <?php if (empty($courses)): ?>
        <div style="text-align: center; padding: 60px 20px; color: #64748b;">
            <h3>Курсы пока не добавлены</h3>
            <p>Загляните сюда позже, мы постоянно обновляем программу обучения!</p>
        </div>
    <?php else: ?>
        <div class="courses-grid">
            <?php foreach ($courses as $course): ?>
                <div class="course-card">
                    <div class="course-image">
                        <?php if (!empty($course['image_url'])): ?>
                            <img src="<?= htmlspecialchars($course['image_url']) ?>" alt="<?= htmlspecialchars($course['title']) ?>">
                        <?php else: ?>
                            🎓
                        <?php endif; ?>
                    </div>
                    <div class="course-content">
                        <div class="course-category"><?= htmlspecialchars($course['category_name'] ?? 'Общее') ?></div>
                        <h3 class="course-title"><?= htmlspecialchars($course['title']) ?></h3>
                        <p class="course-desc"><?= htmlspecialchars($course['description_short'] ?? 'Описание курса') ?></p>
                        
                        <div class="course-footer">
                            <span class="course-price <?= $course['price'] == 0 ? 'free' : '' ?>">
                                <?= $course['price'] == 0 ? 'Бесплатно' : number_format($course['price'], 0, ',', ' ') . ' ₽' ?>
                            </span>
                            <a href="course.php?slug=<?= htmlspecialchars($course['slug']) ?>" class="btn">Подробнее</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>
