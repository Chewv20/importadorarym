-- Fase 7.10: productos que se producen por lote mínimo (ej. "1 millar" = 1000
-- piezas), sobre todo personalizables. NULL = comportamiento actual, sin
-- restricción. Con valor, las cantidades pedidas de ese producto se redondean
-- hacia arriba al múltiplo más cercano (Producto::cantidadValida()).
ALTER TABLE productos
    ADD COLUMN piezas_por_presentacion INT UNSIGNED DEFAULT NULL AFTER unidad;
