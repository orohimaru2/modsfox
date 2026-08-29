<?php
/**
 * Страница профиля пользователя
 * GameMods Platform
 */

require_once 'config.php';

// Проверка авторизации
if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getCurrentUser();
$errors = [];
$success = false;
$successMessage = '';

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCsrfToken($csrfToken)) {
        $errors[] = 'Ошибка безопасности. Попробуйте снова.';
    } else {
        $pdo = getDbConnection();
        
        // Обновление профиля
        if ($_POST['action'] === 'update_profile') {
            $email = trim($_POST['email'] ?? '');
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';
            
            // Валидация email
            if (empty($email)) {
                $errors[] = 'Введите email';
            } elseif (!isValidEmail($email)) {
                $errors[] = 'Введите корректный email адрес';
            } else {
                // Проверка, не занят ли email другим пользователем
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user['id']]);
                if ($stmt->fetch()) {
                    $errors[] = 'Этот email уже используется другим пользователем';
                }
            }
            
            // Смена пароля
            if (!empty($newPassword)) {
                if (strlen($newPassword) < 6) {
                    $errors[] = 'Новый пароль должен быть не менее 6 символов';
                } elseif ($newPassword !== $newPasswordConfirm) {
                    $errors[] = 'Новые пароли не совпадают';
                } elseif (empty($currentPassword)) {
                    $errors[] = 'Введите текущий пароль для смены пароля';
                } elseif (!verifyPassword($currentPassword, $user['password_hash'])) {
                    $errors[] = 'Неверный текущий пароль';
                }
            }
            
            // Загрузка аватара
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadAvatar($_FILES['avatar'], $user['id']);
                if ($uploadResult['success']) {
                    // Удаляем старый аватар если есть
                    if (!empty($user['avatar_url']) && file_exists(AVATAR_PATH . '/' . $user['avatar_url'])) {
                        unlink(AVATAR_PATH . '/' . $user['avatar_url']);
                    }
                    
                    // Обновляем путь к аватару в БД
                    $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                    $stmt->execute([$uploadResult['filename'], $user['id']]);
                    $user['avatar_url'] = $uploadResult['filename'];
                } else {
                    $errors[] = $uploadResult['message'];
                }
            }
            
            // Обновление данных
            if (empty($errors)) {
                if (!empty($newPassword)) {
                    $passwordHash = hashPassword($newPassword);
                    $stmt = $pdo->prepare("UPDATE users SET email = ?, password_hash = ? WHERE id = ?");
                    $stmt->execute([$email, $passwordHash, $user['id']]);
                    $successMessage = 'Профиль и пароль успешно обновлены!';
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                    $stmt->execute([$email, $user['id']]);
                    $successMessage = 'Профиль успешно обновлен!';
                }
                
                $user['email'] = $email;
                logUserActivity($user['id'], 'profile_update', 'Пользователь обновил свой профиль');
            }
        }
        
        // Удаление аккаунта
        if ($_POST['action'] === 'delete_account') {
            if (isAdmin()) {
                $errors[] = 'Нельзя удалить аккаунт администратора';
            } else {
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (!verifyPassword($confirmPassword, $user['password_hash'])) {
                    $errors[] = 'Неверный пароль';
                } else {
                    // Удаляем аватар
                    if (!empty($user['avatar_url']) && file_exists(AVATAR_PATH . '/' . $user['avatar_url'])) {
                        unlink(AVATAR_PATH . '/' . $user['avatar_url']);
                    }
                    
                    // Удаляем пользователя
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    destroySession();
                    redirect('index.html');
                }
            }
        }
    }
}

$csrfToken = generateCsrfToken();

// Получение статистики пользователя
$pdo = getDbConnection();

// Количество загруженных модов
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mods WHERE user_id = ?");
$stmt->execute([$user['id']]);
$modsCount = $stmt->fetch()['count'];

// Количество скачиваний всех модов пользователя
$stmt = $pdo->prepare("SELECT SUM(download_count) as total FROM mods WHERE user_id = ?");
$stmt->execute([$user['id']]);
$totalDownloads = $stmt->fetch()['total'] ?? 0;

// Количество комментариев/отзывов
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
$stmt->execute([$user['id']]);
$reviewsCount = $stmt->fetch()['count'];

