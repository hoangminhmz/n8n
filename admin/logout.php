<?php
/**
 * LightBlog CMS - Logout
 */

session_start();

// Load config for BASE_PATH
require_once __DIR__ . '/../config.php';
$basePath = defined('BASE_PATH') ? BASE_PATH : '/';

session_destroy();

header('Location: ' . $basePath . 'admin/login.php');
exit;
