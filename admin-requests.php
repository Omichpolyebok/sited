<?php
// public/admin-requests.php
ob_start();
require_once '/var/www/mysite/inc/init.php';
require_once '/var/www/mysite/inc/header.php';
require_once '/var/www/mysite/src/db.php';

// Проверка прав админа
if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Доступ только для администрации.");
}

// Получаем ВСЕ заявки с именами жильцов
$sql = "SELECT r.*, u.full_name, u.apartment 
        FROM requests r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC";
$requests = $pdo->query($sql)->fetchAll();
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Все заявки (Админ)</title>
    <link rel="stylesheet" href="style_new.css?v=<?= time() ?>">
</head>
<body>
<?php render_header(); ?>
<div class="container">
    <h1>Журнал всех заявок жильцов</h1>
    
    <table>
        <thead>
            <tr>
                <th>Дата</th>
                <th>Кв.</th>
                <th>Жилец</th>
                <th>Категория</th>
                <th>Суть</th>
                <th>Статус</th>
                <th>Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= date('d.m.Y H:i', strtotime($req['created_at'])) ?></td>
                    <td><?= htmlspecialchars($req['apartment']) ?></td>
                    <td><?= htmlspecialchars($req['full_name']) ?></td>
                    <td><?= htmlspecialchars($req['category']) ?></td>
                    <td><b><?= htmlspecialchars($req['title']) ?></b></td>
                    <td><?= htmlspecialchars($req['status']) ?></td>
                    <td>
                        <!-- Ссылка на файл редактирования, который ты скинул выше -->
                        <a href="admin_edit.php?id=<?= $req['id'] ?>">✏️ Ответить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>