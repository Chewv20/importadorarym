ALTER TABLE usuarios
    ADD COLUMN aprobado TINYINT(1) NOT NULL DEFAULT 0 AFTER activo;

-- Los administradores/editores existentes quedan aprobados automáticamente.
UPDATE usuarios SET aprobado = 1 WHERE rol IN ('admin', 'editor');
