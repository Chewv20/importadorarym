-- Subcategorías: jerarquía de 2 niveles (categoría → subcategoría). Ej.:
--   Biodegradables
--     ├─ Bolsas
--     └─ Cubiertos
--   Desechables
--     └─ Vasos
--
-- Auto-referencia con ON DELETE SET NULL: si se borra una categoría padre,
-- sus subcategorías NO se eliminan, quedan como categorías principales. Es
-- más seguro que un CASCADE (que se llevaría también los productos de las
-- subcategorías al eliminar accidentalmente el padre).
--
-- El límite de 2 niveles (una subcategoría no puede tener padre con padre) se
-- valida en la aplicación (Admin\CategoriaController), no en el esquema.

ALTER TABLE categorias
    ADD COLUMN categoria_padre_id INT UNSIGNED NULL DEFAULT NULL AFTER id,
    ADD KEY idx_categorias_padre (categoria_padre_id),
    ADD CONSTRAINT fk_categorias_padre FOREIGN KEY (categoria_padre_id)
        REFERENCES categorias (id) ON DELETE SET NULL ON UPDATE CASCADE;
