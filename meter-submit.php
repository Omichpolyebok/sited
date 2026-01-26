<?php
// public/meter-submit.php
ob_start();
require_once '/var/www/mysite/inc/init.php';
require_once '/var/www/mysite/inc/header.php';
require_once '/var/www/mysite/src/db.php';

// Проверка авторизации БОЛЕЕ СТРОГАЯ
if (!isset($_SESSION['user_id']) || !isset($_SESSION['apartment'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';
$current_month = date('Y-m-01'); // Первое число текущего месяца для проверки

// Проверяем, не отправлял ли пользователь показания за этот месяц
try {
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM meter_readings 
        WHERE user_id = ? 
        AND apartment = ? 
        AND month_year = ?
    ");
    $checkStmt->execute([
        $_SESSION['user_id'],
        $_SESSION['apartment'],
        $current_month
    ]);
    $alreadySubmitted = $checkStmt->fetch()['count'] > 0;
} catch (PDOException $e) {
    $error = "Ошибка проверки данных: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadySubmitted) {
    // CSRF защита - ОБЯЗАТЕЛЬНО!
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ошибка безопасности. Пожалуйста, обновите страницу.");
    }

    $cold_water = filter_input(INPUT_POST, 'cold_water', FILTER_VALIDATE_FLOAT, 
        ['options' => ['min_range' => 0, 'max_range' => 99999]]);
    $hot_water = filter_input(INPUT_POST, 'hot_water', FILTER_VALIDATE_FLOAT,
        ['options' => ['min_range' => 0, 'max_range' => 99999]]);
    $electricity = filter_input(INPUT_POST, 'electricity', FILTER_VALIDATE_FLOAT,
        ['options' => ['min_range' => 0, 'max_range' => 99999]]);

    if ($cold_water === false || $hot_water === false || $electricity === false) {
        $error = "Пожалуйста, введите корректные числовые значения (от 0 до 99999).";
    } elseif ($cold_water < 0 || $hot_water < 0 || $electricity < 0) {
        $error = "Показания не могут быть отрицательными!";
    } else {
        try {
            // Получаем предыдущие показания для проверки роста
            $prevStmt = $pdo->prepare("
                SELECT cold_water, hot_water, electricity 
                FROM meter_readings 
                WHERE user_id = ? AND apartment = ?
                ORDER BY reading_date DESC 
                LIMIT 1
            ");
            $prevStmt->execute([$_SESSION['user_id'], $_SESSION['apartment']]);
            $prev = $prevStmt->fetch();
            
            // Проверка: текущие показания должны быть >= предыдущих
            if ($prev) {
                if ($cold_water < $prev['cold_water'] || 
                    $hot_water < $prev['hot_water'] || 
                    $electricity < $prev['electricity']) {
                    $error = "Текущие показания не могут быть меньше предыдущих!";
                }
            }

            if (empty($error)) {
                $stmt = $pdo->prepare("
                    INSERT INTO meter_readings 
                    (user_id, apartment, cold_water, hot_water, electricity, month_year) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $_SESSION['apartment'],
                    $cold_water,
                    $hot_water,
                    $electricity,
                    $current_month
                ]);
                $success = "Показания за " . date('m.Y') . " успешно переданы!";
                $alreadySubmitted = true; // Блокируем повторную отправку
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $error = "Вы уже передавали показания за этот месяц!";
            } else {
                $error = "Ошибка при сохранении данных: " . $e->getMessage();
            }
        }
    }
}

// Генерируем CSRF токен если его нет
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Передача показаний</title>
    <link rel="stylesheet" href="style_new.css?v=<?= time() ?>">
    <style>
        .form-box { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; box-sizing: border-box; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 10px 20px; cursor: pointer; width: 100%; border-radius: 4px; }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; }
        .success { color: green; background: #e8f5e9; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .error { color: red; background: #ffebee; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
    </style>
</head>
<body>
<?php render_header(); ?>
<div class="container">
    <h1>Передача показаний счетчиков</h1>
    <p>Квартира №: <strong><?= htmlspecialchars($_SESSION['apartment']) ?></strong></p>
    <p>Месяц: <strong><?= date('F Y') ?></strong></p>

    <?php if ($alreadySubmitted): ?>
        <div class="warning">
            <strong>Внимание!</strong> Вы уже передавали показания за этот месяц.
        </div>
    <?php endif; ?>

    <div class="form-box">
        <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

        <?php if (!$alreadySubmitted): ?>
        <form method="post" id="meterForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label>Холодная вода (ХВС), м³</label>
                <input type="number" step="0.001" min="0" max="99999" 
                       name="cold_water" required placeholder="0.000"
                       oninput="validateInput(this)">
            </div>
            <div class="form-group">
                <label>Горячая вода (ГВС), м³</label>
                <input type="number" step="0.001" min="0" max="99999" 
                       name="hot_water" required placeholder="0.000"
                       oninput="validateInput(this)">
            </div>
            <div class="form-group">
                <label>Электроэнергия, кВт·ч</label>
                <input type="number" step="0.001" min="0" max="99999" 
                       name="electricity" required placeholder="0.000"
                       oninput="validateInput(this)">
            </div>
            <button type="submit" class="btn-submit" id="submitBtn">
                Отправить показания
            </button>
        </form>
        <?php else: ?>
            <p>Следующая передача показаний будет доступна с 1 числа следующего месяца.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function validateInput(input) {
    // Клиентская валидация: не более 3 знаков после запятой
    let value = input.value;
    if (value.includes('.')) {
        let parts = value.split('.');
        if (parts[1].length > 3) {
            input.value = parts[0] + '.' + parts[1].substring(0, 3);
        }
    }
    
    // Блокируем отправку если есть отрицательные значения
    let submitBtn = document.getElementById('submitBtn');
    let inputs = document.querySelectorAll('input[type="number"]');
    let hasNegative = Array.from(inputs).some(i => parseFloat(i.value) < 0);
    submitBtn.disabled = hasNegative;
}
</script>
</body>
</html>