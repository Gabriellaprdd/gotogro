<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = getenv('DB_HOST');
$username   = getenv('DB_USER');
$password   = getenv('DB_PASSWORD');
$dbname     = getenv('DB_NAME');
$port       = (int) (getenv('DB_PORT'));
$sslCa = getenv('DB_SSL_CA');

try {
    $conn = mysqli_init();

    if (!$conn) {
        throw new Exception('Unable to initialize MySQL connection.');
    }

    $conn->ssl_set(
        null,
        null,
        $sslCa,
        null,
        null
    );

    if (defined('MYSQLI_OPT_SSL_VERIFY_SERVER_CERT')) {
        $conn->options(
            MYSQLI_OPT_SSL_VERIFY_SERVER_CERT,
            true
        );
    }

    $conn->real_connect(
        $servername,
        $username,
        $password,
        $dbname,
        $port,
        null,
        MYSQLI_CLIENT_SSL
    );

    $conn->set_charset('utf8mb4');

} catch (Throwable $e) {
    error_log('Database connection error: ' . $e->getMessage());

    http_response_code(500);
    exit('Database connection failed.');
}
?>