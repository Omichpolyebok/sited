<?php
// public/my-requests.php
ob_start();
require_once '/var/www/mysite/inc/init.php'; 
require_once '/var/www/mysite/inc/header.php';
require_once '/var/www/mysite/src/db.php';

// Проверка: вошел ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// --- ГЕНЕРАЦИЯ CSRF ТОКЕНА ---
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// --- ОБРАБОТКА ФОРМЫ (СОЗДАНИЕ ЗАЯВКИ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    // Проверка CSRF
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        die("Ошибка безопасности");
    }

    $category = $_POST['category'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (mb_strlen($title) > 5 && mb_strlen($description) > 5) {
        $stmt = $pdo->prepare("INSERT INTO requests (user_id, category, title, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $category, $title, $description]);
        
        header("Location: my-requests.php?success=1");
        exit;
    }
}

// --- ПОЛУЧЕНИЕ ТОЛЬКО СВОИХ ЗАЯВОК ---
$stmt = $pdo->prepare("SELECT * FROM requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$requests = $stmt->fetchAll();
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои заявки</title>
    <link rel="stylesheet" href="style_new.css?v=<?= time() ?>">
</head>
<body>
<?php render_header(); ?>
<div class="container">
    <h1>Мои заявки в ТСЖ</h1>

    <!-- Форма подачи -->
    <div class="form-box" style="background: #f4f4f4; padding: 20px; border-radius: 8px;">
        <h3>Подать новую заявку</h3>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
            <input type="hidden" name="create_request" value="1">
            
            <label>Категория:</label><br>
            <select name="category">
                <option value="Сантехника">Сантехника</option>
                <option value="Электрика">Электрика</option>
                <option value="Уборка">Уборка</option>
                <option value="Другое">Другое</option>
            </select><br><br>
            
            <label>Тема:</label><br>
            <input type="text" name="title" required style="width: 100%"><br><br>
            
            <label>Описание:</label><br>
            <textarea name="description" required style="width: 100%; height: 80px;"></textarea><br><br>
            
            <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none;">Отправить</button>
        </form>
    </div>

    <hr>

    <!-- Таблица своих заявок -->
    <table>
        <thead>
            <tr>
                <th>Дата</th>
                <th>Категория</th>
                <th>Проблема</th>
                <th>Статус</th>
                <th>Ответ ТСЖ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= date('d.m.Y H:i', strtotime($req['created_at'])) ?></td>
                    <td><?= htmlspecialchars($req['category']) ?></td>
                    <td>
                        <b><?= htmlspecialchars($req['title']) ?></b><br>
                        <small><?= htmlspecialchars($req['description']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($req['status']) ?></td>
                    <td><?= htmlspecialchars($req['admin_comment'] ?? 'Ожидает ответа') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>