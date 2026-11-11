<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function lerCampo(string $mensagem): string
{
    fwrite(STDOUT, $mensagem);

    $entrada = fgets(STDIN);

    if ($entrada === false) {
        throw new RuntimeException('Não foi possível ler a entrada.');
    }

    return rtrim($entrada, "\r\n");
}

try {
    $nome = trim(lerCampo('Nome: '));
    $email = strtolower(trim(lerCampo('Email: ')));

    // Neste script local, a senha digitada fica visível no terminal.
    $senha = lerCampo('Senha (visível neste terminal): ');
    $confirmacao = lerCampo('Confirme a senha: ');

    if ($nome === '' || mb_strlen($nome) > 150) {
        throw new RuntimeException('Informe um nome com até 150 caracteres.');
    }

    if (
        strlen($email) > 191 ||
        filter_var($email, FILTER_VALIDATE_EMAIL) === false
    ) {
        throw new RuntimeException('Informe um email válido.');
    }

    if (strlen($senha) < 12 || strlen($senha) > 72) {
        throw new RuntimeException('A senha deve ter entre 12 e 72 bytes.');
    }

    if ($senha !== $confirmacao) {
        throw new RuntimeException('As senhas não conferem.');
    }

    $hash = password_hash($senha, PASSWORD_DEFAULT);

    if ($hash === false) {
        throw new RuntimeException('Não foi possível proteger a senha.');
    }

    unset($senha, $confirmacao);

    $pdo = require dirname(__DIR__) . '/config/database.php';

    $agora = gmdate('Y-m-d H:i:s');

    $comando = $pdo->prepare(
        'INSERT INTO usuarios
            (nome, email, senha_hash, ativo, criado_em, atualizado_em)
         VALUES
            (:nome, :email, :senha_hash, 1, :criado_em, :atualizado_em)'
    );

    $comando->execute([
        'nome' => $nome,
        'email' => $email,
        'senha_hash' => $hash,
        'criado_em' => $agora,
        'atualizado_em' => $agora,
    ]);

    echo 'Usuário criado! ID: ' . $pdo->lastInsertId() . PHP_EOL;
} catch (PDOException $erro) {
    $duplicado = isset($erro->errorInfo[1])
        && (int) $erro->errorInfo[1] === 1062;

    fwrite(
        STDERR,
        $duplicado
            ? "Já existe um usuário com esse email.\n"
            : "Falha ao cadastrar usuário no banco de dados.\n"
    );

    exit(1);
} catch (Throwable $erro) {
    fwrite(STDERR, $erro->getMessage() . PHP_EOL);
    exit(1);
}