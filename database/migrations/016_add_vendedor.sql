-- Datos de vendedor (para SAE) y asignación de vendedor a clientes.
ALTER TABLE usuarios
    ADD COLUMN clave_vendedor VARCHAR(20) DEFAULT NULL AFTER clave_sae,
    ADD COLUMN comision DECIMAL(5,2) DEFAULT NULL AFTER clave_vendedor,
    ADD COLUMN vendedor_id INT UNSIGNED DEFAULT NULL AFTER comision,
    ADD KEY idx_usuarios_vendedor (vendedor_id),
    ADD CONSTRAINT fk_usuarios_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE;
