
<?php

// Load .env for local development only.
// Render environment variables take priority.
$envFile = __DIR__ . "/.env";

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if (
            $line === "" ||
            str_starts_with($line, "#") ||
            !str_contains($line, "=")
        ) {
            continue;
        }

        [$key, $value] = explode("=", $line, 2);

        $key = trim($key);
        $value = trim($value);
        $value = trim($value, "\"'");

        if ($key !== "" && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
        (($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https")
    );

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => $isSecure,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

// Database configuration
$dbHost = getenv("DB_HOST") ?: "localhost";
$dbPort = (int)(getenv("DB_PORT") ?: 3306);
$dbName = getenv("DB_NAME") ?: "db_website";
$dbUser = getenv("DB_USER") ?: "root";
$dbPass = getenv("DB_PASSWORD") ?: "";

// Create database connection
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = mysqli_init();

    // Configure Aiven SSL when a CA certificate is provided.

    $sslCa = getenv("DB_SSL_CA");

    if ($sslCa) {
        if (!str_starts_with($sslCa, "/")) {
            $sslCa = __DIR__ . "/" . $sslCa;
        }

        if (!is_file($sslCa)) {
            throw new RuntimeException("Aiven CA certificate not found.");
        }

        $conn->ssl_set(null, null, $sslCa, null, null);

        $conn->real_connect(
            $dbHost,
            $dbUser,
            $dbPass,
            $dbName,
            $dbPort,
            null,
            MYSQLI_CLIENT_SSL
        );
    } else {
        $conn->real_connect(
            $dbHost,
            $dbUser,
            $dbPass,
            $dbName,
            $dbPort
        );
    }

    $conn->set_charset("utf8mb4");
} catch (Throwable $e) {
    error_log("Database connection error: " . $e->getMessage());

    http_response_code(500);
    exit("Database connection failed. Please try again later.");
}

?>
