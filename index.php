<?php
/* License: by cs.baguosps@gmail.com */
/**
 * GriView - Google Maps Review Manager & XLS Exporter
 * Front Controller & Router (MVC Architecture)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

// Muat konfigurasi
require_once __DIR__ . '/app/config/config.php';

// Ambil parameter controller dan action
$controllerName = $_GET['c'] ?? $_GET['controller'] ?? 'review';
$actionName = $_GET['a'] ?? $_GET['action'] ?? 'audit';

// Mapping controller
$controllers = [
    'review' => 'ReviewController',
    'store' => 'StoreController',
    'google' => 'GoogleMapsController',
    'analytics' => 'AnalyticsController',
    'extension' => 'ExtensionController',
    'settings' => 'SettingsController'
];

if (!isset($controllers[$controllerName])) {
    http_response_code(404);
    die("Controller '{$controllerName}' tidak ditemukan.");
}

$className = $controllers[$controllerName];
$controllerFile = __DIR__ . '/app/controllers/' . $className . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(404);
    die("File controller '{$controllerFile}' tidak ditemukan.");
}

require_once $controllerFile;

if (!class_exists($className)) {
    http_response_code(500);
    die("Kelas controller '{$className}' tidak ditemukan.");
}

$controllerInstance = new $className();

if (!method_exists($controllerInstance, $actionName)) {
    http_response_code(404);
    die("Method '{$actionName}' pada controller '{$className}' tidak ditemukan.");
}

// Eksekusi action
$controllerInstance->$actionName();
