-- Claves de Aspel SAE para mapear clientes y artículos al exportar pedidos.
ALTER TABLE usuarios  ADD COLUMN clave_sae VARCHAR(30) DEFAULT NULL AFTER rfc;
ALTER TABLE productos ADD COLUMN clave_sae VARCHAR(30) DEFAULT NULL AFTER sku;
