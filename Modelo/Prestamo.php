<?php

require_once __DIR__ . '/../Config/conexion.php';

class Prestamo
{
    private $conexion;

    public function __construct()
    {
        $conexionDb = new Conexion();
        $this->conexion = $conexionDb->conectar();
    }

    public function guardar($datos)
    {
        $sql = "INSERT INTO prestamos
                (valorPrestamo, tasaInteres, numeroCuotas,
                fechaPrestamo, idAsociado)
                VALUES
                (:valorPrestamo, :tasaInteres, :numeroCuotas,
                :fechaPrestamo, :idAsociado)";

        $consulta = $this->conexion->prepare($sql);

        return $consulta->execute([
            ':valorPrestamo' => $datos['valorPrestamo'],
            ':tasaInteres' => $datos['tasaInteres'],
            ':numeroCuotas' => $datos['numeroCuotas'],
            ':fechaPrestamo' => $datos['fechaPrestamo'],
            ':idAsociado' => $datos['idAsociado']
        ]);
    }

    public function obtenerPrestamos()
    {
        $sql = "SELECT p.idPrestamo, p.valorPrestamo,
                p.tasaInteres, p.numeroCuotas,
                p.fechaPrestamo, p.idAsociado,
                a.documento, a.nombres, a.apellidos
                FROM prestamos p
                INNER JOIN asociados a
                ON p.idAsociado = a.idAsociado
                ORDER BY p.fechaPrestamo ASC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function cantidadPrestamos()
    {
        $sql = "SELECT COUNT(*) AS cantidad
                FROM prestamos";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetch();
    }

    public function filtrarFechas($fechaInicio, $fechaFin)
    {
        $sql = "SELECT p.idPrestamo, p.valorPrestamo,
                p.tasaInteres, p.numeroCuotas,
                p.fechaPrestamo, p.idAsociado,
                a.documento, a.nombres, a.apellidos
                FROM prestamos p
                INNER JOIN asociados a
                ON p.idAsociado = a.idAsociado
                WHERE DATE(p.fechaPrestamo)
                BETWEEN :fechaInicio AND :fechaFin";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':fechaInicio' => $fechaInicio,
            ':fechaFin' => $fechaFin
        ]);

        return $consulta->fetchAll();
    }

    public function filtrarDocumento($documento)
    {
        $sql = "SELECT p.idPrestamo, p.valorPrestamo,
                p.tasaInteres, p.numeroCuotas,
                p.fechaPrestamo, p.idAsociado,
                a.documento, a.nombres, a.apellidos
                FROM prestamos p
                INNER JOIN asociados a
                ON p.idAsociado = a.idAsociado
                WHERE a.documento = :documento";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':documento' => $documento
        ]);

        return $consulta->fetchAll();
    }

    public function buscarAsociado($documento)
    {
        $sql = "SELECT idAsociado, documento,
                nombres, apellidos
                FROM asociados
                WHERE documento = :documento";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':documento' => $documento
        ]);

        return $consulta->fetch();
    }

    public function editar($datos)
    {
        $sql = "UPDATE prestamos SET
                valorPrestamo = :valorPrestamo,
                tasaInteres = :tasaInteres,
                numeroCuotas = :numeroCuotas,
                fechaPrestamo = :fechaPrestamo
                WHERE idPrestamo = :idPrestamo";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':valorPrestamo' => $datos['valorPrestamo'],
            ':tasaInteres' => $datos['tasaInteres'],
            ':numeroCuotas' => $datos['numeroCuotas'],
            ':fechaPrestamo' => $datos['fechaPrestamo'],
            ':idPrestamo' => $datos['idPrestamo']
        ]);

        if ($consulta->rowCount() > 0) {
            return true;
        }

        $verificar = $this->conexion->prepare(
            "SELECT idPrestamo FROM prestamos
            WHERE idPrestamo = :idPrestamo"
        );

        $verificar->execute([
            ':idPrestamo' => $datos['idPrestamo']
        ]);

        if ($verificar->fetch()) {
            return true;
        }

        return false;
    }

    public function eliminar($idPrestamo)
    {
        $sql = "DELETE FROM prestamos
                WHERE idPrestamo = :idPrestamo";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':idPrestamo' => $idPrestamo
        ]);

        return $consulta->rowCount() > 0;
    }
}