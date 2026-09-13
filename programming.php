<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Как эффективно учиться программированию</title>
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

        /* Список советов */
        .tips-list {
            list-style: none;
            padding: 0;
        }

        .tips-list li {
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .tips-list li:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .tips-list h3 {
            margin-top: 0;
            color: #007bff;
            font-size: 18px;
        }

        .tips-list p {
            color: #666;
            line-height: 1.5;
            margin-bottom: 0;
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
        <h1 class="animated-title"><strong>Как эффективно учиться программированию</strong></h1>
        <p>
            Мы стремимся помочь каждому студенту раскрыть свой потенциал и достичь высоких результатов. Чтобы ваше обучение было максимально эффективным, мы подготовили несколько полезных советов, которые помогут вам на этом пути.
        </p>

        <!-- Советы по обучению -->
        <h1>Советы для успешного обучения:</h1>
        <ul class="tips-list">
            <li>
                <h3>Планируйте свое время</h3>
                <p>Составьте расписание занятий, выделяя время для лекций, практических занятий и самостоятельной работы. Используйте инструменты планирования, такие как календари или приложения для управления задачами.</p>
            </li>
            <li>
                <h3>Активно участвуйте</h3>
                <p>Задавайте вопросы преподавателям, участвуйте в дискуссиях и групповых проектах. Посещайте дополнительные семинары и мероприятия университета.</p>
            </li>
            <li>
                <h3>Регулярно практикуйтесь</h3>
                <p>Выполняйте задания и упражнения, предложенные преподавателями. Применяйте полученные знания в реальных проектах, чтобы закрепить материал.</p>
            </li>
            <li>
                <h3>Ищите поддержку</h3>
                <p>Обращайтесь за помощью к преподавателям, наставникам или одногруппникам. Присоединяйтесь к студенческим сообществам и клубам по интересам.</p>
            </li>
            <li>
                <h3>Используйте доступные ресурсы</h3>
                <p>Используйте книги, научные статьи, онлайн-платформы и техническую поддержку университета для углубленного изучения тем.</p>
            </li>
            <li>
                <h3>Будьте последовательны</h3>
                <p>Регулярно занимайтесь, даже если это всего 30 минут в день. Не бойтесь ошибаться — они являются частью процесса обучения.</p>
            </li>
            <li>
                <h3>Развивайте soft skills</h3>
                <p>Работайте над навыками презентации, публичных выступлений и делового общения. Участвуйте в студенческих конференциях и конкурсах.</p>
            </li>
            <li>
                <h3>Готовьтесь к будущей карьере</h3>
                <p>Посещайте ярмарки вакансий, создавайте портфолио и используйте возможности стажировок и практик, предлагаемых университетом.</p>
            </li>
        </ul>

        <!-- Заключение -->
        <div class="info-section">
            <h2><strong>Заключение</strong></h2>
            <p>
                Мы верим, что каждый студент может достичь успеха, если будет следовать этим простым, но важным принципам. Наш университет всегда поддерживает вас на каждом этапе вашего пути. Помните: обучение — это не только получение знаний, но и развитие личности. <strong>Удачи в учебе!</strong>
            </p>
        </div>
    </main>

    <div class="magnifier"></div>

    <?php include 'footer.php'; ?>
    <script src="/magnifier.js"></script> 
    <script src="/theme.js"></script> 
</body>
</html>