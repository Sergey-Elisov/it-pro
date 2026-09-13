<?php
session_start();

// === НАСТРОЙКИ ПОДКЛЮЧЕНИЯ К БД ===
$host = 'localhost';
$dbname = 'selisooi_t_educa';
$db_user = 'selisooi_t_educa';
$db_pass = 'QAZwsx123!@#'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных.");
}

// === ЛОГИКА ФИЛЬТРАЦИИ ===
$category_id = $_GET['category'] ?? null;

if ($category_id) {
    $stmt = $pdo->prepare("
        SELECT c.id, c.title, c.slug, c.description_short, c.price, c.image_url, c.created_at, cat.name as category_name 
        FROM courses c
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE c.is_published = 1 AND c.category_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$category_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT c.id, c.title, c.slug, c.description_short, c.price, c.image_url, c.created_at, cat.name as category_name 
        FROM courses c
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE c.is_published = 1
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
}
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем категории для фильтров
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог курсов | IT-pro</title>
    
    <link rel="stylesheet" href="style.css"> 

    <style>
        /* === СТИЛИ КАТАЛОГА === */
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 12px 24px rgba(0,0,0,0.1);
            --radius: 16px;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .catalog-section {
            background: var(--bg-light);
            padding: 40px 0 60px;
            min-height: 80vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .catalog-hero {
            text-align: center;
            margin-bottom: 32px;
        }

        .catalog-hero h1 {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            color: var(--text-main);
            margin: 0 0 8px;
            font-weight: 700;
        }

        .catalog-hero p {
            color: var(--text-muted);
            font-size: 1.05rem;
            margin: 0;
        }

        /* Фильтры-пилюли */
        .catalog-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 36px;
        }

        .filter-pill {
            padding: 8px 18px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 50px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .filter-pill:hover {
            background: #eff6ff;
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-pill.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
        }

        /* Сетка карточек */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 28px;
        }

        /* Карточка курса */
        .course-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .course-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
        }

        .card-media {
            position: relative;
            aspect-ratio: 16 / 9;
            background: #e2e8f0;
            overflow: hidden;
        }

        .card-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .course-card:hover .card-media img {
            transform: scale(1.05);
        }

        .img-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            background: linear-gradient(135deg, #cbd5e1 0%, #94a3b8 100%);
            color: #475569;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(4px);
            color: white;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            margin: 0 0 10px;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-main);
            line-height: 1.3;
        }

        .card-excerpt {
            margin: 0 0 16px;
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
            flex-grow: 1;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            margin-top: auto;
        }

        .price-tag {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .price-value {
            color: var(--text-main);
        }

        .price-free {
            color: var(--success);
            background: #ecfdf5;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .btn-card {
            background: var(--primary);
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
            border: none;
        }

        .btn-card:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(37, 99, 235, 0.25);
        }

        /* Пустое состояние */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 2px dashed var(--border);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 12px;
        }

        .empty-state h3 {
            margin: 0 0 8px;
            color: var(--text-main);
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .btn-reset {
            display: inline-block;
            padding: 10px 20px;
            background: #f1f5f9;
            color: var(--text-main);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-reset:hover {
            background: #e2e8f0;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .catalog-filters {
                justify-content: flex-start;
                overflow-x: auto;
                padding-bottom: 8px;
                -webkit-overflow-scrolling: touch;
            }
            
            .filter-pill {
                white-space: nowrap;
            }
            
            .courses-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .card-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .btn-card {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<section class="catalog-section">
    <div class="container">
        <header class="catalog-hero">
            <h1>Каталог курсов</h1>
            <p>Выберите направление и начните путь в IT уже сегодня</p>
        </header>

        <!-- Фильтры -->
        <div class="catalog-filters">
            <a href="catalog.php" class="filter-pill <?= empty($category_id) ? 'active' : '' ?>">Все</a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= $cat['id'] ?>" class="filter-pill <?= $category_id == $cat['id'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Сетка курсов -->
        <div class="courses-grid">
            <?php if (count($courses) > 0): ?>
                <?php foreach ($courses as $course): ?>
                    <article class="course-card">
                        <div class="card-media">
                            <?php if (!empty($course['image_url'])): ?>
                                <img src="<?= htmlspecialchars($course['image_url']) ?>" alt="<?= htmlspecialchars($course['title']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="img-placeholder">IT-pro</div>
                            <?php endif; ?>
                            <span class="card-badge"><?= htmlspecialchars($course['category_name']) ?></span>
                        </div>
                        
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($course['title']) ?></h3>
                            <p class="card-excerpt"><?= htmlspecialchars(mb_strimwidth($course['description_short'], 0, 110, '…')) ?></p>
                            
                            <div class="card-footer">
                                <div class="price-tag">
                                    <?php if ($course['price'] > 0): ?>
                                        <span class="price-value"><?= number_format($course['price'], 0, ',', ' ') ?> ₽</span>
                                    <?php else: ?>
                                        <span class="price-free">Бесплатно</span>
                                    <?php endif; ?>
                                </div>
                                <a href="course.php?slug=<?= htmlspecialchars($course['slug']) ?>" class="btn-card">Подробнее</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
<script src="theme.js"></script>
</body>
</html>