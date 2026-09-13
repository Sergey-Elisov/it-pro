<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Контакты</title>
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

        .card p {
            color: #666;
            line-height: 1.5;
            margin-bottom: 0;
        }

        /* Иконки для контактов */
        .contact-info li {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .contact-info li svg {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            fill: #007bff;
        }

        /* Карта */
        iframe {
            border: none;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        iframe:hover {
            transform: scale(1.02);
        }

        /* Разделитель */
        .section-divider {
            border-top: 1px solid #e0e0e0;
            margin: 40px 0;
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
    </style>
</head>
<?php include 'header.php'; ?>

<main>
    <h1>Контакты</h1>

    <ul class="contact-info">

<div class="cards-container">
    <div class="card">
        <h3>Телефон</h3>
        <p1><a href="tel:+71234567890">+7 (123) 456-78-90</a></p1>
        <h3>Email</h3>
        <p1><a href="mailto:info@IT-PRO.ru">info@IT-PRO.ru</a></p1>
        <h3>Адрес</h3>
        <p1>г. Москва, ул. Тестовая, д. 1, офис 101</p1>
    </div>
    <div class="card">
        <h3>Режим работы очного приема</h3>
        <p1>Пн–Пт, 9:00–18:00 (обед 13:00–14:00)</p1>
    </div>
</div>

<h2 class="section-title">Часто задаваемые вопросы</h2>
<div class="cards-container">
    <div class="card">
        <h3>Как подать обращение?</h3>
        <p1>Через форму в личном кабинете.</p1>
    </div>
    <div class="card">
        <h3>Какие документы нужны?</h3>
        <p1>Паспорт или иной документ удостоверящий личность.</p1>
    </div>
    <div class="card">
        <h3>Как получить оригинал сертификата?</h3>
        <p1>Приехать в офис.</p1>
    </div>
</div>

<h2 class="section-title">Официальные ресурсы</h2>
<div class="cards-container">
    <div class="card">
        <h3><a href="https://www.gosuslugi.ru" target="_blank">Госуслуги</a></h3>
        <p1>Портал государственных услуг РФ</p1>
    </div>
    <div class="card">
        <h3><a href="https://clck.ru/3U6Vcw" target="_blank">Белыйкод.рф</a></h3>
        <p1>Обзор российских ESB-решений 2026</p1>
    </div>
    <div class="card">
        <h3><a href="https://clck.ru/3U6Vjx" target="_blank">Университет М.Ю.Витте</a></h3>
        <p1>Другие направления обучения</p>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="theme.js"></script> 
</body>
</html>
            