<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Функция для безопасной загрузки переменных из файла .env
if (!function_exists('loadEnv')) {
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
}

// Загружаем .env 
loadEnv(__DIR__ . '/.env');

// Инициализация переменных
$pdo_breadcrumbs = null;
$isAdmin = false;

// Проверка авторизации и безопасное подключение к БД
if (isset($_SESSION['user_id'])) {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'];
    $db_user = $_ENV['DB_USER'];
    $db_pass = $_ENV['DB_PASS'];

    try {
        // Используем utf8mb4 и стандартные настройки безопасности
        $pdo_breadcrumbs = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_user, $db_pass);
        $pdo_breadcrumbs->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo_breadcrumbs->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $stmt = $pdo_breadcrumbs->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Строгая проверка роли (число 1, строка '1' или строка 'admin')
        $isAdmin = $user && ($user['role'] == 1 || $user['role'] === '1' || $user['role'] === 'admin');
        
    } catch (PDOException $e) {
        // Логируем ошибку, но не прерываем работу сайта. 
        // Хлебные крошки просто отобразятся без динамических названий (грациозная деградация).
        error_log("Header DB Error: " . $e->getMessage());
    }
}

// ХЛЕБНЫЕ КРОШКИ 
function getBreadcrumbs($pdo = null) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $page = basename($uri, '.php');
    $crumbs = [['url' => 'index.php', 'label' => '🏠 Главная']];
    
    if ($page === 'index' || $page === '') {
        return $crumbs;
    }
    
    switch ($page) {
        case 'catalog':
            $crumbs[] = ['url' => 'catalog.php', 'label' => '📚 Каталог'];
            if (isset($_GET['category']) && $pdo) {
                try {
                    $cat_id = (int)$_GET['category'];
                    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                    $stmt->execute([$cat_id]);
                    $cat = $stmt->fetch();
                    if ($cat) {
                        $crumbs[] = ['label' => '📁 ' . htmlspecialchars($cat['name'])];
                    }
                } catch (Exception $e) {}
            }
            break;
        
        case 'course':
            $crumbs[] = ['url' => 'catalog.php', 'label' => '📚 Каталог'];
            if (isset($_GET['slug']) && $pdo) {
                try {
                    $stmt = $pdo->prepare("SELECT title FROM courses WHERE slug = ?");
                    $stmt->execute([$_GET['slug']]);
                    $course = $stmt->fetch();
                    if ($course) {
                        $crumbs[] = ['label' => '🎓 ' . htmlspecialchars(mb_strimwidth($course['title'], 0, 30, '...'))];
                    } else {
                        $crumbs[] = ['label' => '🎓 Курс'];
                    }
                } catch (Exception $e) {
                    $crumbs[] = ['label' => '🎓 Курс'];
                }
            }
            break;
        
        case 'lesson':
            $crumbs[] = ['url' => 'catalog.php', 'label' => '📚 Каталог'];
            if (isset($_GET['slug']) && $pdo) {
                try {
                    $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE slug = ?");
                    $stmt->execute([$_GET['slug']]);
                    $course = $stmt->fetch();
                    if ($course) {
                        $crumbs[] = ['url' => 'course.php?slug=' . urlencode($_GET['slug']), 
                                     'label' => '🎓 ' . htmlspecialchars(mb_strimwidth($course['title'], 0, 25, '...'))];
                        if (isset($_GET['lesson']) && $pdo) {
                            $lesson_id = (int)$_GET['lesson'];
                            $stmt = $pdo->prepare("SELECT title FROM lessons WHERE id = ? AND course_id = ?");
                            $stmt->execute([$lesson_id, $course['id']]);
                            $lesson = $stmt->fetch();
                            if ($lesson) {
                                $crumbs[] = ['label' => '📖 ' . htmlspecialchars(mb_strimwidth($lesson['title'], 0, 25, '...'))];
                            }
                        }
                    }
                } catch (Exception $e) {}
            }
            break;
        
        case 'dashboard':
            $crumbs[] = ['label' => '👤 Личный кабинет'];
            break;
        
        case 'appeal':
            $crumbs[] = ['url' => 'dashboard.php', 'label' => '👤 Личный кабинет'];
            $crumbs[] = ['label' => '💬 Обращения'];
            break;
        
        case 'documents':
            $crumbs[] = ['label' => '📖 Полезное'];
            break;
        
        case 'law':
            $crumbs[] = ['url' => 'documents.php', 'label' => '📖 Полезное'];
            $crumbs[] = ['label' => '📚 IT-глоссарий'];
            break;
        
        case 'repair':
            $crumbs[] = ['url' => 'documents.php', 'label' => '📖 Полезное'];
            $crumbs[] = ['label' => '💼 Навыки для работы'];
            break;
        
        case 'browser_extensions':
            $crumbs[] = ['url' => 'documents.php', 'label' => '📖 Полезное'];
            $crumbs[] = ['label' => '🔧 Расширения'];
            break;
        
        case 'trends_2026':
            $crumbs[] = ['url' => 'documents.php', 'label' => '📖 Полезное'];
            $crumbs[] = ['label' => '🚀 Тренды 2026'];
            break;
        
        case 'burnout_it':
            $crumbs[] = ['url' => 'documents.php', 'label' => '📖 Полезное'];
            $crumbs[] = ['label' => '🧠 Профилактика выгорания'];
            break;
        
        case 'cybersecurity_basics':
            $crumbs[] = ['url' => 'documents.php', 'label' => '📖 Полезное'];
            $crumbs[] = ['label' => '🔐 Кибербезопасность'];
            break;
        
        case 'admin':
            $crumbs[] = ['url' => 'admin.php', 'label' => '⚙️ Админ-панель'];
            $tab = $_GET['tab'] ?? 'dashboard';
            $admin_tabs = [
                'dashboard' => null,
                'users'     => '👥 Пользователи',
                'courses'   => '📚 Курсы',
                'appeals'   => '💬 Обращения',
                'deleted'   => '🗑️ Удалённые',
            ];
            if (isset($admin_tabs[$tab]) && $admin_tabs[$tab] !== null) {
                $crumbs[] = ['label' => $admin_tabs[$tab]];
            }
            break;
        
        case 'teacher':
            $crumbs[] = ['url' => 'teacher.php', 'label' => '👨‍🏫 Преподаватель'];
            $tab = $_GET['tab'] ?? 'dashboard';
            $teacher_tabs = [
                'dashboard' => null,
                'courses'   => '📚 Мои курсы',
                'students'  => '👥 Студенты',
                'appeals'   => '💬 Обращения',
            ];
            if (isset($teacher_tabs[$tab]) && $teacher_tabs[$tab] !== null) {
                $crumbs[] = ['label' => $teacher_tabs[$tab]];
            }
            break;
        
        case 'course_edit':
            $crumbs[] = ['url' => 'admin.php?tab=courses', 'label' => '📚 Курсы'];
            if (isset($_GET['id']) && $pdo) {
                try {
                    $stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
                    $stmt->execute([(int)$_GET['id']]);
                    $course = $stmt->fetch();
                    if ($course) {
                        $crumbs[] = ['label' => '✏️ ' . htmlspecialchars(mb_strimwidth($course['title'], 0, 25, '...'))];
                    }
                } catch (Exception $e) {}
            }
            break;
        
        case 'about':
            $crumbs[] = ['label' => 'ℹ️ О нас'];
            break;
        case 'services':
            $crumbs[] = ['label' => '⚙️ Услуги'];
            break;
        case 'contacts':
            $crumbs[] = ['label' => '📞 Контакты'];
            break;
        case 'register':
            $crumbs[] = ['label' => '📝 Регистрация'];
            break;
        case 'login':
            $crumbs[] = ['label' => '🔐 Вход'];
            break;
        case 'search':
            $crumbs[] = ['label' => '🔍 Поиск'];
            if (isset($_GET['query'])) {
                $crumbs[] = ['label' => '«' . htmlspecialchars(mb_strimwidth($_GET['query'], 0, 20, '...')) . '»'];
            }
            break;
        
        default:
            $crumbs[] = ['label' => ucfirst(str_replace('_', ' ', $page))];
            break;
    }
    
    return $crumbs;
}

