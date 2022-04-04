<?php
declare(strict_types=1);

/**
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function str_starts_with(string $haystack, string $needle): bool
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
