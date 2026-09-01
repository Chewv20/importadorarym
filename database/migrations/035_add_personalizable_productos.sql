-- Marca los productos que admiten personalización/impresión con el logo del
-- cliente (la landing ya promueve este servicio en /personalizacion). Mismo
-- patrón que `destacado`: booleano simple, con su propio distintivo en el
-- catálogo público.

ALTER TABLE productos
    ADD COLUMN personalizable TINYINT(1) NOT NULL DEFAULT 0 AFTER destacado;
