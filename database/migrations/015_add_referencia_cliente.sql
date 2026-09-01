-- "Su pedido": referencia/control interno que captura el propio cliente.
ALTER TABLE pedidos ADD COLUMN referencia_cliente VARCHAR(60) DEFAULT NULL AFTER folio;
