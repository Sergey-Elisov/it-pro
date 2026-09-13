<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тренды в IT на 2026 год</title>
    <link rel="stylesheet" href="style.css" id="theme">
    <style>
        /* Общий стиль */
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        main {
            max-width: 1200px; /* Ограничение ширины контента */
            margin: 0 auto; /* Центрирование контента */
            padding: 20px; /* Отступы внутри контейнера */
            box-sizing: border-box; /* Учитываем padding в ширину */
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #007bff;
        }

        p {
            margin-bottom: 20px;
        }

        nav[aria-label="breadcrumb"] li {
            display: inline;
        }

        nav[aria-label="breadcrumb"] a {
            text-decoration: none;
            color: #007bff;
            transition: color 0.3s ease;
        }

        nav[aria-label="breadcrumb"] a:hover {
            color: #0056b3;
        }

        /* Карточки для трендов */
        .trends-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .trend-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .trend-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .trend-card h3 {
            margin-top: 0;
            color: #007bff;
            font-size: 18px;
            display: flex;
            align-items: center;
        }

        .trend-card h3::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #007bff;
            margin-right: 10px;
        }

        .trend-card p {
            color: #666;
            line-height: 1.5;
            margin-bottom: 0;
        }

        /* Кнопка "Наверх" */
        .back-to-top {
            position: fixed;
            bottom: 40px;
            right: 40px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
        }

        .back-to-top:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main>

        <h1>Тренды в IT на 2026 год</h1>

        <p>Вот список потенциальных трендов в IT, которые могут получить широкое распространение в 2026 году:</p>

        <!-- Список трендов в виде карточек -->
        <div class="trends-container">
            <div class="trend-card">
                <h3>Искусственный интеллект (AI)</h3>
                <p>Интеграция AI в приложения и процессы, активное использование GenAI для создания контента и кода.</p>
            </div>
            <div class="trend-card">
                <h3>Фокус на безопасность ИИ</h3>
                <p>Повышение требований к безопасности, прозрачности и объяснимости решений ИИ.</p>
            </div>
            <div class="trend-card">
                <h3>Развитие вычислений</h3>
                <p>Рост Edge и Fog Computing, гибридных облаков.</p>
            </div>
            <div class="trend-card">
                <h3>WebAssembly (Wasm)</h3>
                <p>Выход Wasm за пределы браузера как стандарта для безопасного выполнения изолированных приложений.</p>
            </div>
            <div class="trend-card">
                <h3>Квантовые вычисления</h3>
                <p>Прогресс в аппаратуре и алгоритмах, появление гибридных решений.</p>
            </div>
            <div class="trend-card">
                <h3>"Зелёные" IT-технологии</h3>
                <p>Увеличение внимания к энергоэффективности и экологичности ПО.</p>
            </div>
            <div class="trend-card">
                <h3>Цифровое взаимодействие</h3>
                <p>Развитие метавселенной, AR/VR в корпоративной и образовательной среде.</p>
            </div>
            <div class="trend-card">
                <h3>Новые подходы к кибербезопасности</h3>
                <p>Zero Trust Architecture как стандарт, использование ИИ для обнаружения угроз.</p>
            </div>
            <div class="trend-card">
                <h3>Автоматизация DevOps и MLOps</h3>
                <p>Развитие Platform Engineering, AutoML и MLOps.</p>
            </div>
            <div class="trend-card">
                <h3>Эволюция языков программирования</h3>
                <p>Появление новых языков, ориентированных на безопасность и производительность.</p>
            </div>
        </div>

        <p>Технологическая сфера развивается быстро. Этот список отражает текущие тенденции и прогнозы, и может меняться по мере появления новых технологий.</p>

        <!-- Кнопка "Наверх" -->
        <button class="back-to-top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });">
            ↑
        </button>
    </main>

    <?php include 'footer.php'; ?>
    <script src="/magnifier.js"></script>
    <script src="/theme.js"></script>
</body>
</html>