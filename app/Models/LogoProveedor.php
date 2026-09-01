<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

class LogoProveedor extends Model
{
    /** Clave de caché de la grilla del sitio público. */
    public const CACHE_KEY = 'sitio.logos_proveedores';

    /** Red de seguridad por si se edita la tabla fuera del panel. */
    private const CACHE_TTL = 3600;

    /**
     * Logos activos para la grilla de marcas, cacheados: este dato se pedía en
     * cada carga del home y de "Nosotros". El panel invalida la caché al
     * guardar (ver Admin\LogoProveedorController).
     */
    public function activos(): array
    {
        return Cache::remember(self::CACHE_KEY, function () {
            return $this->db->query(
                "SELECT * FROM logos_proveedores WHERE activo = 1 ORDER BY orden, id"
            )->fetchAll();
        }, self::CACHE_TTL);
    }

    /** Descarta la caché del sitio. Llamar tras cualquier escritura. */
    public static function olvidarCache(): void
    {
        Cache::olvidar(self::CACHE_KEY);
    }

    public function todos(): array
    {
        return $this->db->query("SELECT * FROM logos_proveedores ORDER BY orden, id")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM logos_proveedores WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function crear(array $data): int
    {
        $this->db->prepare(
            "INSERT INTO logos_proveedores (nombre, imagen, activo, orden)
             VALUES (:nombre, :imagen, :activo, :orden)"
        )->execute([
            ':nombre' => $data['nombre'],
            ':imagen' => $data['imagen'],
            ':activo' => !empty($data['activo']) ? 1 : 0,
            ':orden'  => (int) ($data['orden'] ?? 0),
        ]);
        self::olvidarCache();
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): void
    {
        $set = "nombre = :nombre, activo = :activo, orden = :orden";
        $params = [
            ':nombre' => $data['nombre'],
            ':activo' => !empty($data['activo']) ? 1 : 0,
            ':orden'  => (int) ($data['orden'] ?? 0),
            ':id'     => $id,
        ];
        if (!empty($data['imagen'])) {
            $set .= ", imagen = :imagen";
            $params[':imagen'] = $data['imagen'];
        }
        $this->db->prepare("UPDATE logos_proveedores SET {$set} WHERE id = :id")->execute($params);
        self::olvidarCache();
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM logos_proveedores WHERE id = ?")->execute([$id]);
        self::olvidarCache();
    }
}