// Последние моды пользователя
$stmt = $pdo->prepare("SELECT id, title, slug, download_count, rating_avg, created_at FROM mods WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$recentMods = $stmt->fetchAll();

// История активности
$stmt = $pdo->prepare("SELECT action_type, description, created_at FROM user_activity WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user['id']]);
$activityHistory = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - <?= e($user['username']) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.html" class="logo">
                    <i class="fas fa-gamepad"></i>
                    <span><?= APP_NAME ?></span>
                </a>
                
                <div class="nav-links">
                    <a href="index.html"><i class="fas fa-home"></i> Главная</a>
                    <a href="#"><i class="fas fa-gamepad"></i> Игры</a>
                    <a href="#"><i class="fas fa-download"></i> Моды</a>
                    <a href="#"><i class="fas fa-folder"></i> Категории</a>
                </div>
                
                <div class="nav-actions">
                    <a href="profile.php" class="btn btn-primary">
                        <i class="fas fa-user"></i>
                        <?= e($user['username']) ?>
                        <?php if ($user['role'] === 'admin'): ?>
                            <span class="badge badge-admin">ADMIN</span>
                        <?php elseif ($user['role'] === 'moderator'): ?>
                            <span class="badge badge-mod">MOD</span>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i></a>
                </div>
                
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Profile Section -->
    <section class="profile-section">
        <div class="container">
            <div class="profile-grid">
                <!-- Sidebar -->
                <aside class="profile-sidebar">
                    <div class="profile-card">
                        <div class="profile-avatar">
                            <img src="<?= getAvatarUrl($user) ?>" alt="<?= e($user['username']) ?>">
                            <?php if ($user['role'] === 'admin'): ?>
                                <div class="role-badge admin"><i class="fas fa-shield-alt"></i></div>
                            <?php elseif ($user['role'] === 'moderator'): ?>
                                <div class="role-badge moderator"><i class="fas fa-user-shield"></i></div>
                            <?php endif; ?>
                        </div>
                        <h2><?= e($user['username']) ?></h2>
                        <p class="profile-email"><?= e($user['email']) ?></p>
                        
                        <div class="profile-stats">
                            <div class="stat">
                                <span class="stat-value"><?= $modsCount ?></span>
                                <span class="stat-label">Модов</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= number_format($totalDownloads) ?></span>
                                <span class="stat-label">Скачиваний</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?= $reviewsCount ?></span>
                                <span class="stat-label">Отзывов</span>
                            </div>
                        </div>
                        
                        <div class="profile-meta">
                            <p><i class="fas fa-calendar"></i> Регистрация: <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
                            <p><i class="fas fa-clock"></i> На сайте <?= floor((time() - strtotime($user['created_at'])) / 86400) ?> дн.</p>
                        </div>
                    </div>
                    
                    <nav class="profile-nav">
                        <a href="#profile-settings" class="active"><i class="fas fa-user-cog"></i> Настройки</a>
                        <a href="#my-mods"><i class="fas fa-cube"></i> Мои моды</a>
                        <a href="#activity"><i class="fas fa-history"></i> Активность</a>
                        <?php if (isAdmin()): ?>
                            <a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Админ-панель</a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                    </nav>
                </aside>

                <!-- Main Content -->
                <main class="profile-content">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span><?= e($successMessage) ?></span>
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

                    <!-- Profile Settings -->
                    <div id="profile-settings" class="profile-section-card">
                        <h2><i class="fas fa-user-cog"></i> Настройки профиля</h2>
                        
                        <form method="POST" enctype="multipart/form-data" class="profile-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-group">
                                <label>Аватар</label>
                                <div class="avatar-upload">
                                    <img src="<?= getAvatarUrl($user) ?>" alt="Current avatar" class="current-avatar">
                                    <div class="avatar-upload-btn">
                                        <label for="avatar-input" class="btn btn-outline">
                                            <i class="fas fa-camera"></i> Изменить
                                        </label>
                                        <input type="file" id="avatar-input" name="avatar" accept="image/*">
                                        <small>JPG, PNG, GIF, WEBP (макс. 5MB)</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="username-display">Имя пользователя</label>
                                <input type="text" id="username-display" value="<?= e($user['username']) ?>" disabled>
                                <small>Имя пользователя нельзя изменить</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    value="<?= e($user['email']) ?>"
                                    required
                                >
                            </div>
                            
                            <hr class="divider">
                            
                            <h3>Смена пароля</h3>
                            <p class="form-hint">Оставьте пустым, чтобы не менять пароль</p>
                            
                            <div class="form-group">
                                <label for="current_password">Текущий пароль</label>
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password"
                                    autocomplete="current-password"
                                >
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password">Новый пароль</label>
                                    <input 
                                        type="password" 
                                        id="new_password" 
                                        name="new_password"
                                        minlength="6"
                                        autocomplete="new-password"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password_confirm">Подтверждение нового пароля</label>
                                    <input 
                                        type="password" 
                                        id="new_password_confirm" 
                                        name="new_password_confirm"
                                        minlength="6"
                                        autocomplete="new-password"
                                    >
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить изменения
                            </button>
                        </form>
                    </div>

                    <!-- My Mods -->
                    <div id="my-mods" class="profile-section-card">
                        <div class="section-header">
                            <h2><i class="fas fa-cube"></i> Мои моды</h2>
                            <a href="upload.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Добавить мод</a>
                        </div>
                        
                        <?php if (empty($recentMods)): ?>
                            <div class="empty-state">
                                <i class="fas fa-cube"></i>
                                <p>У вас пока нет загруженных модов</p>
                                <a href="upload.php" class="btn btn-primary">Загрузить первый мод</a>
                            </div>
                        <?php else: ?>
                            <div class="mods-list">
                                <?php foreach ($recentMods as $mod): ?>
                                    <div class="mod-item">
                                        <div class="mod-item-info">
                                            <h4><?= e($mod['title']) ?></h4>
                                            <div class="mod-item-meta">
                                                <span><i class="fas fa-download"></i> <?= number_format($mod['download_count']) ?></span>
                                                <span><i class="fas fa-star"></i> <?= number_format($mod['rating_avg'], 1) ?></span>
                                                <span><i class="fas fa-calendar"></i> <?= date('d.m.Y', strtotime($mod['created_at'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="mod-item-actions">
                                            <a href="mod.php?id=<?= $mod['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
                                            <a href="edit-mod.php?id=<?= $mod['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i></a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Activity History -->
                    <div id="activity" class="profile-section-card">
                        <h2><i class="fas fa-history"></i> История активности</h2>
                        
                        <?php if (empty($activityHistory)): ?>
                            <div class="empty-state">
                                <i class="fas fa-history"></i>
                                <p>История активности пуста</p>
                            </div>
                        <?php else: ?>
                            <div class="activity-list">
                                <?php foreach ($activityHistory as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <?php
                                            $icon = 'fa-circle';
                                            switch ($activity['action_type']) {
                                                case 'login': $icon = 'fa-sign-in-alt'; break;
                                                case 'logout': $icon = 'fa-sign-out-alt'; break;
                                                case 'register': $icon = 'fa-user-plus'; break;
                                                case 'profile_update': $icon = 'fa-user-cog'; break;
                                                case 'mod_upload': $icon = 'fa-upload'; break;
                                                case 'mod_download': $icon = 'fa-download'; break;
                                                case 'review': $icon = 'fa-star'; break;
                                            }
                                            ?>
                                            <i class="fas <?= $icon ?>"></i>
                                        </div>
                                        <div class="activity-info">
                                            <p><?= e($activity['description'] ?? ucfirst($activity['action_type'])) ?></p>
                                            <span class="activity-time"><i class="far fa-clock"></i> <?= date('d.m.Y H:i', strtotime($activity['created_at'])) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Delete Account -->
                    <div class="profile-section-card danger-zone">
                        <h2><i class="fas fa-exclamation-triangle"></i> Опасная зона</h2>
                        <p>Удаление аккаунта необратимо. Все ваши данные будут удалены.</p>
                        
                        <form method="POST" class="delete-form" onsubmit="return confirm('Вы уверены? Это действие нельзя отменить!')">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="action" value="delete_account">
                            
                            <div class="form-group">
                                <label for="confirm_password">Подтвердите пароль</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt"></i> Удалить аккаунт
                            </button>
                        </form>
                    </div>
                </main>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script>
        // Предпросмотр аватара при загрузке
        document.getElementById('avatar-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.current-avatar').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
