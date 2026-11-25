<?php

require_once __DIR__ . '/../Config/conexion.php';

class Pago
{
    private $conexion;

    public function __construct()
    {
        $conexionDb = new Conexion();
        $this->conexion = $conexionDb->conectar();
    }

    public function guardar($datos)
    {
        $sql = "INSERT INTO pagos_cuotas
                (valorCuota, fechaPago, idPrestamo)
                VALUES
                (:valorCuota, :fechaPago, :idPrestamo)";

        $consulta = $this->conexion->prepare($sql);

        return $consulta->execute([
            ':valorCuota' => $datos['valorCuota'],
            ':fechaPago' => $datos['fechaPago'],
            ':idPrestamo' => $datos['idPrestamo']
        ]);
    }

    public function obtenerPagos()
    {
        $sql = "SELECT pc.idPago, pc.valorCuota,
                pc.fechaPago, pc.idPrestamo,
                a.documento, a.nombres, a.apellidos
                FROM pagos_cuotas pc
                INNER JOIN prestamos p
                ON pc.idPrestamo = p.idPrestamo
                INNER JOIN asociados a
                ON p.idAsociado = a.idAsociado
                ORDER BY pc.fechaPago ASC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function totalPagado($documento)
    {
        $sql = "SELECT a.documento, a.nombres,
                a.apellidos,
                COALESCE(SUM(pc.valorCuota), 0) AS totalPagado
                FROM asociados a
                LEFT JOIN prestamos p
                ON a.idAsociado = p.idAsociado
                LEFT JOIN pagos_cuotas pc
                ON p.idPrestamo = pc.idPrestamo
                WHERE a.documento = :documento
                GROUP BY a.documento, a.nombres, a.apellidos";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':documento' => $documento
        ]);

        return $consulta->fetch();
    }

    public function filtrarPrestamo($idPrestamo)
    {
        $sql = "SELECT pc.idPago, pc.valorCuota,
                pc.fechaPago, pc.idPrestamo,
                a.documento, a.nombres, a.apellidos
                FROM pagos_cuotas pc
                INNER JOIN prestamos p
                ON pc.idPrestamo = p.idPrestamo
                INNER JOIN asociados a
                ON p.idAsociado = a.idAsociado
                WHERE pc.idPrestamo = :idPrestamo
                ORDER BY pc.fechaPago ASC";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':idPrestamo' => $idPrestamo
        ]);

        return $consulta->fetchAll();
    }

    public function buscarPrestamo($idPrestamo)
    {
        $sql = "SELECT p.idPrestamo, p.valorPrestamo,
                p.tasaInteres, p.numeroCuotas,
                a.documento, a.nombres, a.apellidos
                FROM prestamos p
                INNER JOIN asociados a
                ON p.idAsociado = a.idAsociado
                WHERE p.idPrestamo = :idPrestamo";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':idPrestamo' => $idPrestamo
        ]);

        return $consulta->fetch();
    }

    public function editar($datos)
    {
        $sql = "UPDATE pagos_cuotas SET
                valorCuota = :valorCuota,
                fechaPago = :fechaPago
                WHERE idPago = :idPago";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':valorCuota' => $datos['valorCuota'],
            ':fechaPago' => $datos['fechaPago'],
            ':idPago' => $datos['idPago']
        ]);

        if ($consulta->rowCount() > 0) {
            return true;
        }

        $verificar = $this->conexion->prepare(
            "SELECT idPago FROM pagos_cuotas
             WHERE idPago = :idPago"
        );

        $verificar->execute([
            ':idPago' => $datos['idPago']
        ]);

        if ($verificar->fetch()) {
            return true;
        }

        return false;
    }

    public function eliminar($idPago)
    {
        $sql = "DELETE FROM pagos_cuotas
                WHERE idPago = :idPago";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':idPago' => $idPago
        ]);

        return $consulta->rowCount() > 0;
    }
}