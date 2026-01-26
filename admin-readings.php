<?php
// public/admin-readings.php
ob_start();
require_once '/var/www/mysite/inc/init.php';
require_once '/var/www/mysite/inc/header.php';
require_once '/var/www/mysite/src/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    die("Доступ запрещен.");
}

// Фильтры
$month_filter = $_GET['month'] ?? '';
$apartment_filter = $_GET['apartment'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];

if (!empty($month_filter)) {
    // SQLite синтаксис strftime
    $where[] = "strftime('%Y-%m', r.month_year) = ?";
    $params[] = $month_filter;
}

if (!empty($apartment_filter)) {
    $where[] = "r.apartment = ?";
    $params[] = $apartment_filter;
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 1. Считаем общее количество
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM meter_readings r 
    JOIN users u ON r.user_id = u.id 
    $where_clause
");
$count_stmt->execute($params);
$total = $count_stmt->fetch()['total'] ?? 0;
$total_pages = ceil($total / $per_page);

// 2. Основной запрос (SQLite LIMIT/OFFSET исправлен)
$sql = "
    SELECT r.*, u.full_name, u.email, u.phone
    FROM meter_readings r 
    JOIN users u ON r.user_id = u.id 
    $where_clause
    ORDER BY r.reading_date DESC 
    LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params); 
$readings = $stmt->fetchAll();

// 3. Месяцы для фильтра (SQLite синтаксис)
$months_stmt = $pdo->query("
    SELECT DISTINCT strftime('%Y-%m', month_year) as month 
    FROM meter_readings 
    ORDER BY month DESC
");
$available_months = $months_stmt->fetchAll();

// 4. Список квартир
$apartments_stmt = $pdo->query("SELECT DISTINCT apartment FROM meter_readings ORDER BY apartment");
$available_apartments = $apartments_stmt->fetchAll();
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Журнал показаний</title>
    <link rel="stylesheet" href="style_new.css?v=<?= time() ?>">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; position: sticky; top: 0; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f5f5f5; }
        .filters { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .filter-group { display: inline-block; margin-right: 15px; }
        label { font-weight: bold; margin-right: 5px; }
        .pagination { margin-top: 20px; text-align: center; }
        .pagination a, .pagination span { 
            display: inline-block; 
            padding: 5px 10px; 
            margin: 0 2px; 
            border: 1px solid #ddd; 
            text-decoration: none;
        }
        .pagination a:hover { background: #eee; }
        .pagination .current { background: #007bff; color: white; border-color: #007bff; }
        .export-btn { background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .export-btn:hover { background: #218838; }
    </style>
</head>
<body>
<?php render_header(); ?>
<div class="container">
    <h1>Журнал показаний счетчиков</h1>
    
    <!-- Фильтры -->
    <div class="filters">
        <form method="get" action="">
            <div class="filter-group">
                <label>Месяц:</label>
                <select name="month" onchange="this.form.submit()">
                    <option value="">Все месяцы</option>
                    <?php foreach ($available_months as $m): ?>
                        <option value="<?= $m['month'] ?>" 
                            <?= ($month_filter == $m['month']) ? 'selected' : '' ?>>
                            <?= date('F Y', strtotime($m['month'] . '-01')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Квартира:</label>
                <select name="apartment" onchange="this.form.submit()">
                    <option value="">Все квартиры</option>
                    <?php foreach ($available_apartments as $apt): ?>
                        <option value="<?= $apt['apartment'] ?>" 
                            <?= ($apartment_filter == $apt['apartment']) ? 'selected' : '' ?>>
                            Кв. <?= $apt['apartment'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="export-btn">Применить</button>
            <a href="?" class="export-btn">Сбросить</a>
            
            <!-- Кнопка экспорта -->
            <button type="button" class="export-btn" onclick="exportToExcel()">
                📊 Экспорт в Excel
            </button>
        </form>
    </div>
    
    <!-- Информация о фильтрах -->
    <p>Найдено записей: <strong><?= $total ?></strong></p>
    
    <!-- Таблица -->
    <table id="readingsTable">
        <thead>
            <tr>
                <th>Дата отправки</th>
                <th>Месяц</th>
                <th>Кв.</th>
                <th>Жилец</th>
                <th>Телефон</th>
                <th>ХВС (м³)</th>
                <th>ГВС (м³)</th>
                <th>Электро. (кВт·ч)</th>
                <th>Суммарно (м³)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($readings as $row): 
                $total_water = $row['cold_water'] + $row['hot_water'];
            ?>
            <tr>
                <td><?= date('d.m.Y H:i', strtotime($row['reading_date'])) ?></td>
                <td><?= date('m.Y', strtotime($row['month_year'])) ?></td>
                <td><?= htmlspecialchars($row['apartment']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['phone'] ?? '') ?></td>
                <td><?= number_format($row['cold_water'], 3, ',', ' ') ?></td>
                <td><?= number_format($row['hot_water'], 3, ',', ' ') ?></td>
                <td><?= number_format($row['electricity'], 3, ',', ' ') ?></td>
                <td><strong><?= number_format($total_water, 3, ',', ' ') ?></strong></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($readings)): ?>
                <tr><td colspan="9">Показаний нет.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Пагинация -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">««</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">«</a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <?php if ($i == $page): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">»</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">»»</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function exportToExcel() {
    let table = document.getElementById('readingsTable');
    let html = table.outerHTML;
    
    // Создаем Blob и скачиваем
    let blob = new Blob(['\ufeff', html], {
        type: 'application/vnd.ms-excel'
    });
    
    let link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'Показания_счетчиков_<?= date('Y-m-d') ?>.xls';
    link.click();
}
</script>
</body>
</html>