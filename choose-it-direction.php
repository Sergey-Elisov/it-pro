<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Как выбрать направление в IT</title>
    <style>
        /* Общий стиль */
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #007bff;
        }

        p {
            margin-bottom: 20px;
        }

        /* Анимация заголовка */
        .animated-title {
            font-size: 2.5rem;
            color: #007bff;
            overflow: hidden;
            white-space: nowrap;
            animation: typing 3s steps(40, end);
        }

        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }

        /* Карточки с направлениями */
        .directions-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .direction-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .direction-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .direction-card img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .direction-card h3 {
            margin-top: 0;
            color: #007bff;
            font-size: 18px;
        }

        .direction-card p {
            color: #666;
            line-height: 1.5;
            margin-bottom: 0;
        }

        /* Градиентные кнопки */
        .cta-button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            background: linear-gradient(90deg, #007bff, #00d2ff);
            border: none;
            border-radius: 25px;
            text-decoration: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .cta-button:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Полезная информация */
        .info-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .info-section h2 {
            color: #007bff;
            margin-bottom: 10px;
        }

        .info-section p {
            color: #666;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <!-- Анимированный заголовок -->
        <h1 class="animated-title"><strong>Как выбрать направление в IT</strong></h1>
        <p>
           IT — это обширная сфера с множеством направлений. Чтобы сделать правильный выбор, изучите популярные направления и свои интересы. </p>
<p>
Оцените свои навыки и предпочтения:
Если вы любите работать с числами и анализировать данные, обратите внимание на Data Science или Анализ данных.
Если вам нравится создавать красивые интерфейсы и работать с визуальным контентом, попробуйте себя в UI/UX-дизайне или Frontend-разработке.
Если вы предпочитаете решать сложные технические задачи, возможно, вам подойдут Backend-разработка, DevOps или Системное администрирование.</p>
<p>
Изучите рынок труда:
Исследуйте вакансии на популярных платформах например <a href="https://www.hh.ru" target="_blank">hh.ru</a>.
Обратите внимание на востребованные направления: Веб-разработка, Мобильная разработка, Искусственный интеллект, Кибербезопасность и другие.</p>
<p>Обратите внимание на перспективы роста:
Некоторые направления, такие как Искусственный интеллект и Big Data, активно развиваются и имеют высокий потенциал для карьерного роста.
Другие, например, QA-тестирование или Техническая поддержка, могут стать хорошей стартовой точкой для входа в IT.</p>
<p>Учитесь постоянно:
IT — это динамичная сфера, где технологии быстро меняются. Регулярно обновляйте свои знания и следите за новыми трендами.
Главное правило: Начните с малого, но двигайтесь вперёд уверенно. Выбор направления — это процесс, который может занять время, но он обязательно приведёт вас к успеху, если вы будете следовать своим интересам и стремиться к развитию.
        </p>
        <?php include 'footer.php'; ?>
    <script src="/magnifier.js"></script> 
    <script src="/theme.js"></script> 
</body>
</html>