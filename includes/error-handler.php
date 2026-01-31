<?php
include_once __DIR__ . '/nav.php';
/**
 * Global error handler for LankanLens
 * - Logs errors to /logs/errors.log
 * - Displays user-friendly error pages
 */

function logErrorMessage($message) {
    $logFile = __DIR__ . '/../logs/errors.log';
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message" . PHP_EOL, 3, $logFile);
}

function renderErrorPage($title, $message, $code = 500) {
    http_response_code($code);

    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title) . '</title>';
    echo '<script src="https://cdn.tailwindcss.com"></script>';
    echo '</head>';
    echo '<body class="bg-gray-50 text-gray-800">';
    echo '<div class="min-h-screen flex items-center justify-center px-6">';
    echo '<div class="max-w-xl text-center">';
    echo '<h1 class="text-3xl font-bold mb-4">' . htmlspecialchars($title) . '</h1>';
    echo '<p class="text-gray-600 mb-6">' . htmlspecialchars($message) . '</p>';
    echo '<a href="' . BASE_URL . 'public/index.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">Return Home</a>';
    echo '</div>';
    echo '</div>';
    echo '</body>';
    echo '</html>';
}

function renderNotFoundPage() {
    renderErrorPage('404 Not Found', 'The page you requested could not be found.', 404);
}

function renderGenericErrorPage() {
    renderErrorPage('Something went wrong', 'An unexpected error occurred. Please try again later.', 500);
}

function handlePhpError($severity, $message, $file, $line) {
    $errorMessage = "PHP Error [$severity]: $message in $file on line $line";
    logErrorMessage($errorMessage);

    if (!(error_reporting() & $severity)) {
        return false;
    }

    renderGenericErrorPage();
    exit;
}

function handleException($exception) {
    $errorMessage = 'Uncaught Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine();
    logErrorMessage($errorMessage);

    if ($exception instanceof PDOException) {
        logErrorMessage('PDOException Code: ' . $exception->getCode());
    }

    renderGenericErrorPage();
    exit;
}

function handleShutdown() {
    $error = error_get_last();
    if ($error !== null) {
        $errorMessage = "Shutdown Error [{$error['type']}]: {$error['message']} in {$error['file']} on line {$error['line']}";
        logErrorMessage($errorMessage);
        renderGenericErrorPage();
        exit;
    }
}

set_error_handler('handlePhpError');
set_exception_handler('handleException');
register_shutdown_function('handleShutdown');
