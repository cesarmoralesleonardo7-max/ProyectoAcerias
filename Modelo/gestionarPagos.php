<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

function validarId($id)
{
    $id = filter_var($id, FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1]
    ]);

    if ($id === false || $id === null) {
        http_response_code(400);
        throw new Exception("Identificador inválido");
    }

    return $id;
}


try {

    if (!isset($_SESSION["documento"], $_SESSION["rol"])) {
        http_response_code(401);
        throw new Exception("Inicia sesión");
    }

    // El administrador solo puede consultar pagos

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        http_response_code(405);
        throw new Exception(
            "Este módulo permite únicamente consultar pagos"
        );
    }

    if ($_SESSION["rol"] !== "admin") {
        http_response_code(403);
        throw new Exception(
            "No tienes permisos para consultar los pagos"
        );
    }

    require_once __DIR__ . "/Pago.php";

    $pago = new Pago();

    $filtro = $_GET["filtro"] ?? "todos";


    // Consultar todos los pagos

    if ($filtro === "todos") {

        $resultado = [
            "success" => true,
            "rol" => $_SESSION["rol"],
            "pagos" => $pago->obtenerPagos()
        ];


    // Consultar total pagado por asociado

    } else if ($filtro === "total") {

        $documento = $_GET["documento"] ?? "";

        if (!is_string($documento) || trim($documento) === "") {
            http_response_code(400);
            throw new Exception("Ingresa el documento");
        }

        $total = $pago->totalPagado(trim($documento));

        if (!$total) {
            http_response_code(404);
            throw new Exception("Asociado no encontrado");
        }

        $resultado = [
            "success" => true,
            "rol" => $_SESSION["rol"],
            "total" => $total
        ];


    // Consultar pagos de un préstamo

    } else if ($filtro === "prestamo") {

        $idPrestamo = validarId(
            $_GET["idPrestamo"] ?? null
        );

        $resultado = [
            "success" => true,
            "rol" => $_SESSION["rol"],
            "pagos" => $pago->filtrarPrestamo($idPrestamo)
        ];


    // Conservar la búsqueda existente de préstamos

    } else if ($filtro === "buscarPrestamo") {

        $idPrestamo = validarId(
            $_GET["idPrestamo"] ?? null
        );

        $prestamo = $pago->buscarPrestamo($idPrestamo);

        if (!$prestamo) {
            http_response_code(404);
            throw new Exception("Préstamo no encontrado");
        }

        $resultado = [
            "success" => true,
            "rol" => $_SESSION["rol"],
            "prestamo" => $prestamo
        ];

    } else {

        http_response_code(400);
        throw new Exception("Filtro inválido");
    }

    http_response_code(200);

    echo json_encode($resultado);

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