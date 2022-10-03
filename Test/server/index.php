<?php
declare(strict_types=1);

$return = [];
foreach ($_SERVER as $k => $v) {
    $return[$k] = $v;
}
foreach ($_GET as $k => $v) {
    $return[$k] = $v;
}

if (preg_match('/^\/status\/(\d{3})(\?.*)?$/', $_SERVER["REQUEST_URI"], $matches)) {
    $code = (int)$matches[1];
    if ($code < 100 || $code >= 600) {
        $code = 500;
    }
    if ($code >= 300 && $code < 400) {
        if (in_array(strtolower($_GET['r'] ?? ''), ['1', 'true', 'y', 'yes', 'o', 'on'])) {
            header('Location: /');
        } elseif (isset($_GET['r'])) {
            header('Location: ' . $_GET['r']);
        }
    }
    http_response_code($code);
}

$return['X-MESSAGE'] = 'message';

if (preg_match('/^\/timeout\/(\d+)$/', $_SERVER["REQUEST_URI"], $matches)) {
    $timeout = (int)$matches[1];
    $return['X-TIMEOUT'] = '$timeouts';
    sleep($timeout);
}

header('Content-type: application/json');
echo json_encode($return);
