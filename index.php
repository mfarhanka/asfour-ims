<?php
// index.php
$page = $_GET['p'] ?? 'dashboard';  // e.g. ?p=dashboard
$view = __DIR__ . "/pages/{$page}.php";
if (!file_exists($view)) $view = __DIR__ . "/pages/dashboard.php";
include __DIR__ . "/layouts/main.php";
