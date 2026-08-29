<?php
/**
 * Страница регистрации пользователей
 * GameMods Platform
 */

require_once 'config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Проверка CSRF токена
    if (!verifyCsrfToken($csrfToken)) {
        $errors[] = 'Ошибка безопасности. Попробуйте снова.';
    }
    
    // Валидация username
    if (empty($username)) {
        $errors[] = 'Введите имя пользователя';
    } elseif (!isValidUsername($username)) {
        $errors[] = 'Имя должно содержать от 3 до 50 символов (буквы, цифры, подчеркивание)';
    }
    
    // Валидация email
    if (empty($email)) {
        $errors[] = 'Введите email';
    } elseif (!isValidEmail($email)) {
        $errors[] = 'Введите корректный email адрес';
    }
    
    // Валидация пароля
    if (empty($password)) {
        $errors[] = 'Введите пароль';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не менее 6 символов';
    }
    
    // Проверка совпадения паролей
    if ($password !== $passwordConfirm) {
        $errors[] = 'Пароли не совпадают';
    }
    
    // Проверка на существование пользователя
    if (empty($errors)) {
        $pdo = getDbConnection();
        
        // Проверка username
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Пользователь с таким именем уже существует';
        }
        
        // Проверка email
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Этот email уже зарегистрирован';
            }
        }
    }
    
    // Регистрация пользователя
    if (empty($errors)) {
        try {
            $pdo = getDbConnection();
            $passwordHash = hashPassword($password);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $passwordHash, ROLE_USER]);
            
            $userId = $pdo->lastInsertId();
            
            // Логирование активности
            logUserActivity($userId, 'register', 'Новый пользователь зарегистрировался');
            
            $success = true;
            
            // Автоматический вход после регистрации
            createSession($userId);
            redirect('profile.php');
            
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                $errors[] = 'Ошибка базы данных: ' . $e->getMessage();
            } else {
                $errors[] = 'Произошла ошибка при регистрации. Попробуйте позже.';
            }
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
    <title>Регистрация - <?= e(APP_NAME) ?></title>
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
                    <a href="login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i> Вход</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Registration Form -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1><i class="fas fa-user-plus"></i> Регистрация</h1>
                    <p>Создайте аккаунт для доступа ко всем функциям</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span>Регистрация успешна! Перенаправление...</span>
                    </div>
                <?php endif; ?>

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

                <form method="POST" class="auth-form" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i>
                            Имя пользователя
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            placeholder="Придумайте логин"
                            value="<?= e($username ?? '') ?>"
                            required
                            minlength="3"
                            maxlength="50"
                            pattern="[a-zA-Z0-9_]+"
                        >
                        <small>От 3 до 50 символов (буквы, цифры, подчеркивание)</small>
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            Email
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="your@email.com"
                            value="<?= e($email ?? '') ?>"
                            required
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
                            placeholder="Минимум 6 символов"
                            required
                            minlength="6"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">
                            <i class="fas fa-lock"></i>
                            Подтверждение пароля
                        </label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            placeholder="Повторите пароль"
                            required
                        >
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="terms" required>
                            <span>Я согласен с <a href="#">условиями использования</a> и <a href="#">политикой конфиденциальности</a></span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-user-plus"></i>
                        Зарегистрироваться
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
                </div>

                <div class="social-auth">
                    <p>Или зарегистрируйтесь через</p>
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
    <script>
        // Валидация совпадения паролей
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Пароли не совпадают!');
                return false;
            }
        });
    </script>
</body>
</html>
