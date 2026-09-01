<?php

namespace App\Models;

use App\Core\Model;

/**
 * Factura (PDF + XML) de un envío (Fase 7.6). Los archivos viven en
 * storage/facturas/ (fuera de la web); esta tabla solo guarda sus nombres —
 * la ruta absoluta se resuelve con App\Core\Upload::documentoRuta().
 */
class PedidoFactura extends Model
{
    public function crear(array $d): int
    {
        $this->db->prepare(
            "INSERT INTO pedido_facturas (envio_id, archivo_pdf, archivo_xml, subido_por)
             VALUES (?, ?, ?, ?)"
        )->execute([
            (int) $d['envio_id'],
            $d['archivo_pdf'],
            $d['archivo_xml'],
            $d['subido_por'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM pedido_facturas WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** Facturas de un envío, de la más reciente a la más antigua. */
    public function porEnvio(int $envioId): array
    {
        $st = $this->db->prepare("SELECT * FROM pedido_facturas WHERE envio_id = ? ORDER BY created_at DESC");
        $st->execute([$envioId]);
        return $st->fetchAll();
    }

    /**
     * Una factura con los datos del envío/pedido/cliente a los que pertenece
     * (para validar propiedad al descargar, tanto en el panel como en el portal).
     */
    public function conDetalle(int $facturaId): ?array
    {
        $st = $this->db->prepare(
            "SELECT pf.*, pe.pedido_id, p.usuario_id, p.folio
               FROM pedido_facturas pf
               JOIN pedido_envios pe ON pe.id = pf.envio_id
               JOIN pedidos p ON p.id = pe.pedido_id
              WHERE pf.id = ?"
        );
        $st->execute([$facturaId]);
        return $st->fetch() ?: null;
    }

    /** Todas las facturas de un pedido (de todos sus envíos), para el portal del cliente. */
    public function paraPedido(int $pedidoId): array
    {
        $st = $this->db->prepare(
            "SELECT pf.* FROM pedido_facturas pf
               JOIN pedido_envios pe ON pe.id = pf.envio_id
              WHERE pe.pedido_id = ? ORDER BY pf.created_at DESC"
        );
        $st->execute([$pedidoId]);
        return $st->fetchAll();
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM pedido_facturas WHERE id = ?")->execute([$id]);
    }
}
