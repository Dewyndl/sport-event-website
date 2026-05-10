<?php
require_once __DIR__ . '/../core/bootstrap.php';
Auth::logout();
header("Location: index.php");
exit;
