<?php

require_once __DIR__ . '/../Config/conexion.php';

class Actividad
{
    private $conexion;

    public function __construct()
    {
        $conexionDb = new Conexion();
        $this->conexion = $conexionDb->conectar();
    }

    public function obtenerActividades()
    {
        $sql = "SELECT * FROM actividades
                ORDER BY fechaEvento ASC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function guardar($datos)
    {
        $sql = "INSERT INTO actividades
                (nombreActividad, fechaEvento, municipio)
                VALUES
                (:nombreActividad, :fechaEvento, :municipio)";

        $consulta = $this->conexion->prepare($sql);

        return $consulta->execute([
            ':nombreActividad' => $datos['nombreActividad'],
            ':fechaEvento' => $datos['fechaEvento'],
            ':municipio' => $datos['municipio']
        ]);
    }

    public function cantidadActividades($anio)
    {
        $sql = "SELECT COUNT(*) AS cantidad
                FROM actividades
                WHERE YEAR(fechaEvento) = :anio";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':anio' => $anio
        ]);

        return $consulta->fetch();
    }

    public function filtrarMunicipio($municipio)
    {
        $sql = "SELECT * FROM actividades
                WHERE municipio = :municipio
                ORDER BY fechaEvento ASC";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':municipio' => $municipio
        ]);

        return $consulta->fetchAll();
    }

    public function editar($datos)
    {
        $sql = "UPDATE actividades SET
                nombreActividad = :nombreActividad,
                fechaEvento = :fechaEvento,
                municipio = :municipio,
                totalRecaudado = :totalRecaudado
                WHERE idActividad = :idActividad";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':nombreActividad' => $datos['nombreActividad'],
            ':fechaEvento' => $datos['fechaEvento'],
            ':municipio' => $datos['municipio'],
            ':totalRecaudado' => $datos['totalRecaudado'],
            ':idActividad' => $datos['idActividad']
        ]);

        if ($consulta->rowCount() > 0) {
            return true;
        }

        $verificar = $this->conexion->prepare(
            "SELECT idActividad FROM actividades
            WHERE idActividad = :idActividad"
        );

        $verificar->execute([
            ':idActividad' => $datos['idActividad']
        ]);

        if ($verificar->fetch()) {
            return true;
        }

        return false;
    }

    public function eliminar($idActividad)
    {
        $sql = "DELETE FROM actividades
                WHERE idActividad = :idActividad";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':idActividad' => $idActividad
        ]);

        return $consulta->rowCount() > 0;
    }
}