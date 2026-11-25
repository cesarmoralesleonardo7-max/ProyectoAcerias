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


function validarDocumento($documento)
{
    if (!is_string($documento) && !is_int($documento)) {
        http_response_code(400);
        throw new Exception("Ingrese un documento válido");
    }

    $documento = trim((string)$documento);

    if ($documento === "" || !ctype_digit($documento)) {
        http_response_code(400);
        throw new Exception("Ingrese un documento válido");
    }

    return $documento;
}

function validarId($id)
{
    $idActividad = filter_var($id, FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1]
    ]);

    if ($idActividad === false || $idActividad === null) {
        http_response_code(400);
        throw new Exception("Código de actividad inválido");
    }

    return $idActividad;
}


try {

    if (!isset($_SESSION["documento"], $_SESSION["rol"])) {
        http_response_code(401);
        throw new Exception("Inicia sesión");
    }

    $metodo = $_SERVER["REQUEST_METHOD"];
    $rol = $_SESSION["rol"];

    if (!in_array($metodo, ["GET", "POST"], true)) {
        http_response_code(405);
        throw new Exception("Método HTTP no permitido");
    }

    require_once __DIR__ . "/Participacion.php";

    $participacion = new Participacion();

    switch ($metodo) {

        case "GET":

            $filtro = $_GET["filtro"] ?? "actividades";

            if ($filtro === "token") {

                // Preparar inscripción del asociado

                if ($rol !== "asociados") {
                    http_response_code(403);
                    throw new Exception(
                        "Acceso exclusivo para asociados"
                    );
                }

                if (!isset($_SESSION["tokenParticipacion"])) {
                    $_SESSION["tokenParticipacion"] =
                        bin2hex(random_bytes(32));
                }

                $resultado = [
                    "success" => true,
                    "token" => $_SESSION["tokenParticipacion"]
                ];

            } else {

                // Consultas exclusivas del administrador

                if ($rol !== "admin") {
                    http_response_code(403);
                    throw new Exception(
                        "Consulta exclusiva para el administrador"
                    );
                }

                if ($filtro === "actividades") {

                    $resultado = [
                        "success" => true,
                        "rol" => $rol,
                        "actividades" => $participacion->obtenerActividades()
                    ];

                } else if ($filtro === "cantidad") {

                    $documento = validarDocumento(
                        $_GET["documento"] ?? null
                    );

                    $cantidad = $participacion->cantidadPorDocumento(
                        $documento
                    );

                    if (!$cantidad) {
                        http_response_code(404);
                        throw new Exception("Asociado no encontrado");
                    }

                    $resultado = [
                        "success" => true,
                        "rol" => $rol,
                        "asociado" => $cantidad
                    ];

                } else if ($filtro === "participantes") {

                    $nombre = $_GET["nombre"] ?? "";

                    if (!is_string($nombre) || trim($nombre) === "") {
                        http_response_code(400);
                        throw new Exception(
                            "Ingrese el nombre de la actividad"
                        );
                    }

                    $resultado = [
                        "success" => true,
                        "rol" => $rol,
                        "participantes" =>
                            $participacion->participantesPorActividad(
                                trim($nombre)
                            )
                    ];

                } else {
                    http_response_code(400);
                    throw new Exception("Filtro inválido");
                }
            }

            http_response_code(200);
            break;


        case "POST":

            // Solo los asociados pueden inscribirse

            if ($rol !== "asociados") {
                http_response_code(403);
                throw new Exception(
                    "Solo el asociado puede inscribirse"
                );
            }

            $datos = leerDatos();

            $token = $datos["token"] ?? null;

            if (!is_string($token) ||
                !isset($_SESSION["tokenParticipacion"]) ||
                !hash_equals($_SESSION["tokenParticipacion"], $token)) {

                http_response_code(403);
                throw new Exception("Solicitud no autorizada");
            }

            $idActividad = validarId(
                $datos["idActividad"] ?? null
            );

            // El documento se obtiene de la sesión

            $documento = validarDocumento(
                $_SESSION["documento"]
            );

            if (!$participacion->registrar($documento, $idActividad)) {

                http_response_code(500);
                throw new Exception(
                    "No se pudo registrar la participación"
                );
            }

            $resultado = [
                "success" => true,
                "mensaje" =>
                    "Te inscribiste correctamente en la actividad"
            ];

            http_response_code(201);
            break;
    }

    echo json_encode($resultado);

} catch (PDOException $e) {

    if ($e->getCode() == 23000) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "mensaje" => "Ya estás inscrito en esta actividad"
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