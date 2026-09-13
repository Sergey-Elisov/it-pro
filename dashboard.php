<?php
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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

try {
    // Используем utf8mb4 для полной поддержки Unicode (включая эмодзи)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем данные пользователя (включая роль)
    $stmt = $pdo->prepare("SELECT id, username, email, role, is_active FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 🔍 ОТЛАДКА: собираем информацию о роли
    $role_from_db = $current_user['role'] ?? 'NULL';
    $role_type = gettype($role_from_db);
    $check_double_equal = ($role_from_db == 1);
    $check_triple_equal = ($role_from_db === 1);
    $check_string_one = ($role_from_db === '1');
    
    // Универсальная проверка админа
    $isAdmin = false;
    if ($current_user) {
        $r = $current_user['role'];
        $isAdmin = ($r == 1 || $r === '1' || $r === 'admin');
    }
    
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    die("Ошибка подключения к базе данных.");
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет | IT-pro</title>
    <link rel="stylesheet" href="style.css">
    
    <!-- Стили для кнопок в личном кабинете -->
    <style>
        .btn-catalog {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; background: #0ea5e9; color: white;
            text-decoration: none; border-radius: 10px; font-weight: 600;
            transition: all 0.2s; border: none; cursor: pointer;
        }
        .btn-catalog:hover { background: #0284c7; transform: translateY(-2px); }
        
        .btn-admin-panel {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; background: linear-gradient(135deg, #1e40af, #1d4ed8);
            color: white; text-decoration: none; border-radius: 10px;
            font-weight: 600; transition: all 0.2s; border: 2px solid #1e3a8a;
        }
        .btn-admin-panel:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(30,64,175,0.3);
        }
        
        .btn-teacher {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white; text-decoration: none; border-radius: 10px;
            font-weight: 600; transition: all 0.2s; border: 2px solid #0e7490;
        }
        .btn-teacher:hover {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(8,145,178,0.3);
        }
        
        /* Стили для отладочного блока */
        .debug-panel {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #0f172a;
            color: #22c55e;
            padding: 12px 20px;
            font-size: 12px;
            font-family: 'Consolas', 'Monaco', monospace;
            z-index: 9999;
            line-height: 1.6;
            border-bottom: 2px solid #22c55e;
        }
        .debug-panel .ok { color: #22c55e; font-weight: bold; }
        .debug-panel .err { color: #ef4444; font-weight: bold; }
        .debug-panel .warn { color: #f59e0b; font-weight: bold; }
        
        /* Статистика преподавателя */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .stat-card h3 {
            margin: 0 0 8px;
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }
        .stat-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0f172a;
        }
        
        @media (max-width: 600px) {
            .welcome-header { 
                flex-direction: column; 
                align-items: flex-start; 
                gap: 12px; 
            }
            .welcome-header > div:last-child { 
                display: flex; 
                gap: 10px; 
                flex-wrap: wrap; 
                width: 100%; 
            }
            .btn-catalog, .btn-admin-panel, .btn-teacher { 
                flex: 1; 
                justify-content: center; 
                min-width: 140px; 
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="dashboard-container">
    <h1>Личный кабинет</h1>
    
    <!-- Блок приветствия -->
    <div class="welcome-block">
        <div class="welcome-header">
            <div>
                <p style="margin:0 0 5px;">
                    Добро пожаловать, <strong><?= htmlspecialchars($current_user['username'] ?? $_SESSION['username'] ?? 'Пользователь') ?></strong>! 👋
                </p>
                <p style="margin:0; color:#64748b; font-size:0.95rem;">
                    <?php 
                    $role_num = (int)($current_user['role'] ?? 3);
                    echo match($role_num) {
                        1 => 'Панель администратора',
                        2 => 'Панель преподавателя: управляйте своими курсами',
                        default => 'Продолжайте обучение или найдите новый курс'
                    };
                    ?>
                </p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <!-- Кнопка каталога (всем) -->
                <a href="catalog.php" class="btn-catalog">📚 Каталог курсов</a>
                
                <!-- Кнопка админ-панели (ТОЛЬКО АДМИНАМ) -->
                <?php if ($isAdmin): ?>
                    <a href="admin.php" class="btn-admin-panel">⚙️ Админ-панель</a>
                <?php endif; ?>
                
                <!-- Кнопка преподавателя (ТОЛЬКО ПРЕПОДАВАТЕЛЯМ) -->
                <?php if ((int)($current_user['role'] ?? 0) === 2): ?>
                    <a href="teacher.php" class="btn-teacher">👨‍🏫 Кабинет преподавателя</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- === АДАПТИВНЫЙ БЛОК: ЗАВИСИТ ОТ РОЛИ === -->
    <?php if ((int)($current_user['role'] ?? 0) === 2): ?>
        <!-- 🟦 ДАШБОРД ПРЕПОДАВАТЕЛЯ -->
        
        <?php
        // Статистика преподавателя
        $teacher_id = $current_user['id'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $courses_count = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT e.user_id) FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $students_count = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE status = 'new'");
        $stmt->execute();
        $new_tickets = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT id, title, slug, is_published FROM courses WHERE teacher_id = ? ORDER BY created_at DESC LIMIT 3");
        $stmt->execute([$teacher_id]);
        $my_courses = $stmt->fetchAll();
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Мои курсы</h3>
                <div class="value"><?= $courses_count ?></div>
            </div>
            <div class="stat-card">
                <h3>Студентов</h3>
                <div class="value"><?= $students_count ?></div>
            </div>
            <div class="stat-card">
                <h3>Новых вопросов</h3>
                <div class="value" style="color:#dc2626;"><?= $new_tickets ?></div>
            </div>
        </div>

        <section class="my-courses">
            <h2>📚 Мои последние курсы</h2>
            <?php if ($my_courses): ?>
                <div class="courses-grid">
                    <?php foreach ($my_courses as $c): ?>
                    <div class="course-card">
                        <div class="course-image-placeholder" style="background:linear-gradient(135deg,#0891b2,#06b6d4);color:#fff;">📖</div>
                        <div class="course-content">
                            <h3><?= htmlspecialchars($c['title']) ?></h3>
                            <div class="course-actions" style="margin-top:15px; justify-content:space-between;">
                                <span style="font-size:0.85rem; color:<?= $c['is_published']?'#16a34a':'#64748b' ?>; font-weight:500;">
                                    <?= $c['is_published'] ? '🟢 Опубликован' : '🔒 Черновик' ?>
                                </span>
                                <div style="display:flex; gap:8px;">
                                    <a href="course_edit.php?id=<?= $c['id'] ?>" class="btn btn-primary" style="padding:6px 10px; font-size:0.85rem;">✏️</a>
                                    <a href="teacher.php?tab=students" class="btn btn-secondary" style="padding:6px 10px; font-size:0.85rem;">👥</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="text-align:center; margin-top:15px;">
                    <a href="teacher.php?tab=courses" class="btn btn-outline">Показать все курсы</a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>📝 У вас пока нет созданных курсов.</p>
                    <a href="teacher.php?tab=courses" class="btn btn-outline">Создать первый курс</a>
                </div>
            <?php endif; ?>
        </section>

    <?php else: ?>
        <!-- 🟩 ДАШБОРД СТУДЕНТА -->
        <section class="my-courses">
            <h2>📚 Мои курсы</h2>
            
            <?php
            $stmt = $pdo->prepare("
                SELECT 
                    c.id AS course_id, c.title, c.description_short, c.image_url, c.slug,
                    e.progress_percent, e.status, e.enrolled_at,
                    (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) AS total_lessons
                FROM enrollments e
                JOIN courses c ON e.course_id = c.id
                WHERE e.user_id = ?
                ORDER BY e.enrolled_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($courses): ?>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): 
                        $progress = (int)($course['progress_percent'] ?? 0);
                    ?>
                    <div class="course-card">
                        <?php if (!empty($course['image_url'])): ?>
                            <img src="<?= htmlspecialchars($course['image_url']) ?>" alt="<?= htmlspecialchars($course['title']) ?>" class="course-image">
                        <?php else: ?>
                            <div class="course-image-placeholder">IT</div>
                        <?php endif; ?>
                        
                        <div class="course-content">
                            <h3><?= htmlspecialchars($course['title']) ?></h3>
                            <?php if (!empty($course['description_short'])): ?>
                                <p class="course-desc"><?= htmlspecialchars(mb_strimwidth($course['description_short'], 0, 100, '...')) ?></p>
                            <?php endif; ?>
                            
                            <div class="progress-wrapper">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $progress ?>%"></div>
                                </div>
                                <span class="progress-text"><?= $progress ?>% пройдено</span>
                            </div>
                            
                            <div class="course-actions">
                                <span class="status-badge status-<?= $course['status'] ?>">
                                    <?= match($course['status']) {
                                        'active' => '🟡 В процессе',
                                        'completed' => '🟢 Завершен',
                                        'dropped' => '🔴 Отменен',
                                        default => '⚪ Неактивен'
                                    } ?>
                                </span>
                                
                                <?php if ($course['status'] === 'active'): ?>
                                    <a href="course.php?slug=<?= htmlspecialchars($course['slug']) ?>" class="btn btn-primary">Продолжить</a>
                                <?php elseif ($course['status'] === 'completed'): ?>
                                    <a href="certificate.php?course_id=<?= $course['course_id'] ?>" class="btn btn-success">Сертификат</a>
                                <?php endif; ?>
                            </div>
                            <small class="enrolled-date">Записан: <?= date('d.m.Y', strtotime($course['enrolled_at'])) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>🎓 У вас пока нет записей на курсы.</p>
                    <a href="catalog.php" class="btn btn-outline">Перейти в каталог курсов</a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <!-- === КОНЕЦ АДАПТИВНОГО БЛОКА === -->

    <!-- === БЛОК: ОБРАЩЕНИЯ (общий для всех) === -->
    <section class="support-section">
        <h2>📬 Мои обращения</h2>
        <a href="appeal.php" class="btn btn-secondary">Создать новое обращение</a>
        
        <?php
        $stmt = $pdo->prepare("SELECT subject, status, created_at FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
        $stmt->execute([$_SESSION['user_id']]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($tickets): ?>
            <ul class="tickets-list">
                <?php foreach ($tickets as $ticket): ?>
                <li>
                    <strong><?= htmlspecialchars($ticket['subject']) ?></strong>
                    <span class="status-mini status-<?= $ticket['status'] ?>"><?= htmlspecialchars($ticket['status']) ?></span>
                    <small><?= date('d.m.Y', strtotime($ticket['created_at'])) ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>У вас нет активных обращений.</p>
        <?php endif; ?>
    </section>
</main>

<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>