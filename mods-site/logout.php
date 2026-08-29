<?php
/**
 * Страница выхода из системы
 * GameMods Platform
 */

require_once 'config.php';

destroySession();
redirect('index.html');
