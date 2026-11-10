<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function responderJson(int $status, array $conteudo): void
{
    http_response_code($status);

    echo json_encode(
        $conteudo,
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
}

$metodo = $_SERVER['REQUEST_METHOD'];
$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$rotasConhecidas = ['/', '/api/v1/health'];

if (!in_array($caminho, $rotasConhecidas, true)) {
    responderJson(404, [
        'error' => [
            'code' => 'RESOURCE_NOT_FOUND',
            'message' => 'Recurso não encontrado.',
        ],
    ]);
    exit;
}

if ($metodo !== 'GET') {
    header('Allow: GET');

    responderJson(405, [
        'error' => [
            'code' => 'METHOD_NOT_ALLOWED',
            'message' => 'Método não permitido.',
        ],
    ]);
    exit;
}

responderJson(200, [
    'data' => [
        'status' => 'ok',
        'service' => 'consultoria-bananeira-api',
    ],
]);