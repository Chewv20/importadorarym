<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

class Modal extends Model
{
    /** Clave de caché del modal que ve el sitio público. */
    public const CACHE_KEY = 'sitio.modal_activo';

    /** Red de seguridad por si se edita la tabla fuera del panel. */
    private const CACHE_TTL = 3600;

    /**
     * El modal activo a mostrar en el sitio (el de menor orden), cacheado:
     * este dato se pedía en CADA página pública, incluidas las de visitantes
     * anónimos. El panel invalida la caché al guardar (ver Admin\ModalController).
     */
    public function activoParaMostrar(): ?array
    {
        return Cache::remember(self::CACHE_KEY, function () {
            $row = $this->db->query(
                "SELECT * FROM modales WHERE activo = 1 ORDER BY orden, id DESC LIMIT 1"
            )->fetch();
            return $row ?: null;
        }, self::CACHE_TTL);
    }

    /** Descarta la caché del sitio. Llamar tras cualquier escritura. */
    public static function olvidarCache(): void
    {
        Cache::olvidar(self::CACHE_KEY);
    }

    public function todos(): array
    {
        return $this->db->query("SELECT * FROM modales ORDER BY orden, id DESC")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM modales WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function crear(array $data): int
    {
        $this->db->prepare(
            "INSERT INTO modales (titulo, imagen, enlace, activo, orden)
             VALUES (:titulo, :imagen, :enlace, :activo, :orden)"
        )->execute([
            ':titulo' => $data['titulo'],
            ':imagen' => $data['imagen'],
            ':enlace' => $data['enlace'] ?? null,
            ':activo' => !empty($data['activo']) ? 1 : 0,
            ':orden'  => (int) ($data['orden'] ?? 0),
        ]);
        self::olvidarCache();
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): void
    {
        // La imagen solo se actualiza si se subió una nueva.
        $set = "titulo = :titulo, enlace = :enlace, activo = :activo, orden = :orden";
        $params = [
            ':titulo' => $data['titulo'],
            ':enlace' => $data['enlace'] ?? null,
            ':activo' => !empty($data['activo']) ? 1 : 0,
            ':orden'  => (int) ($data['orden'] ?? 0),
            ':id'     => $id,
        ];
        if (!empty($data['imagen'])) {
            $set .= ", imagen = :imagen";
            $params[':imagen'] = $data['imagen'];
        }
        $this->db->prepare("UPDATE modales SET {$set} WHERE id = :id")->execute($params);
        self::olvidarCache();
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM modales WHERE id = ?")->execute([$id]);
        self::olvidarCache();
    }
}
