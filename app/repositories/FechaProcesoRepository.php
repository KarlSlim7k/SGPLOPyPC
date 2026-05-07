<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class FechaProcesoRepository {
    private PDO $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function replaceForLicitacion(int $idLicitacion, array $fechas): void {
        $delete = $this->db->prepare('DELETE FROM fecha_proceso WHERE id_licitacion = :id_licitacion');
        $delete->execute(['id_licitacion' => $idLicitacion]);

        if (empty($fechas)) {
            return;
        }

        $insert = $this->db->prepare(
            'INSERT INTO fecha_proceso (id_licitacion, tipo_fecha, fecha_programada, fecha_real, observaciones) '
            . 'VALUES (:id_licitacion, :tipo_fecha, :fecha_programada, NULL, :observaciones)'
        );

        foreach ($fechas as $fecha) {
            $insert->execute([
                'id_licitacion' => $idLicitacion,
                'tipo_fecha' => $fecha['tipo_fecha'],
                'fecha_programada' => $fecha['fecha_programada'],
                'observaciones' => $fecha['observaciones'] ?? null,
            ]);
        }
    }
}
