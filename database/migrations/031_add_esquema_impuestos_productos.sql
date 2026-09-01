-- Clave de esquema de impuestos de Aspel SAE, por artículo.
--
-- El importador de pedidos de SAE incluye la columna "Clave de esquema de
-- impuestos" y hasta ahora se llenaba con un único valor global (la variable
-- ERP_ESQUEMA_IMPUESTOS del .env). Como el esquema puede variar de un artículo a
-- otro, pasa a ser un dato del producto; el valor global queda como respaldo
-- para el catálogo que comparta esquema.
--
-- Es numérico: en SAE la clave del esquema es un entero (normalmente 1-99).

ALTER TABLE productos
    ADD COLUMN esquema_impuestos SMALLINT UNSIGNED NULL DEFAULT NULL AFTER clave_sae;
