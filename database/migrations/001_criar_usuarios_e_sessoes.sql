USE consultoria_bananeira;

CREATE TABLE usuarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL,
    atualizado_em DATETIME NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sessoes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64)
        CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    criado_em DATETIME NOT NULL,
    expira_em DATETIME NOT NULL,
    revogado_em DATETIME NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_sessoes_token_hash (token_hash),
    KEY idx_sessoes_usuario (usuario_id),
    KEY idx_sessoes_expiracao (expira_em),

    CONSTRAINT fk_sessoes_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;