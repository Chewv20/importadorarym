-- Auditoría final pre-producción (01/09/2026): cotizaciones.usuario_id/pedido_id
-- eran la única relación del esquema sin FK (arrastrado desde la migración 021,
-- que solo agregó el índice de usuario_id). Sin borrado físico de usuarios ni
-- pedidos hoy, pero deja el esquema consistente con el resto y evita huérfanos
-- silenciosos si eso cambia. Verificado sin huérfanos antes de aplicar.
ALTER TABLE cotizaciones
    ADD KEY idx_cot_pedido (pedido_id),
    ADD CONSTRAINT fk_cot_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_cot_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos (id) ON DELETE SET NULL ON UPDATE CASCADE;
