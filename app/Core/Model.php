<?php

namespace App\Core;

use PDO;

/**
 * Modelo base. Expone la conexión PDO a las clases hijas.
 */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }
}
