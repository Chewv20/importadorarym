<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

class ClienteLogo extends Model
{
    /** Clave de caché de la marquesina del sitio público. */
    public const CACHE_KEY = 'sitio.logos_clientes';

    /** Red de seguridad por si se edita la tabla fuera del panel. */
    private const CACHE_TTL = 3600;

    /**
     * Logos activos para la marquesina, cacheados: este dato se pedía en CADA
     * página pública. El panel invalida la caché al guardar
     * (ver Admin\ClienteLogoController).
     */
    public function activos(): array
    {
        return Cache::remember(self::CACHE_KEY, function () {
            return $this->db->query(
                "SELECT * FROM clientes_logos WHERE activo = 1 ORDER BY orden, id"
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
        return $this->db->query("SELECT * FROM clientes_logos ORDER BY orden, id")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM clientes_logos WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function crear(array $data): int
    {
        $this->db->prepare(
            "INSERT INTO clientes_logos (nombre, imagen, activo, orden)
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
        $this->db->prepare("UPDATE clientes_logos SET {$set} WHERE id = :id")->execute($params);
        self::olvidarCache();
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM clientes_logos WHERE id = ?")->execute([$id]);
        self::olvidarCache();
    }
}
