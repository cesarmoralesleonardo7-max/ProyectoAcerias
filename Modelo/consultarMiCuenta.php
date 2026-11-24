<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

try {

    if (!isset($_SESSION["documento"], $_SESSION["rol"])) {
        http_response_code(401);
        throw new Exception("Debes iniciar sesión");
    }

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);
        throw new Exception("Método no permitido");
    }

    if ($_SESSION["rol"] !== "asociados") {
        http_response_code(403);
        throw new Exception("Acceso exclusivo para asociados");
    }

    require_once __DIR__ . "/../Config/conexion.php";

    $conexionDb = new Conexion();
    $conexion = $conexionDb->conectar();

    $documento = $_SESSION["documento"];

    // Consultar los datos del asociado

    $sql = "SELECT idAsociado, documento, nombres, apellidos,
                   fechaNacimiento, genero, direccionResidencia,
                   ciudad, telefono, email
            FROM asociados
            WHERE documento = :documento";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":documento" => $documento
    ]);

    $asociado = $consulta->fetch();

    if (!$asociado) {
        http_response_code(404);
        throw new Exception("Asociado no encontrado");
    }

    $idAsociado = $asociado["idAsociado"];

    // Consultar sus préstamos

    $sql = "SELECT idPrestamo, valorPrestamo, tasaInteres,
                   numeroCuotas, fechaPrestamo
            FROM prestamos
            WHERE idAsociado = :idAsociado
            ORDER BY fechaPrestamo DESC";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":idAsociado" => $idAsociado
    ]);

    $prestamos = $consulta->fetchAll();

    // Consultar los pagos de sus préstamos

    $sql = "SELECT pc.idPago, pc.idPrestamo,
                   pc.valorCuota, pc.fechaPago
            FROM pagos_cuotas pc
            INNER JOIN prestamos p
            ON pc.idPrestamo = p.idPrestamo
            WHERE p.idAsociado = :idAsociado
            ORDER BY pc.fechaPago DESC";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":idAsociado" => $idAsociado
    ]);

    $pagos = $consulta->fetchAll();

    // Consultar las actividades del club
    // e identificar aquellas en las que participa

    $sql = "SELECT ac.idActividad, ac.nombreActividad,
                   ac.fechaEvento, ac.municipio,
                   CASE
                       WHEN EXISTS (
                           SELECT 1
                           FROM actividades_asociado aa
                           WHERE aa.idActividad = ac.idActividad
                           AND aa.idAsociado = :idAsociado
                       )
                       THEN 1
                       ELSE 0
                   END AS participa
            FROM actividades ac
            ORDER BY ac.fechaEvento DESC";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":idAsociado" => $idAsociado
    ]);

    $actividades = $consulta->fetchAll();

    echo json_encode([
        "success" => true,
        "asociado" => $asociado,
        "prestamos" => $prestamos,
        "pagos" => $pagos,
        "actividades" => $actividades
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "mensaje" => "Error en la base de datos"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ]);
}