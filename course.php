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
    // Используем utf8mb4 для полной поддержки Unicode (включая эмодзи в описаниях)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // ВАЖНО: На публичной странице никогда не выводим $e->getMessage() пользователю
    error_log("DB Connection Error (course.php): " . $e->getMessage());
    die("Ошибка подключения к базе данных. Попробуйте позже.");
}

// === ПОЛУЧАЕМ КУРС ===
$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    header('Location: catalog.php');
    exit();
}

$stmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name 
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE c.slug = ? AND c.is_published = 1
");
$stmt->execute([$slug]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: catalog.php');
    exit();
}

// === ЗАГРУЖАЕМ УРОКИ ===
$stmt = $pdo->prepare("
    SELECT id, title, content_type, content_body, is_free_preview, order_index 
    FROM lessons WHERE course_id = ? ORDER BY order_index ASC
");
$stmt->execute([$course['id']]);
$lessons = $stmt->fetchAll();

// === ПРОВЕРЯЕМ ЗАПИСЬ ПОЛЬЗОВАТЕЛЯ ===
$is_enrolled = false;
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'active'");
    $stmt->execute([$user_id, $course['id']]);
    if ($stmt->fetch()) {
        $is_enrolled = true;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?> | IT-pro</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8; --success: #10b981; --warning: #f59e0b;
            --text-main: #0f172a; --text-muted: #64748b; --border: #e2e8f0;
            --bg-light: #f8fafc; --code-bg: #1e293b; --radius: 12px;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: var(--bg-light); color: var(--text-main); }
        .container { max-width: 1140px; margin: 0 auto; padding: 0 20px; }

        /* Шапка курса */
        .course-header {
            background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%);
            color: #fff; padding: 50px 0 40px; margin-bottom: 30px;
        }
        .course-header h1 { font-size: clamp(1.8rem, 4vw, 2.5rem); margin: 0 0 12px; line-height: 1.2; }
        .course-meta { display: flex; gap: 15px; flex-wrap: wrap; opacity: 0.9; font-size: 0.95rem; }
        .badge { background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 20px; font-weight: 600; }

        /* Сетка контента */
        .course-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 40px; align-items: start; padding-bottom: 60px; }
        
        /* Уроки */
        .lesson-card {
            background: #fff; border-radius: var(--radius); box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            margin-bottom: 16px; overflow: hidden; border: 1px solid var(--border); transition: 0.2s;
        }
        .lesson-header {
            padding: 16px 20px; background: #f9fafb; border-bottom: 1px solid var(--border);
            cursor: pointer; display: flex; justify-content: space-between; align-items: center;
        }
        .lesson-header:hover { background: #f3f4f6; }
        .lesson-header h2 { margin: 0; font-size: 1.05rem; display: flex; align-items: center; gap: 12px; font-weight: 600; }
        .lesson-num { background: var(--primary); color: #fff; width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; flex-shrink: 0; }
        .toggle-icon { font-size: 1.3rem; color: var(--text-muted); transition: transform 0.2s; user-select: none; }
        
        .lesson-content { padding: 20px; display: none; line-height: 1.6; color: #334155; }
        .lesson-content.active { display: block; }
        .lesson-content p { margin: 0 0 12px; }
        .lesson-content code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 0.9em; }
        
        /* Paywall / Заблокированный урок */
        .lesson-card.locked { opacity: 0.75; background: #fafafa; }
        .lesson-card.locked .lesson-header { cursor: not-allowed; background: #f3f4f6; }
        .lock-icon { margin-left: auto; font-size: 1.1rem; }
        .locked-overlay { padding: 15px 20px; background: #fffbeb; border-top: 1px solid #fde68a; color: #92400e; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }

         .btn {
            display: block; width: 100%; padding: 14px; border-radius: 8px; font-weight: 600;
            text-align: center; text-decoration: none; cursor: pointer; transition: 0.2s; border: none; font-size: 1rem;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-outline { background: transparent; border: 2px solid var(--border); color: var(--text-muted); }
        .btn-outline:hover { border-color: var(--text-main); color: var(--text-main); }
        
        .progress-wrap { margin-top: 15px; }
        .progress-bar { height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, var(--primary), var(--success)); width: 0%; transition: width 0.5s; }
        .progress-text { font-size: 0.85rem; color: var(--text-muted); margin-top: 6px; text-align: center; }
        
        .lesson-list { list-style: none; padding: 0; margin: 0; }
        .lesson-list li { border-bottom: 1px solid var(--border); }
        .lesson-list li:last-child { border-bottom: none; }
        .lesson-list a { display: flex; align-items: center; gap: 10px; padding: 10px 0; color: var(--text-main); text-decoration: none; font-size: 0.9rem; transition: 0.2s; }
        .lesson-list a:hover { color: var(--primary); }
        .check { width: 18px; height: 18px; border-radius: 50%; border: 2px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 0.65rem; flex-shrink: 0; }
        .check.done { background: var(--success); border-color: var(--success); color: #fff; }
        .check.locked { background: #f3f4f6; border-color: #d1d5db; color: #9ca3af; }

        /* Адаптивность */
        @media (max-width: 768px) {
            .course-layout { grid-template-columns: 1fr; }
            .sidebar { position: static; order: -1; }
            .course-header { padding: 30px 0; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="course-header">
    <div class="container">
        <div class="course-meta">
            <span class="badge"><?= htmlspecialchars($course['category_name'] ?? 'Без категории') ?></span>
            <span>📚 <?= count($lessons) ?> уроков</span>
            <span>⏱ ~<?= max(2, count($lessons)) * 40 ?> мин</span>
        </div>
        <h1><?= htmlspecialchars($course['title']) ?></h1>
        <p style="font-size: 1.05rem; opacity: 0.9; max-width: 750px;"><?= htmlspecialchars($course['description_short'] ?? '') ?></p>
        
        <!-- БЛОК: ПОЛНОЕ ОПИСАНИЕ КУРСА -->
        <?php if (!empty($course['description_full'])): ?>
        <div class="course-description-block" style="background:#fff; border-radius:12px; padding:25px; margin-top: 30px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
            <h3 style="margin:0 0 15px; font-size:1.3rem; color:#0f172a;">📖 О курсе</h3>
            <div style="line-height:1.7; color:#334155; font-size:1rem;">
                <?= nl2br(htmlspecialchars($course['description_full'])) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <div class="course-layout">
        
        <!-- УРОКИ -->
        <main>
            <?php if (empty($lessons)): ?>
                <div style="background:#fff; padding:30px; border-radius:var(--radius); text-align:center; color:var(--text-muted);">
                    <h3>📦 Уроки ещё не добавлены</h3>
                    <p>Администратор наполнит курс материалами в ближайшее время.</p>
                </div>
            <?php else: ?>
                <?php foreach ($lessons as $i => $lesson): 
                    $is_locked = !$is_enrolled && !$lesson['is_free_preview'];
                ?>
                <div class="lesson-card <?= $is_locked ? 'locked' : '' ?>" id="lesson-<?= $lesson['id'] ?>">
                    <div class="lesson-header" onclick="<?= $is_locked ? '' : "toggleLesson({$lesson['id']})" ?>">
                        <h2>
                            <span class="lesson-num"><?= $i + 1 ?></span>
                            <?= htmlspecialchars($lesson['title']) ?>
                        </h2>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <?= $is_locked ? '<span class="lock-icon">🔒</span>' : '' ?>
                            <span class="toggle-icon" id="icon-<?= $lesson['id'] ?>">+</span>
                        </div>
                    </div>
                    
                    <?php if ($is_locked): ?>
                        <div class="locked-overlay">
                            <span>🔒</span> Этот урок доступен после записи на курс
                        </div>
                    <?php else: ?>
                        <div class="lesson-content" id="content-<?= $lesson['id'] ?>">
                            <?php if (!empty($lesson['content_body'])): ?>
                                <?= nl2br(htmlspecialchars($lesson['content_body'], ENT_QUOTES, 'UTF-8')) ?>
                            <?php else: ?>
                                <p><em>Материал урока загружается...</em></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <!-- САЙДБАР -->
        <aside class="sidebar">
            <div class="sidebar-card">
                <?php if ($course['price'] > 0): ?>
                    <div class="price-tag"><?= number_format($course['price'], 0, ',', ' ') ?> ₽</div>
                <?php else: ?>
                    <div class="price-tag price-free">Бесплатно</div>
                <?php endif; ?>

                <?php if ($is_enrolled): ?>
                    <a href="dashboard.php" class="btn btn-primary">📚 Продолжить обучение</a>
                    <div class="progress-wrap">
                        <div class="progress-bar"><div class="progress-fill" style="width: 0%"></div></div>
                        <div class="progress-text">Прогресс: 0%</div>
                    </div>
                    <p style="font-size:0.85rem; color:var(--success); text-align:center; margin-top:10px;">✅ Вы записаны на курс</p>
                
                <?php else: ?>
                    <?php if ($user_id): ?>
                        
                        <!-- ЛОГИКА РАЗДЕЛЕНИЯ: ПЛАТНЫЙ ИЛИ БЕСПЛАТНЫЙ -->
                        <?php if ($course['price'] > 0): ?>
                            <!-- Если платный — ведем на оплату -->
                            <a href="payment.php?course_id=<?= $course['id'] ?>" class="btn btn-primary">
                                💳 Оплатить и начать
                            </a>
                        <?php else: ?>
                            <!-- Если бесплатный — сразу записываем через форму -->
                            <form action="enroll.php" method="POST" style="margin:0;">
                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                <button type="submit" class="btn btn-primary" style="width:100%;">
                                    🚀 Записаться бесплатно
                                </button>
                            </form>
                        <?php endif; ?>

                    <?php else: ?>
                        <a href="login.php?redirect=<?= urlencode('course.php?slug=' . $slug) ?>" class="btn btn-primary">Войти / Регистрация</a>
                        <p style="font-size:0.8rem; color:var(--text-muted); text-align:center; margin-top:8px;">Для доступа нужна авторизация</p>
                    <?php endif; ?>
                <?php endif; ?>

                <ul style="list-style:none; padding:0; margin:20px 0 0; font-size:0.9rem; color:var(--text-muted);">
                    <li style="margin-bottom:6px;">✅ Доступ навсегда</li>
                    <li style="margin-bottom:6px;">✅ Сертификат об окончании</li>
                    <li style="margin-bottom:6px;">✅ Практические задания</li>
                </ul>
            </div>

            <div class="sidebar-card">
                <h3>📋 Программа курса</h3>
                <ul class="lesson-list">
                    <?php foreach ($lessons as $i => $lesson): 
                        $locked = !$is_enrolled && !$lesson['is_free_preview'];
                    ?>
                    <li>
                        <a href="#lesson-<?= $lesson['id'] ?>" onclick="<?= $locked ? 'return false;' : "openLesson({$lesson['id']}); return false;" ?>">
                            <span class="check <?= $locked ? 'locked' : ($i === 0 ? 'done' : '') ?>">
                                <?= $locked ? '🔒' : ($i === 0 ? '✓' : ($i + 1)) ?>
                            </span>
                            <span><?= htmlspecialchars($lesson['title']) ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

    </div>
</div>

<script>
    function toggleLesson(id) {
        const content = document.getElementById('content-' + id);
        const icon = document.getElementById('icon-' + id);
        const isOpen = content.classList.contains('active');
        
        document.querySelectorAll('.lesson-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.toggle-icon').forEach(el => { el.textContent = '+'; el.style.transform = 'rotate(0deg)'; });
        
        if (!isOpen) {
            content.classList.add('active');
            icon.textContent = '−';
            icon.style.transform = 'rotate(180deg)';
        }
    }

    function openLesson(id) {
        const card = document.getElementById('lesson-' + id);
        if (!card || card.classList.contains('locked')) return;
        
        toggleLesson(id);
        setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const first = document.querySelector('.lesson-card:not(.locked)');
        if (first) openLesson(parseInt(first.id.replace('lesson-', '')));
    });
</script>

<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>