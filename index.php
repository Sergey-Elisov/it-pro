<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>IT обучение с нуля</title>
    <link rel="stylesheet" href="/style.css" id="theme"> 
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

        p {
            margin-bottom: 20px;
        }

        /* Анимация заголовка */
        .animated-title {
            font-size: 2.5rem;
            color: #330066;
            overflow: hidden;
            white-space: nowrap;
            animation: typing 3s steps(40, end), blink-caret 0.75s step-end infinite;
        }

        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }

        @keyframes blink-caret {
            from, to { border-color: transparent; }
            50% { border-color: #007b00; }
        }

        .featured-service:hover {
            background-color: #bae7ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .featured-service h2 {
            color: #1890ff;
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .featured-service strong {
            font-size: 18px;
            display: block;
            margin: 5px 0;
            color: #1890ff;
        }

        .featured-service p {
            margin: 0;
            font-size: 14px;
            color: #595959;
        }

        /* Карточки с информацией */
        .info-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .info-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .info-card img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .info-card h3 {
            margin-top: 0;
            color: #007bff;
            font-size: 18px;
        }

        .info-card p {
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

        /* Иконки для списка преимуществ */
        .icon-list {
            list-style: none;
            padding: 0;
        }

        .icon-list li {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .icon-list li::before {
            content: '✓';
            margin-right: 10px;
            color: #007bff;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <!-- Анимированный заголовок -->
        <h1 class="animated-title"><strong>Добро пожаловать в IT-pro !</strong></h1>
        <p>
            Мы открываем двери в мир информационных технологий. Независимо от вашего начального уровня, мы поможем вам стать востребованным IT-специалистом.
        </p>
        <p>
            Мы — команда опытных разработчиков, преподавателей и наставников с многолетним опытом работы в IT-индустрии. 
            Наша цель — сделать обучение доступным, понятным и эффективным, превращая новичков в профессионалов.
        </p>

        <!-- Наши преимущества -->
        <h1>Наши преимущества:</h1>
        <ul class="icon-list">
            <li>Обучение с нуля: Программы разработаны специально для тех, кто начинает с основ.</li>
            <li>Практическая направленность: Реальные проекты и задачи от компаний-партнеров.</li>
            <li>Поддержка наставников: Личный наставник сопровождает вас на протяжении всего обучения.</li>
            <li>Гибкий график: Учитесь в удобное для вас время, онлайн.</li>
            <li>Помощь в трудоустройстве: Работа с рекрутинговыми агентствами, подготовка к собеседованиям, портфолио.</li>
        </ul>

        <!-- Полезная информация -->
        <h1>Полезная информация:</h1>
        <div class="info-cards-container">
            <div class="info-card">
                <img src="photo/naprav.png" alt="Направление">
                <h3>Как выбрать направление в IT?</h3>
                <p1>IT — это обширная сфера где выбор направления может быть очень сложным. Для того что бы точно определиться куда пойти, посмотрите разлиные направления этой индустрии. </p1>
                <a href="choose-it-direction.php" class="cta-button">Подробнее</a>
            </div>
            <div class="info-card">
                <img src="photo/obychenie.png" alt="Обучение">
                <h3>Как эффективно учиться программированию?</h3>
                <p>Практика, проекты, комьюнити</p>
                <a href="programming.php" class="cta-button">Подробнее</a>
            </div>
            <div class="info-card">
                <img src="photo/it-pro.png" alt="it-pro">
                <h3>Как подготовиться к обучению?</h3>
                <p>IT — это динамичная и перспективная сфера, которая требует правильной подготовки. Чтобы начать обучение и достичь своих целей, пройдите регистрацию на сайте.</p>
                <a href="register.php" class="cta-button">Зарегестрироваться</a>
            </div>
        </div>

        <!-- Картинка -->
        <img src="photo/IT.png" alt="Обучение программированию и IT" style="display: block; margin: 20px auto; max-width: 100%; height: auto;">
    </main>

    <div class="magnifier"></div>

    <?php include 'footer.php'; ?>
    <script src="/magnifier.js"></script> 
    <script src="/theme.js"></script> 
</body>
</html>