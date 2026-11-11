<?php

declare(strict_types=1);

header('Cache-Control: no-store');

$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if ($authorization === '' && function_exists('getallheaders')) {
    foreach (getallheaders() as $nome => $valor) {
        if (strcasecmp($nome, 'Authorization') === 0) {
            $authorization = $valor;
            break;
        }
    }
}

if (!preg_match(
    '/\ABearer[ \t]+([a-f0-9]{64})\z/i',
    trim($authorization),
    $partes
)) {
    header('WWW-Authenticate: Bearer');

    responderJson(401, [
        'error' => [
            'code' => 'UNAUTHENTICATED',
            'message' => 'Informe um token de sessão válido.',
        ],
    ]);
    return;
}

$tokenHash = hash('sha256', $partes[1]);

unset($partes, $authorization);

$pdo = require dirname(__DIR__, 3) . '/config/database.php';

$comando = $pdo->prepare(
    'UPDATE sessoes
     SET revogado_em = :agora
     WHERE token_hash = :token_hash
       AND revogado_em IS NULL'
);

$comando->execute([
    'agora' => gmdate('Y-m-d H:i:s'),
    'token_hash' => $tokenHash,
]);

http_response_code(204);