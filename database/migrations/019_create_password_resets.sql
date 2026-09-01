-- Tokens para restablecer contraseña. Se guarda el hash SHA-256 del token,
-- nunca el token en claro. Un solo uso y con caducidad (expira_en).
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email       VARCHAR(191) NOT NULL,
    token_hash  VARCHAR(64)  NOT NULL,
    expira_en   DATETIME     NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token (token_hash),
    KEY idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
