<?php

declare(strict_types=1);

ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

function responderJson(int $status, array $conteudo): void
{
    http_response_code($status);

    echo json_encode(
        $conteudo,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
}

try {
    $metodo = $_SERVER['REQUEST_METHOD'];
    $caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$rotas = [
    '/' => 'GET',
    '/api/v1/health' => 'GET',
    '/api/v1/auth/login' => 'POST',
    '/api/v1/auth/me' => 'GET',
];

    if (!isset($rotas[$caminho])) {
        responderJson(404, [
            'error' => [
                'code' => 'RESOURCE_NOT_FOUND',
                'message' => 'Recurso não encontrado.',
            ],
        ]);
        exit;
    }

    if ($metodo !== $rotas[$caminho]) {
        header('Allow: ' . $rotas[$caminho]);

        responderJson(405, [
            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'Método não permitido.',
            ],
        ]);
        exit;
    }

    if ($caminho === '/api/v1/auth/login') {
        require dirname(__DIR__) . '/src/Modules/Auth/login.php';
        exit;
    }

    if ($caminho === '/api/v1/auth/me') {
    require dirname(__DIR__) . '/src/Modules/Auth/me.php';
    exit;
    }
    
    responderJson(200, [
        'data' => [
            'status' => 'ok',
            'service' => 'consultoria-bananeira-api',
        ],
    ]);
} catch (Throwable $erro) {
    // Registra o tipo da falha, sem senha, token ou SQL.
    error_log('Falha na API: ' . get_class($erro));

    responderJson(500, [
        'error' => [
            'code' => 'INTERNAL_ERROR',
            'message' => 'Não foi possível concluir a operação.',
        ],
    ]);
}