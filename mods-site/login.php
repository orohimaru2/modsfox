<?php
/**
 * Страница авторизации пользователей
 * GameMods Platform
 */

require_once 'config.php';

$errors = [];
$success = false;

// Если пользователь уже авторизован, перенаправляем в профиль
if (isLoggedIn()) {
    redirect('profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? ''); // Может быть username или email
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Проверка CSRF токена
    if (!verifyCsrfToken($csrfToken)) {
        $errors[] = 'Ошибка безопасности. Попробуйте снова.';
    }
    
    // Валидация ввода
    if (empty($login)) {
        $errors[] = 'Введите логин или email';
    }
    
    if (empty($password)) {
        $errors[] = 'Введите пароль';
    }
    
    // Поиск пользователя
    if (empty($errors)) {
        $pdo = getDbConnection();
        
        // Определяем, что введено - email или username
        if (isValidEmail($login)) {
            $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE email = ?");
        } else {
            $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ?");
        }
        
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $errors[] = 'Пользователь не найден';
        } elseif (!verifyPassword($password, $user['password_hash'])) {
            $errors[] = 'Неверный пароль';
        } else {
            // Успешная авторизация
            createSession($user['id']);
            
            // Установка remember me cookie если нужно
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 дней
                
                // Здесь можно сохранить токен в БД для remember me
            }
            
            redirect('profile.php');
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <!-- Header -->
    <header class="header auth-header">
        <div class="container">
            <nav class="navbar">
                <a href="index.html" class="logo">
                    <i class="fas fa-gamepad"></i>
                    <span><?= APP_NAME ?></span>
                </a>
                <div class="nav-actions">
                    <a href="register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Регистрация</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Login Form -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1><i class="fas fa-sign-in-alt"></i> Вход</h1>
                    <p>Войдите в свой аккаунт</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="form-group">
                        <label for="login">
                            <i class="fas fa-user"></i>
                            Логин или Email
                        </label>
                        <input 
                            type="text" 
                            id="login" 
                            name="login" 
                            placeholder="Введите логин или email"
                            value="<?= e($login ?? '') ?>"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Пароль
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Введите пароль"
                            required
                        >
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="remember">
                            <span>Запомнить меня</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Забыли пароль?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-sign-in-alt"></i>
                        Войти
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
                </div>

                <div class="social-auth">
                    <p>Или войдите через</p>
                    <div class="social-buttons">
                        <button class="btn-social btn-vk">
                            <i class="fab fa-vk"></i>
                            ВКонтакте
                        </button>
                        <button class="btn-social btn-google">
                            <i class="fab fa-google"></i>
                            Google
                        </button>
                        <button class="btn-social btn-discord">
                            <i class="fab fa-discord"></i>
                            Discord
                        </button>
                    </div>
                </div>

                <!-- Demo credentials hint -->
                <div class="demo-credentials">
                    <p><strong>Демо доступ:</strong></p>
                    <p>Логин: <code>admin</code></p>
                    <p>Пароль: <code>admin123</code></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer auth-footer-custom">
        <div class="container">
            <div class="footer-content">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
