<?php

require_once __DIR__ . '/../Config/conexion.php';

class Participacion
{
    private $conexion;

    public function __construct()
    {
        $conexionDb = new Conexion();
        $this->conexion = $conexionDb->conectar();
    }

    public function obtenerActividades()
    {
        $sql = "SELECT idActividad, nombreActividad,
                       fechaEvento, municipio
                FROM actividades
                ORDER BY fechaEvento DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function registrar($documento, $idActividad)
    {
        // Buscar al asociado

        $sql = "SELECT idAsociado
                FROM asociados
                WHERE documento = :documento";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':documento' => $documento
        ]);

        $asociado = $consulta->fetch();

        if (!$asociado) {
            http_response_code(404);
            throw new Exception("Asociado no encontrado");
        }

        $idAsociado = $asociado["idAsociado"];

        // Verificar que exista la actividad

        $sql = "SELECT fechaEvento
                FROM actividades
                WHERE idActividad = :idActividad";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':idActividad' => $idActividad
        ]);

        $actividad = $consulta->fetch();

        if (!$actividad) {
            http_response_code(404);
            throw new Exception("Actividad no encontrada");
        }

        // Validar que la actividad no haya finalizado

        $hoy = (new DateTimeImmutable(
            "now",
            new DateTimeZone("America/Bogota")
        ))->format("Y-m-d");

        if ($actividad["fechaEvento"] <=$hoy) {
            http_response_code(400);
            throw new Exception(
                "Las inscripciones se cierran un dia antes de la actividad "
            );
        }

        // Evitar participaciones repetidas

        $sql = "SELECT idParticipacion
                FROM actividades_asociado
                WHERE idAsociado = :idAsociado
                AND idActividad = :idActividad";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':idAsociado' => $idAsociado,
            ':idActividad' => $idActividad
        ]);

        if ($consulta->fetch()) {
            http_response_code(409);
            throw new Exception(
                "El asociado ya participa en esta actividad"
            );
        }

        // Guardar la participación

        $sql = "INSERT INTO actividades_asociado
                (idAsociado, idActividad)
                VALUES (:idAsociado, :idActividad)";

        $consulta = $this->conexion->prepare($sql);

        return $consulta->execute([
            ':idAsociado' => $idAsociado,
            ':idActividad' => $idActividad
        ]);
    }

    // Consulta 11

    public function cantidadPorDocumento($documento)
    {
        $sql = "SELECT a.documento, a.nombres,
                       a.apellidos,
                       COUNT(DISTINCT aa.idActividad) AS cantidad
                FROM asociados a
                LEFT JOIN actividades_asociado aa
                ON a.idAsociado = aa.idAsociado
                WHERE a.documento = :documento
                GROUP BY a.idAsociado,
                         a.documento,
                         a.nombres,
                         a.apellidos";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':documento' => $documento
        ]);

        return $consulta->fetch();
    }

    // Consulta 14

    public function participantesPorActividad($nombre)
    {
        $sql = "SELECT ac.idActividad,
                       ac.nombreActividad,
                       a.documento,
                       a.nombres,
                       a.apellidos
                FROM actividades ac
                LEFT JOIN actividades_asociado aa
                ON ac.idActividad = aa.idActividad
                LEFT JOIN asociados a
                ON aa.idAsociado = a.idAsociado
                WHERE ac.nombreActividad LIKE :nombre
                ORDER BY ac.nombreActividad, a.nombres";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':nombre' => '%' . $nombre . '%'
        ]);

        return $consulta->fetchAll();
    }
}