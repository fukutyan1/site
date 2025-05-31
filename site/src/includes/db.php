<?php
// Параметри підключення до бази даних
$host = 'db';               // Ім'я хоста бази даних (в Docker-контейнері - назва сервісу)
$db   = 'garbuz';           // Назва бази даних
$user = 'my_user';           // Користувач бази даних
$pass = 'my_password';       // Пароль користувача
$charset = 'utf8mb4';        // Кодування

// Створюємо DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Опції PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Викидає виключення при помилках
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Результат повертається у вигляді асоціативного масиву
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Використовувати справжні підготовлені вирази
];

try {
    // Створення об'єкта PDO для роботи з базою
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Якщо не вдалося підключитися, виводимо повідомлення і припиняємо виконання
    die('Помилка підключення до бази даних: ' . $e->getMessage());
}
