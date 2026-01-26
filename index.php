<?php
// public/index.php
ob_start();
require_once '/var/www/mysite/inc/init.php'; 
require_once '/var/www/mysite/inc/header.php';
require_once '/var/www/mysite/src/db.php';

// Если не вошел — отправляем на логин
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
$userName = $_SESSION['full_name'];
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Главная — ТСЖ</title>
    <link rel="stylesheet" href="style_new.css?v=<?= time() ?>">
    <style>
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px; }
        .card { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 10px; text-align: center; transition: 0.3s; }
        .card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); transform: translateY(-5px); }
        .card h3 { color: #007bff; margin-bottom: 15px; }
        .card p { font-size: 14px; color: #666; margin-bottom: 20px; }
        .btn-link { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .admin-card { border-color: #ffc107; }
        .welcome-section { background: #e9ecef; padding: 30px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
<?php render_header(); ?>

<div class="container">
    <div class="welcome-section">
        <h1>Добро пожаловать, <?= htmlspecialchars($userName) ?>!</h1>
        <p>Вы зашли в систему ТСЖ как <strong><?= ($role === 'admin') ? 'Администратор' : 'Жилец' ?></strong>.</p>
    </div>

    <div class="dashboard-grid">
        <?php if ($role === 'admin'): ?>
            <!-- КАРТОЧКИ ДЛЯ АДМИНА -->
            <div class="card admin-card">
                <h3>Заявки жильцов</h3>
                <p>Просмотр и ответ на новые жалобы и обращения.</p>
                <a href="admin-requests.php" class="btn-link">Перейти к списку</a>
            </div>

            <div class="card admin-card">
                <h3>Показания счетчиков</h3>
                <p>Сводная таблица по всем квартирам за текущий месяц.</p>
                <a href="admin-readings.php" class="btn-link">Открыть журнал</a>
            </div>

            <div class="card admin-card">
                <h3>Управление домом</h3>
                <p>Добавление новостей, работа со списками жильцов.</p>
                <a href="#" class="btn-link" style="background: #ccc;">В разработке</a>
            </div>

        <?php else: ?>
            <!-- КАРТОЧКИ ДЛЯ ЖИЛЬЦА -->
            <div class="card">
                <h3>Мои заявки</h3>
                <p>Подать новую жалобу или посмотреть статус старых.</p>
                <a href="my-requests.php" class="btn-link">Открыть заявки</a>
            </div>

            <div class="card">
                <h3>Сдать показания</h3>
                <p>Передать данные по воде и электричеству за текущий месяц.</p>
                <a href="meter-submit.php" class="btn-link">Сдать данные</a>
            </div>

            <div class="card">
                <h3>Мои платежи</h3>
                <p>История начислений и оплата квитанций онлайн.</p>
                <a href="#" class="btn-link" style="background: #ccc;">В разработке</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>