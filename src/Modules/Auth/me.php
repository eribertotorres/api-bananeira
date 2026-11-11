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

function responderNaoAutorizado(): void
{
    header('WWW-Authenticate: Bearer');

    responderJson(401, [
        'error' => [
            'code' => 'UNAUTHENTICATED',
            'message' => 'Sessão inválida ou expirada. Faça login novamente.',
        ],
    ]);
}

if (!preg_match(
    '/\ABearer[ \t]+([a-f0-9]{64})\z/i',
    trim($authorization),
    $partes
)) {
    responderNaoAutorizado();
    return;
}

$token = $partes[1];
$tokenHash = hash('sha256', $token);

unset($token, $partes, $authorization);

$pdo = require dirname(__DIR__, 3) . '/config/database.php';

$consulta = $pdo->prepare(
    'SELECT
        u.id,
        u.nome,
        u.email,
        s.expira_em
     FROM sessoes AS s
     INNER JOIN usuarios AS u ON u.id = s.usuario_id
     WHERE s.token_hash = :token_hash
       AND s.revogado_em IS NULL
       AND s.expira_em > :agora
       AND u.ativo = 1
     LIMIT 1'
);

$consulta->execute([
    'token_hash' => $tokenHash,
    'agora' => gmdate('Y-m-d H:i:s'),
]);

$sessao = $consulta->fetch();

if (!$sessao) {
    responderNaoAutorizado();
    return;
}

$expiracao = new DateTimeImmutable(
    $sessao['expira_em'],
    new DateTimeZone('UTC')
);

responderJson(200, [
    'data' => [
        'usuario' => [
            'id' => (int) $sessao['id'],
            'nome' => $sessao['nome'],
            'email' => $sessao['email'],
        ],
        'expires_at' => $expiracao->format('Y-m-d\TH:i:s\Z'),
    ],
]);