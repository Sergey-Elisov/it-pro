<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>О нас</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Стили для карточек */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }


        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #e0e0e0;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background-color: #007bff;
        }

        .tasks .card::before {
            background-color: #007bff;
        }

        .reasons .card::before {
            background-color: #28a745;
        }

        .values .card::before {
            background-color: #ffc107;
        }

        .card h3 {
            margin-top: 0;
            color: #333;
            font-size: 18px;
            display: flex;
            align-items: center;
        }

        .card h3::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #007bff;
            margin-right: 10px;
        }

        .tasks .card h3::before {
            background-color: #007bff;
        }

        .reasons .card h3::before {
            background-color: #28a745;
        }

        .values .card h3::before {
            background-color: #ffc107;
        }

        .card p {
            color: #666;
            line-height: 1.5;
            margin-bottom: 0;
        }

        .section-title {
            position: relative;
            padding-bottom: 10px;
            margin-top: 30px;
            font-size: 24px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #007bff, #00d2ff);
            border-radius: 3px;
        }

        .tasks .section-title::after {
            background: linear-gradient(90deg, #007bff, #00d2ff);
        }

        .reasons .section-title::after {
            background: linear-gradient(90deg, #28a745, #71dd8a);
        }

        .values .section-title::after {
            background: linear-gradient(90deg, #ffc107, #ffe082);
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
    </style>
</head>
<?php include 'header.php'; ?>
<main>
    <p1>
    <h1>О нас</h1>
    </p1>
    
    <p>
        Добро пожаловать в <strong>IT-PRO</strong> - ваш надёжный партнёр в мире информационных технологий.
        Мы - команда опытных разработчиков, преподавателей и наставников, которые уже много лет помогают людям начать карьеру в IT.
        Наша цель - сделать обучение современным, практичным и доступным, открывая двери в одну из самых перспективных и динамично развивающихся сфер.
    </p>

    <h2 class="section-title tasks">Наши задачи</h2>
    <div class="cards-container tasks">
        <div class="card">
            <h3>Передача актуальных знаний</h3>
            <p>Обучаем современным языкам программирования, фреймворкам и инструментам, востребованным на рынке труда.</p>
        </div>
        <div class="card">
            <h3>Практическая направленность</h3>
            <p>Каждый курс включает реальные проекты и задачи, которые вы решаете под руководством опытных наставников.</p>
        </div>
        <div class="card">
            <h3>Поддержка и мотивация</h3>
            <p>Мы сопровождаем вас на всём пути обучения, помогаем преодолевать трудности и достигать целей.</p>
        </div>
        <div class="card">
            <h3>Помощь в трудоустройстве</h3>
            <p>Работаем с рекрутинговыми агентствами, помогаем составлять резюме и готовиться к собеседованиям.</p>
        </div>
        <div class="card">
            <h3>Создание сообщества</h3>
            <p>Формируем дружную и вовлечённую среду, где студенты могут делиться опытом и находить друзей.</p>
        </div>
    </div>

    <h2 class="section-title reasons">Почему выбирают нас</h2>
    <div class="cards-container reasons">
        <div class="card">
            <h3>Обучение с нуля</h3>
            <p>Наши программы разработаны специально для новичков, не требуя глубоких знаний в программировании.</p>
        </div>
        <div class="card">
            <h3>Опытные преподаватели</h3>
            <p>Преподают действующие специалисты с реальным опытом работы в IT-компаниях.</p>
        </div>
        <div class="card">
            <h3>Гибкий график</h3>
            <p>Учитесь онлайн в удобное для вас время.</p>
        </div>
        <div class="card">
            <h3>Современные технологии</h3>
            <p>Используем передовые методики и инструменты обучения.</p>
        </div>
        <div class="card">
            <h3>Гарантированный результат</h3>
            <p>Наши выпускники успешно проходят собеседования и устраиваются на работу.</p>
        </div>
    </div>

    <h2 class="section-title values">Наши ценности</h2>
    <div class="cards-container values">
        <div class="card">
            <h3>Доступность</h3>
            <p>IT-образование должно быть доступно каждому, независимо от возраста, опыта и финансовых возможностей.</p>
        </div>
        <div class="card">
            <h3>Практика</h3>
            <p>Теория без практики бесполезна. Мы делаем упор на реальные проекты и практические навыки.</p>
        </div>
        <div class="card">
            <h3>Результат</h3>
            <p>Мы фокусируемся на реальных навыках и трудоустройстве наших студентов, а не только на теории.</p>
        </div>
        <div class="card">
            <h3>Сообщество</h3>
            <p>Вместе учиться и развиваться интереснее и эффективнее. Наше сообщество — ваша поддержка на пути к цели.</p>
        </div>
        <div class="card">
            <h3>Инновации</h3>
            <p>Мы всегда в курсе последних тенденций и внедряем их в обучение, чтобы вы оставались впереди рынка.</p>
        </div>
    </div>

    <p1>
	<h1>Миссия</h1>
	</p1>
    <p>
        Наша миссия - вдохновлять, обучать и поддерживать каждого, кто решил освоить профессию в сфере информационных технологий.
        Мы стремимся к тому, чтобы каждый человек, решивший начать путь в IT, получил качественное образование, уверенность в своих силах
        и реальный шанс на успешную карьеру в одной из самых интересных и перспективных отраслей.
    </p>

 <!-- Кнопка "Наверх" -->
        <button class="back-to-top" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });">
            ↑
        </button>
    </main>

</main>
<?php include 'footer.php'; ?>