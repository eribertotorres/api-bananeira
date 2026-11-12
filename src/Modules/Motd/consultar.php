<?php

declare(strict_types=1);

header('Cache-Control: no-store');

$pdo = require dirname(__DIR__, 3) . '/config/database.php';

$agora = gmdate('Y-m-d H:i:s');

$consulta = $pdo->prepare(
    'SELECT id, titulo, mensagem, ativo, inicio, fim
     FROM comunicados
     WHERE ativo = 1
       AND (inicio IS NULL OR inicio <= :agora_inicio)
       AND (fim IS NULL OR fim > :agora_fim)
     ORDER BY prioridade DESC, criado_em DESC, id DESC
     LIMIT 1'
);

$consulta->execute([
    'agora_inicio' => $agora,
    'agora_fim' => $agora,
]);

$comunicado = $consulta->fetch();

if (!$comunicado) {
    responderJson(200, ['data' => null]);
    return;
}

function dataMotdUtc(?string $valor): ?string
{
    if ($valor === null) {
        return null;
    }

    $data = new DateTimeImmutable(
        $valor,
        new DateTimeZone('UTC')
    );

    return $data->format('Y-m-d\TH:i:s\Z');
}

responderJson(200, [
    'data' => [
        'id' => 'remoto_' . $comunicado['id'],
        'titulo' => $comunicado['titulo'],
        'mensagem' => $comunicado['mensagem'],
        'ativo' => (bool) $comunicado['ativo'],
        'inicio' => dataMotdUtc($comunicado['inicio']),
        'fim' => dataMotdUtc($comunicado['fim']),
    ],
]);