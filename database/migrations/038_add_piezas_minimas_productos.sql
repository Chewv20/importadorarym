-- Fase 7.10 (ajuste): "piezas por presentación" (ej. 50 = paquete de 50) y
-- "mínimo de piezas" (ej. 100) son conceptos distintos — un producto puede
-- venderse en paquetes de 50 pero exigir un mínimo de 2 paquetes (100 pzas).
-- NULL = sin mínimo adicional (solo aplica el redondeo de la presentación).
ALTER TABLE productos
    ADD COLUMN piezas_minimas INT UNSIGNED DEFAULT NULL AFTER piezas_por_presentacion;
