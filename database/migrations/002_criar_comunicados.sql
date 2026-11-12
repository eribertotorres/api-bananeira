USE consultoria_bananeira;

CREATE TABLE comunicados (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(150) NOT NULL,
    mensagem TEXT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 0,
    prioridade INT NOT NULL DEFAULT 0,
    inicio DATETIME NULL,
    fim DATETIME NULL,
    criado_em DATETIME NOT NULL,
    atualizado_em DATETIME NOT NULL,

    PRIMARY KEY (id),
    KEY idx_comunicados_selecao (ativo, prioridade, criado_em),

    CONSTRAINT chk_comunicados_periodo
        CHECK (inicio IS NULL OR fim IS NULL OR fim > inicio)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;