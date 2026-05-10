<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/vk.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/CSRF.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/VKNotifier.php';
require_once __DIR__ . '/VKAuth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Member.php';
require_once __DIR__ . '/../models/ActionLog.php';

Auth::init();
$siteConfig = loadConfig();
