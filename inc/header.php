<?php
function render_header(): void {
    $role = $_SESSION['role'] ?? 'guest';
    $name = $_SESSION['full_name'] ?? 'Гость';
    ?>
    <header class="main-header">
        <nav class="navbar">
            <div class="nav-left">
                <a href="index.php" class="logo">
                    <span class="logo-icon">🏠</span> 
                    <span class="logo-text">ТСЖ "Наш Дом"</span>
                </a>
            </div>

            <div class="nav-menu">
                <?php if ($role !== 'guest'): ?>
                    <a href="index.php" class="nav-link">Главная</a>

                    <?php if ($role === 'admin'): ?>
                        <a href="admin-readings.php" class="nav-link admin-link">📊 Все показания</a>
                        <a href="admin-requests.php" class="nav-link admin-link">📋 Все заявки</a>
                    <?php else: ?>
                        <a href="meter-submit.php" class="nav-link">⚡ Сдать показания</a>
                        <a href="my-requests.php" class="nav-link">📩 Мои заявки</a>
                    <?php endif; ?>

                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($name) ?></span>
                        <a href="logout.php" class="logout-btn">Выйти</a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Войти</a>
                    <a href="register.php" class="nav-link auth-btn">Регистрация</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <?php
}