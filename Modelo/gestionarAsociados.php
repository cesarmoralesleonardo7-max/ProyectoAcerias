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

function validarAsociado($datos)
{
    $campos = [
        "documento",
        "nombres",
        "apellidos",
        "fechaNacimiento",
        "genero",
        "direccionResidencia",
        "ciudad",
        "telefono",
        "email"
    ];

    foreach ($campos as $campo) {

        if (!isset($datos[$campo]) || $datos[$campo] === "") {
            http_response_code(400);
            throw new Exception("Falta el campo: " . $campo);
        }
    }

    if (!filter_var($datos["email"], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        throw new Exception("Email inválido");
    }

    return $datos;
}

try {

    if (!isset($_SESSION["documento"], $_SESSION["rol"])) {
        http_response_code(401);
        throw new Exception("Inicia sesión para consultar los asociados");
    }

    $metodo = $_SERVER["REQUEST_METHOD"];

    if (!in_array($metodo, ["GET", "PUT", "DELETE"], true)) {
        http_response_code(405);
        throw new Exception("Método HTTP no permitido");
    }

    if ($_SESSION["rol"] !== "admin") {
        http_response_code(403);
        throw new Exception("No tienes permisos para realizar esta acción");
    }

    require_once __DIR__ . "/Asociado.php";

    $asociado = new Asociado();

    switch ($metodo) {

        case "GET":

            $filtro = $_GET["filtro"] ?? "todos";
            $valor = $_GET["valor"] ?? "";

            if ($filtro === "todos"){   
            $asociados = $asociado->obtenerAsociados();
            } else if ($filtro === "genero" && $valor !== "") {
                $asociados = $asociado->filtrarGenero($valor);
            } else if ($filtro === "ciudad" && $valor !== "") {
                $asociados = $asociado->filtrarCiudad($valor);}
            } else {
                http_response_code(400);
                throw new Exception("Filtro inválido");

            }

            $resultado = [
                "success" => true,
                "rol" => $_SESSION["rol"],
                "asociados" => $asociados
            ];

            http_response_code(200);

            break;

        case "PUT":

            $datos = validarAsociado(leerDatos());

            if (!$asociado->editar($datos)) {
                http_response_code(404);
                throw new Exception("No se encontró el asociado");
            }

            $resultado = [
                "success" => true,
                "mensaje" => "Asociado actualizado correctamente"
            ];

            http_response_code(200);

            break;

        case "DELETE":

            $datos = leerDatos();

            $documento = $datos["documento"] ?? "";

            if ($documento === "") {
                http_response_code(400);
                throw new Exception("Documento inválido");
            }

            if (!$asociado->eliminar($documento)) {
                http_response_code(404);
                throw new Exception("No se encontró el asociado");
            }

            $resultado = [
                "success" => true,
                "mensaje" => "Asociado eliminado correctamente"
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