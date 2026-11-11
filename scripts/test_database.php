<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $pdo = require dirname(__DIR__) . '/config/database.php';

$banco = $pdo->query('SELECT DATABASE()')->fetchColumn();

echo 'Conexão PDO funcionando! Banco: ' . $banco . PHP_EOL;

} catch (Throwable $erro) {
    fwrite(
        STDERR,
        'Falha PDO [' . $erro->getCode() . ']: '
        . $erro->getMessage() . PHP_EOL
    );

    exit(1);
}