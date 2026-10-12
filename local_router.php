<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = urldecode($uri);
$rootDir = __DIR__;

// 1. Check client assets (/client/...)
if (strpos($uri, '/client/') === 0) {
    $targetFile = $rootDir . $uri;
    if (file_exists($targetFile) && is_file($targetFile)) {
        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'json'  => 'application/json; charset=UTF-8',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'eot'   => 'application/vnd.ms-fontobject',
        ];
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }
        readfile($targetFile);
        return true;
    }
}

// 2. Check public directory static files
$publicFile = $rootDir . '/public' . $uri;
if (file_exists($publicFile) && is_file($publicFile) && !preg_match('/\.php$/i', $publicFile)) {
    return false; // Handled by PHP built-in server
}

// 3. API requests (/api/v1/...)
if (strpos($uri, '/api/v1') === 0) {
    chdir($rootDir . '/public/api/v1');
    $_SERVER['SCRIPT_NAME'] = '/api/v1/index.php';
    require $rootDir . '/public/api/v1/index.php';
    return true;
}

// 4. Portal requests (/portal/...)
if (strpos($uri, '/portal') === 0) {
    chdir($rootDir . '/public/portal');
    $_SERVER['SCRIPT_NAME'] = '/portal/index.php';
    require $rootDir . '/public/portal/index.php';
    return true;
}

// 5. Default application entry point
chdir($rootDir . '/public');
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $rootDir . '/public/index.php';
