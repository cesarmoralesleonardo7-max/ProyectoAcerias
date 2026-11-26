<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

function leerDatos()
{
    $datos = json_decode(file_get_contents("php://input"), true);

    if (!is_array($datos) || json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        throw new Exception("Datos inválidos");
    }

    return $datos;
}


function validarPrestamo($datos)
{
    $campos = [
        "valorPrestamo",
        "tasaInteres",
        "numeroCuotas",
        "fechaPrestamo"
    ];

    foreach ($campos as $campo) {

        if (!isset($datos[$campo]) || $datos[$campo] === "") {
            http_response_code(400);
            throw new Exception("Falta el campo: " . $campo);
        }
    }

    // Validar valor del préstamo

    if (!is_numeric($datos["valorPrestamo"]) ||
        $datos["valorPrestamo"] <= 0 ||
        $datos["valorPrestamo"] > 80000000) {

        http_response_code(400);
        throw new Exception("Valor del préstamo inválido");
    }

    // Validar tasa de interés

    if (!is_numeric($datos["tasaInteres"]) ||
        $datos["tasaInteres"] < 0) {

        http_response_code(400);
        throw new Exception("Tasa de interés inválida");
    }

    // Validar número de cuotas

    validarId($datos["numeroCuotas"]);

    // Validar formato de la fecha

    $fecha = $datos["fechaPrestamo"];

    if (!is_string($fecha)) {
        http_response_code(400);
        throw new Exception("Ingrese una fecha válida");
    }

    $fechaPrestamo = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

    if (!$fechaPrestamo || $fechaPrestamo->format('Y-m-d') !== $fecha) {
        http_response_code(400);
        throw new Exception("La fecha del préstamo no es válida");
    }

    return $datos;
}


function validarId($id)
{
    $id = filter_var($id, FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1]
    ]);

    if ($id === false) {
        http_response_code(400);
        throw new Exception("Identificador o cantidad inválida");
    }

    return $id;
}


try {

    if (!isset($_SESSION["documento"], $_SESSION["rol"])) {
        http_response_code(401);
        throw new Exception("Inicia sesión para consultar los préstamos");
    }

    $metodo = $_SERVER["REQUEST_METHOD"];

    if (!in_array($metodo, ["GET", "POST", "PUT", "DELETE"], true)) {
        http_response_code(405);
        throw new Exception("Método HTTP no permitido");
    }

    if ($_SESSION["rol"] !== "admin") {
        http_response_code(403);
        throw new Exception("No tienes permisos para realizar esta acción");
    }

    require_once __DIR__ . "/Prestamo.php";

    $prestamo = new Prestamo();

    switch ($metodo) {

        case "GET":

            $filtro = $_GET["filtro"] ?? "todos";

            if ($filtro === "todos") {

                $resultado = [
                    "success" => true,
                    "rol" => $_SESSION["rol"],
                    "prestamos" => $prestamo->obtenerPrestamos()
                ];

            } else if ($filtro === "cantidad") {

                $cantidad = $prestamo->cantidadPrestamos();

                $resultado = [
                    "success" => true,
                    "rol" => $_SESSION["rol"],
                    "cantidad" => $cantidad["cantidad"]
                ];

            } else if ($filtro === "fechas") {

                $fechaInicio = $_GET["fechaInicio"] ?? "";
                $fechaFin = $_GET["fechaFin"] ?? "";

                if ($fechaInicio === "" || $fechaFin === "") {
                    http_response_code(400);
                    throw new Exception("Selecciona las dos fechas");
                }

                if ($fechaInicio > $fechaFin) {
                    http_response_code(400);
                    throw new Exception("El rango de fechas es inválido");
                }

                $resultado = [
                    "success" => true,
                    "rol" => $_SESSION["rol"],
                    "prestamos" => $prestamo->filtrarFechas(
                        $fechaInicio,
                        $fechaFin
                    )
                ];

            } else if ($filtro === "documento") {

                $documento = $_GET["documento"] ?? "";

                if ($documento === "") {
                    http_response_code(400);
                    throw new Exception("Ingresa el documento");
                }

                $resultado = [
                    "success" => true,
                    "rol" => $_SESSION["rol"],
                    "prestamos" => $prestamo->filtrarDocumento($documento)
                ];

            } else if ($filtro === "buscarAsociado") {

                $documento = $_GET["documento"] ?? "";

                if ($documento === "") {
                    http_response_code(400);
                    throw new Exception("Ingresa el documento");
                }

                $asociado = $prestamo->buscarAsociado($documento);

                if (!$asociado) {
                    http_response_code(404);
                    throw new Exception("Asociado no encontrado");
                }

                $resultado = [
                    "success" => true,
                    "rol" => $_SESSION["rol"],
                    "asociado" => $asociado
                ];

            } else {

                http_response_code(400);
                throw new Exception("Filtro inválido");
            }

            http_response_code(200);

            break;


        case "POST":

            $datos = validarPrestamo(leerDatos());

            $datos["idAsociado"] = validarId(
                $datos["idAsociado"] ?? null
            );

            // Validar que los préstamos nuevos no tengan fechas anteriores

            $fechaPrestamo = new DateTimeImmutable($datos["fechaPrestamo"]);

            if ($fechaPrestamo < new DateTimeImmutable('today')) {
                http_response_code(400);
                throw new Exception("No se permiten fechas anteriores a hoy");
            }

            if (!$prestamo->guardar($datos)) {
                http_response_code(500);
                throw new Exception("No se pudo registrar el préstamo");
            }

            $resultado = [
                "success" => true,
                "mensaje" => "Préstamo registrado correctamente"
            ];

            http_response_code(201);

            break;


        case "PUT":

            $datos = validarPrestamo(leerDatos());

            $datos["idPrestamo"] = validarId(
                $datos["idPrestamo"] ?? null
            );

            if (!$prestamo->editar($datos)) {
                http_response_code(404);
                throw new Exception("No se encontró el préstamo");
            }

            $resultado = [
                "success" => true,
                "mensaje" => "Préstamo actualizado correctamente"
            ];

            http_response_code(200);

            break;


        case "DELETE":

            $datos = leerDatos();

            $idPrestamo = validarId(
                $datos["idPrestamo"] ?? null
            );

            if (!$prestamo->eliminar($idPrestamo)) {
                http_response_code(404);
                throw new Exception("No se encontró el préstamo");
            }

            $resultado = [
                "success" => true,
                "mensaje" => "Préstamo eliminado correctamente"
            ];

            http_response_code(200);

            break;
    }

    echo json_encode($resultado);

} catch (PDOException $e) {

    if ($e->getCode() == 23000) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "mensaje" => "La operación genera un conflicto con otros registros"
        ]);

    } else {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "mensaje" => "Error en la base de datos"
        ]);
    }

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ]);
}