-- Fase 7.4: la serie de SAE se determina por el día de la semana de la fecha
-- de entrega (L, M, X, J, V — no se permite capturar sábado/domingo). Cada
-- serie lleva su propio consecutivo independiente, incrementado automáticamente
-- al exportar y corregible a mano desde una pantalla de reconciliación.

ALTER TABLE pedido_envios
    ADD COLUMN fecha_entrega DATE DEFAULT NULL AFTER notas,
    ADD COLUMN sae_serie CHAR(1) DEFAULT NULL AFTER fecha_entrega,
    ADD COLUMN sae_consecutivo INT UNSIGNED DEFAULT NULL AFTER sae_serie;

CREATE TABLE IF NOT EXISTS sae_series_consecutivos (
    serie CHAR(1) NOT NULL,
    ultimo_consecutivo INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (serie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO sae_series_consecutivos (serie, ultimo_consecutivo) VALUES
    ('L', 0), ('M', 0), ('X', 0), ('J', 0), ('V', 0);