$breadcrumbs = getBreadcrumbs($pdo_breadcrumbs);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT-Academy.pro</title>
    <link rel="stylesheet" href="style.css" id="theme"> 
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const themeLink = document.getElementById('theme');
            if (!themeLink) return;
            const isAccessibilityMode = localStorage.getItem('accessibilityMode') === 'true';
            themeLink.setAttribute('href', isAccessibilityMode ? 'accessible.css' : 'style.css'); 
            document.body.classList.toggle('accessibility-mode', isAccessibilityMode);
            const toggleButton = document.getElementById('accessibility-toggle');
            if (toggleButton) {
                toggleButton.textContent = isAccessibilityMode ? 'Обычная версия' : 'Версия для слабовидящих';
            }
        });
        function toggleTheme() {
            const themeLink = document.getElementById('theme');
            if (!themeLink) return;
            const isAccessibilityMode = localStorage.getItem('accessibilityMode') === 'true';
            const newMode = !isAccessibilityMode;
            localStorage.setItem('accessibilityMode', newMode);
            themeLink.setAttribute('href', newMode ? 'accessible.css' : 'style.css'); 
            document.body.classList.toggle('accessibility-mode', newMode); 
            const toggleButton = document.getElementById('accessibility-toggle');
            if (toggleButton) {
                toggleButton.textContent = newMode ? 'Обычная версия' : 'Версия для слабовидящих';
            }
        }
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; }

        /* === ХЛЕБНЫЕ КРОШКИ (УЛУЧШЕННЫЕ) === */
        .breadcrumbs-container {
            background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 0;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .breadcrumbs {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            flex-wrap: nowrap;
            overflow-x: auto;
            white-space: nowrap;
            scrollbar-width: none;
            height: 32px;
        }
        .breadcrumbs::-webkit-scrollbar { display: none; }
        .breadcrumbs a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: middle;
        }
        .breadcrumbs a:hover { color: #1e40af; background: #eff6ff; }
        .breadcrumbs .separator { 
            color: #94a3b8; 
            padding: 0 2px; 
            user-select: none;
        }
        .breadcrumbs .current {
            color: #0f172a;
            font-weight: 600;
            padding: 4px 8px;
            background: #f1f5f9;
            border-radius: 6px;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: middle;
        }

        header {
            overflow: visible !important;
            background: linear-gradient(135deg, #00ffff 0%, #66ccff 100%);
            padding: 8px 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        nav {
            max-width: 1400px;
            margin: 0 auto;
        }
        nav > ul {
            display: flex;
            list-style: none;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
            scrollbar-width: none;
            padding: 2px 0;
        }
        nav > ul::-webkit-scrollbar { display: none; }
        nav > ul > li {
            position: relative;
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }
        .logo-item img {
            height: 36px;
            width: auto;
            aspect-ratio: 4 / 1;
            border-radius: 6px;
            display: block;
            object-fit: contain;
        }
        nav > ul > li > a {
            display: block;
            padding: 7px 12px;
            text-decoration: none;
            color: #1e293b;
            font-weight: 500;
            font-size: 13px;
            border-radius: 6px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        nav > ul > li > a:hover { background: rgba(255,255,255,0.6); color: #007bff; }
        nav ul ul {
            display: none;
            position: absolute;
            top: 100%; left: 0;
            background: #ffffff;
            min-width: 220px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            border-radius: 8px;
            z-index: 1000;
            padding: 6px 0;
            border: 1px solid #e2e8f0;
        }
        nav > ul > li:hover > ul { display: block; }
        nav ul ul a {
            padding: 10px 16px;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        nav ul ul a:hover { background: #f8fafc; color: #007bff; padding-left: 20px; }

        .header-btn {
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
        }
        .btn-login { background: #007bff; color: white; }
        .btn-login:hover { background: #0056b3; }
        .btn-register { background: #28a745; color: white; }
        .btn-register:hover { background: #1e7e34; }
        .btn-admin { background: #ffc107; color: #1e293b; }
        .btn-logout { background: #dc3545; color: white; }

        .search-form { display: flex; align-items: center; flex-shrink: 0; }
        .search-input-wrapper { position: relative; display: flex; align-items: center; }
        .search-form input {
            padding: 6px 28px 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 18px;
            width: 150px;
            font-size: 12px;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .search-form input:focus { width: 170px; border-color: #007bff; outline: none; }
        .search-form button {
            position: absolute;
            right: 8px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 2px;
        }
        .search-form button img { height: 13px; display: block; }

        #accessibility-toggle {
            background: linear-gradient(135deg, #ffff00 0%, #ffcc00 100%);
            color: #1e293b;
            border: 2px solid #eab308;
            border-radius: 22px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            max-width: 200px;
        }
        #accessibility-toggle:hover {
            background: linear-gradient(135deg, #ffcc00 0%, #ffb300 100%);
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }

        @media (max-width: 1200px) {
            nav > ul { justify-content: flex-start; padding: 0 5px; }
            .search-form input { width: 130px; }
            .search-form input:focus { width: 150px; }
        }

        @media (max-width: 992px) {
            header { padding: 6px 10px; }
            nav > ul { gap: 4px; }
            nav > ul > li > a { padding: 6px 10px; font-size: 12px; }
            .header-btn { padding: 6px 12px; font-size: 11px; }
            #accessibility-toggle { font-size: 11px; padding: 5px 10px; }
            #accessibility-toggle::after { content: "👁"; display: inline; }
            #accessibility-toggle span { display: none; }
        }

        @media (max-width: 768px) {
            .breadcrumbs { font-size: 11px; padding: 0 10px; }
            .breadcrumbs a:not(:nth-last-child(-n+3)) { display: none; }
            .breadcrumbs .separator:not(:nth-last-child(-n+4)) { display: none; }
            .breadcrumbs a:nth-last-child(3)::before {
                content: "... / ";
                color: #94a3b8;
                margin-right: 4px;
            }
            
            nav > ul {
                flex-wrap: wrap;
                justify-content: space-between;
                padding: 5px 0;
            }
            
            #accessibility-toggle {
                order: 10;
                margin-left: auto;
                font-size: 11px;
                padding: 4px 8px;
            }
            
            .search-form {
                order: 3;
                width: 100%;
                margin-top: 8px;
                justify-content: center;
            }
            .search-form input { width: 100%; max-width: 250px; }
            
            .logo-item img { height: 32px; }
        }
    </style>
</head>
<body>

    <header>
        <nav>
            <ul>
                <li class="logo-item">
                    <a href="index.php">
                        <img src="photo/logo.jpg" alt="IT-Academy.pro">
                    </a>
                </li>
                
                <li>
                    <a href="index.php">Главная</a>
                    <ul>
                        <li><a href="about.php">ℹ️ О нас</a></li>
                        <li><a href="services.php">⚙️ Услуги</a></li>
                    </ul>
                </li>
                <li>
                    <a href="documents.php">📖 Полезное</a>
                    <ul>
                        <li><a href="law.php">📚 IT-глоссарий</a></li>
                        <li><a href="repair.php">💼 Навыки для работы</a></li>
                        <li><a href="browser_extensions.php">🔧 Расширения</a></li>
                        <li><a href="trends_2026.php">🚀 Тренды 2026</a></li>
                        <li><a href="burnout_it.php">🧠 Профилактика выгорания</a></li>
                        <li><a href="cybersecurity_basics.php">🔐 Кибербезопасность</a></li>
                    </ul>
                </li>
                <li><a href="contacts.php">📞 Контакты</a></li>

                <?php if (!isset($_SESSION['user_id'])): ?>
                    <li><a href="login.php" class="header-btn btn-login">🔐 Войти</a></li>
                    <li><a href="register.php" class="header-btn btn-register">📝 Регистрация</a></li>
                <?php else: ?>
                    <li><a href="appeal.php" class="header-btn">💬 Обращения</a></li>
                    <li><a href="dashboard.php" class="header-btn">👤 Кабинет</a></li>
                    <li><a href="logout.php" class="header-btn btn-logout">🚪 Выйти</a></li>
                    <?php if ($isAdmin): ?>
                        <li>
                            <a href="admin.php" class="header-btn btn-admin">⚙️ Админка</a>
                            <ul>
                                <li><a href="admin.php?tab=users">👥 Пользователи</a></li>
                                <li><a href="admin.php?tab=appeals">📬 Обращения</a></li>
                                <li><a href="admin.php?tab=deleted">🗑️ Удалённые</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <li class="search-form-item">
                    <form class="search-form" action="search.php" method="GET">
                        <div class="search-input-wrapper">
                            <input type="text" name="query" placeholder="🔍 Поиск..." required>
                            <button type="submit">
                                <img src="photo/search.png" alt="Поиск" style="height: 14px;">
                            </button>
                        </div>
                    </form>
                </li>

                <li>
                    <button id="accessibility-toggle" onclick="toggleTheme()">👁 Версия для слабовидящих</button>
                </li>
            </ul>
        </nav>
    </header>
    
    <div class="breadcrumbs-container">
        <nav class="breadcrumbs" aria-label="Навигация">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index === count($breadcrumbs) - 1): ?>
                    <span class="current"><?= $crumb['label'] ?></span>
                <?php elseif (isset($crumb['url'])): ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= $crumb['label'] ?></a>
                    <span class="separator">/</span>
                <?php else: ?>
                    <span><?= $crumb['label'] ?></span>
                    <span class="separator">/</span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>