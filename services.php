<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Услуги</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Стили для карточек услуг */
        .services-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }

        .service-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .service-header {
            padding: 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .service-header h2 {
            margin: 0;
            color: #333;
            font-size: 18px;
            display: flex;
            align-items: center;
        }

        .service-header h2::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #007bff;
            margin-right: 10px;
        }

        .programming .service-header h2::before {
            background-color: #007bff;
        }

        .interviews .service-header h2::before {
            background-color: #17a2b8;
        }

        .employment .service-header h2::before {
            background-color: #28a745;
        }

        .cybersecurity .service-header h2::before {
            background-color: #6f42c1;
        }

        .service-body {
            padding: 15px;
        }

        .service-body p {
            color: #666;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .service-features {
            list-style: none;
            padding-left: 0;
        }

        .service-features li {
            position: relative;
            padding-left: 20px;
            margin-bottom: 8px;
            color: #555;
        }

        .service-features li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: #007bff;
            font-weight: bold;
        }

        .programming .service-features li::before {
            color: #007bff;
        }

        .interviews .service-features li::before {
            color: #17a2b8;
        }

        .employment .service-features li::before {
            color: #28a745;
        }

        .cybersecurity .service-features li::before {
            color: #6f42c1;
        }

        .featured-service:hover {
            background-color: #bae7ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
    </style>
</head>
<?php include 'header.php'; ?>
    <main>
        <h1 class="section-title">Наши услуги</h1>
        
        <div class="services-container">
            <!-- Обучение программированию -->
            <div class="service-card programming">
                <div class="service-header">
                    <h2>Обучение программированию</h2>
                </div>
                <div class="service-body">
                    <p>Наши курсы разработаны опытными преподавателями и практикующими разработчиками. Мы обучаем современным технологиям и передовым практикам разработки.</p>
                    <ul class="service-features">
                        <li>Индивидуальные и групповые занятия</li>
                        <li>Практические проекты и задачи от реальных компаний</li>
                        <li>Обратная связь от наставников</li>
                    </ul>
                </div>
            </div>
            
            <!-- Подготовка к собеседованиям -->
            <div class="service-card interviews">
                <div class="service-header">
                    <h2>Подготовка к собеседованиям</h2>
                </div>
                <div class="service-body">
                    <p>Процесс подготовки к собеседованию на IT-должности требует глубоких знаний и уверенности в своих силах.</p>
                    <ul class="service-features">
                        <li>Знание технических вопросов по специальности</li>
                        <li>Понимание алгоритмов и структур данных</li>
                        <li>Опыт прохождения технических интервью</li>
                        <li>Составление сильного резюме</li>
                        <li>Подготовка к типичным и сложным вопросам</li>
                        <li>Проведение пробных интервью</li>
                    </ul>
                </div>
            </div>
            
            <!-- Помощь в трудоустройстве -->
            <div class="service-card employment">
                <div class="service-header">
                    <h2>Помощь в трудоустройстве</h2>
                </div>
                <div class="service-body">
                    <p>Наша цель - не только научить, но и помочь начать карьеру. Мы сотрудничаем с IT-компаниями и рекрутинговыми агентствами.</p>
                    <ul class="service-features">
                        <li>База вакансий от наших партнёров</li>
                        <li>Поддержка на этапе поиска работы</li>
                        <li>Консультации по построению карьеры</li>
                    </ul>
                </div>
            </div>
            
            <!-- Курс по кибербезопасности -->
            <div class="service-card cybersecurity">
                <div class="service-header">
                    <h2>Курс по кибербезопасности</h2>
                </div>
                <div class="service-body">
                    <p>Кибербезопасность - одна из самых востребованных и перспективных сфер. Наш курс охватывает ключевые аспекты защиты информации.</p>
                    <ul class="service-features">
                        <li>Основы защиты информации</li>
                        <li>Анализ угроз и уязвимостей</li>
                        <li>Методы предотвращения и реагирования на инциденты</li>
                        <li>Практические лаборатории и "песочницы"</li>
                        <li>Обучение у экспертов отрасли</li>
                        <li>Поддержка в получении сертификатов</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <p style="text-align: center; margin-top: 30px; font-size: 18px;">
            Для доступа к полному каталогу курсов и подачи заявки, 
            <a href="login.php" style="color: #007bff; text-decoration: none; font-weight: 600;">войдите</a> 
            или 
            <a href="register.php" style="color: #007bff; text-decoration: none; font-weight: 600;">зарегистрируйтесь</a> 
            в личном кабинете.
        </p>
    </main>
<?php include 'footer.php'; ?>
<script src="theme.js"> </script>
</body>
</html>