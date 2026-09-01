<?php

namespace App\Models;

use App\Core\Model;

class Permiso extends Model
{
    public function todos(): array
    {
        return $this->db->query("SELECT * FROM permisos ORDER BY grupo, id")->fetchAll();
    }

    /** @return array<string, array> permisos agrupados por su columna `grupo` */
    public function agrupados(): array
    {
        $out = [];
        foreach ($this->todos() as $p) {
            $out[$p['grupo']][] = $p;
        }
        return $out;
    }

    /** @return array<int,string> permiso_id => clave (para validar overrides) */
    public function clavesPorId(): array
    {
        $out = [];
        foreach ($this->todos() as $p) {
            $out[(int) $p['id']] = $p['clave'];
        }
        return $out;
    }
}
