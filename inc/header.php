<?php
function render_header(): void {
    // Проверяем роль, чтобы не писать $_SESSION везде
    $role = $_SESSION['role'] ?? 'guest';
    $name = $_SESSION['full_name'] ?? 'Гость';
    ?>
    <header class="main-header">
        <nav class="navbar">
            <div class="nav-left">
                <a href="index.php" class="logo">🏠 ТСЖ "Наш Дом"</a>
            </div>

            <div class="nav-menu">
                <?php if ($role !== 'guest'): ?>
                    <!-- Общие ссылки -->
                    <a href="index.php">Главная</a>

                    <?php if ($role === 'admin'): ?>
                        <!-- Ссылки только для АДМИНА -->
                        <a href="admin-readings.php" class="admin-link">📊 Все показания</a>
                        <a href="admin-requests.php" class="admin-link">📋 Все заявки</a>
                    <?php else: ?>
                        <!-- Ссылки только для ЖИЛЬЦА -->
                        <a href="meter-submit.php">⚡ Сдать показания</a>
                        <a href="my-requests.php">📩 Мои заявки</a>
                    <?php endif; ?>

                    <a href="logout.php" class="logout-link">Выйти (<?= htmlspecialchars($name) ?>)</a>
                <?php else: ?>
                    <!-- Для тех, кто не вошел -->
                    <a href="login.php">Войти</a>
                    <a href="register.php">Регистрация</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <hr>
    <?php
}