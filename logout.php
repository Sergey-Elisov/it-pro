<?php
// Начинаем сессию
session_start(); 

// Удаляем все данные сессии
session_destroy();

// Отправляем на главную страницу
header("Location: index.php");
exit();
?>