-- Cada anfitrión pertenece a una oficina. El kiosco de un dispositivo solo
-- ofrece los anfitriones de la oficina de ese dispositivo.
-- Pasos idempotentes (IF NOT EXISTS) para poder re-ejecutar sin romper.

ALTER TABLE anfitriones
    ADD COLUMN IF NOT EXISTS oficina_id INT UNSIGNED DEFAULT NULL AFTER area;
ALTER TABLE anfitriones
    ADD KEY IF NOT EXISTS idx_anfitriones_oficina (oficina_id);
ALTER TABLE anfitriones
    ADD FOREIGN KEY IF NOT EXISTS fk_anfitriones_oficina (oficina_id)
        REFERENCES oficinas (id) ON DELETE SET NULL ON UPDATE CASCADE;
