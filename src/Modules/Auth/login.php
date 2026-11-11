<?php

declare(strict_types=1);

header('Cache-Control: no-store');

$tipo = strtolower(trim(
    explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]
));

if ($tipo !== 'application/json') {
    responderJson(415, [
        'error' => [
            'code' => 'UNSUPPORTED_MEDIA_TYPE',
            'message' => 'Envie o conteúdo como application/json.',
        ],
    ]);
    return;
}

$corpo = file_get_contents('php://input', false, null, 0, 8193);

if ($corpo === false || strlen($corpo) > 8192) {
    responderJson(413, [
        'error' => [
            'code' => 'PAYLOAD_TOO_LARGE',
            'message' => 'Conteúdo excede o limite permitido.',
        ],
    ]);
    return;
}

try {
    $dados = json_decode($corpo, false, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $erro) {
    responderJson(400, [
        'error' => [
            'code' => 'INVALID_JSON',
            'message' => 'JSON inválido.',
        ],
    ]);
    return;
}

if (
    !($dados instanceof stdClass) ||
    !isset($dados->email, $dados->password) ||
    !is_string($dados->email) ||
    !is_string($dados->password)
) {
    responderJson(422, [
        'error' => [
            'code' => 'VALIDATION_ERROR',
            'message' => 'Informe email e password como texto.',
        ],
    ]);
    return;
}

$email = strtolower(trim($dados->email));
$senha = $dados->password;

if (
    strlen($email) > 191 ||
    filter_var($email, FILTER_VALIDATE_EMAIL) === false ||
    strlen($senha) < 12 ||
    strlen($senha) > 72 ||
    strpos($senha, "\0") !== false
) {
    responderJson(422, [
        'error' => [
            'code' => 'VALIDATION_ERROR',
            'message' => 'Email ou formato da senha inválido.',
        ],
    ]);
    return;
}

$pdo = require dirname(__DIR__, 3) . '/config/database.php';

$consulta = $pdo->prepare(
    'SELECT id, nome, email, senha_hash, ativo
     FROM usuarios
     WHERE email = :email
     LIMIT 1'
);

$consulta->execute(['email' => $email]);
$usuario = $consulta->fetch();

// Hash fixo de comparação para também verificar senha
// quando o email não existe. Não representa uma conta.
$hashComparacao = $usuario
    ? $usuario['senha_hash']
    : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';

$senhaValida = password_verify($senha, $hashComparacao);

unset($senha, $dados);

if (!$usuario || !$senhaValida || (int) $usuario['ativo'] !== 1) {
    responderJson(401, [
        'error' => [
            'code' => 'INVALID_CREDENTIALS',
            'message' => 'Email ou senha inválidos.',
        ],
    ]);
    return;
}

$token = bin2hex(random_bytes(32));

$agora = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$expiracao = $agora->modify('+8 hours');

$inserir = $pdo->prepare(
    'INSERT INTO sessoes
        (usuario_id, token_hash, criado_em, expira_em)
     VALUES
        (:usuario_id, :token_hash, :criado_em, :expira_em)'
);

$inserir->execute([
    'usuario_id' => $usuario['id'],
    'token_hash' => hash('sha256', $token),
    'criado_em' => $agora->format('Y-m-d H:i:s'),
    'expira_em' => $expiracao->format('Y-m-d H:i:s'),
]);

responderJson(200, [
    'data' => [
        'token' => $token,
        'token_type' => 'Bearer',
        'expires_at' => $expiracao->format('Y-m-d\TH:i:s\Z'),
        'usuario' => [
            'id' => (int) $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
        ],
    ],
]);