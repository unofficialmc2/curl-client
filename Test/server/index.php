<?php
declare(strict_types=1);
function str_starts_with($haystack, $needle)
{
    return strpos($haystack, $needle) === 0;
}

if (preg_match('/^\/status\/(\d{3})$/', $_SERVER["REQUEST_URI"], $matches)) {
    $code = (int)$matches[1];
    if ($code < 100 || $code >= 600) {
        $code = 500;
    }
    http_response_code($code);
}
echo json_encode($_SERVER);
