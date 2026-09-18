-- Inventario / disponibilidad por producto, sincronizado desde Aspel SAE (Firebird).
-- existencia_sae: última existencia conocida (tabla INVE03, columna EXIST de SAE),
--   sincronizada por App\Core\DisponibilidadSync vía POST /integraciones/sae/disponibilidad.
--   NULL = nunca sincronizado (producto sin clave_sae, o la sync aún no lo ha tocado):
--   no debe mostrar ningún aviso, mismo comportamiento que hoy.
-- existencia_actualizada_en: solo informativo, para mostrar "actualizado hace X" en el
--   panel y notar si la sincronización dejó de correr.
-- disponibilidad_manual: override manual que SIEMPRE gana sobre existencia_sae — para
--   forzar "bajo pedido" en un artículo sin stock que igual se puede producir, sin que
--   la siguiente sincronización lo revierta. NULL = automático (se deriva de existencia_sae).

ALTER TABLE productos
    ADD COLUMN existencia_sae INT DEFAULT NULL AFTER piezas_minimas,
    ADD COLUMN existencia_actualizada_en DATETIME DEFAULT NULL AFTER existencia_sae,
    ADD COLUMN disponibilidad_manual ENUM('disponible','agotado','bajo_pedido') DEFAULT NULL AFTER existencia_actualizada_en;
