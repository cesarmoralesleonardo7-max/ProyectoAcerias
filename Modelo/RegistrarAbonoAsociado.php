<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

try {

    if (!isset($_SESSION["documento"], $_SESSION["rol"])) {

        http_response_code(401);
        throw new Exception("Debes iniciar sesión");
    }

    if ($_SESSION["rol"] !== "asociados") {

        http_response_code(403);
        throw new Exception("Acceso exclusivo para asociados");
    }

    $metodo = $_SERVER["REQUEST_METHOD"];

    if ($metodo === "GET") {

        if (!isset($_SESSION["tokenAbono"])) {

            $_SESSION["tokenAbono"] = bin2hex(random_bytes(32));
        }

        echo json_encode([
            "success" => true,
            "token" => $_SESSION["tokenAbono"]
        ]);

        exit;
    }

    if ($metodo !== "POST") {

        http_response_code(405);
        throw new Exception("Método HTTP no permitido");
    }

    $datos = json_decode(file_get_contents("php://input"), true);

    if (!is_array($datos) || json_last_error() !== JSON_ERROR_NONE) {

        http_response_code(400);
        throw new Exception("Datos inválidos");
    }

    $token = $datos["token"] ?? null;

    if (!is_string($token) ||
        !isset($_SESSION["tokenAbono"]) ||
        !hash_equals($_SESSION["tokenAbono"], $token)) {

        http_response_code(403);
        throw new Exception("Solicitud no autorizada");
    }

    // Validar identificador del préstamo

    $idPrestamo = filter_var(
        $datos["idPrestamo"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if ($idPrestamo === false) {

        http_response_code(400);
        throw new Exception("Selecciona un préstamo válido");
    }

    // Validar valor del abono

    $valor = $datos["valorCuota"] ?? null;

    if ((!is_string($valor) && !is_numeric($valor)) ||
        !preg_match('/^\d{1,10}(\.\d{1,2})?$/D', (string)$valor) ||
        (float)$valor <= 0) {

        http_response_code(400);

        throw new Exception(
            "El pago debe ser mayor a cero y tener máximo dos decimales"
        );
    }

    require_once __DIR__ . "/../Config/conexion.php";

    $conexionDb = new Conexion();
    $conexion = $conexionDb->conectar();

    // Verificar que el préstamo pertenezca al asociado que inició sesión

    $sql = "SELECT p.fechaPrestamo
            FROM prestamos p
            INNER JOIN asociados a
            ON p.idAsociado = a.idAsociado
            WHERE p.idPrestamo = :idPrestamo
              AND a.documento = :documento";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":idPrestamo" => $idPrestamo,
        ":documento" => $_SESSION["documento"]
    ]);

    $prestamo = $consulta->fetch();

    if (!$prestamo) {

        http_response_code(404);
        throw new Exception("El préstamo no existe o no te pertenece");
    }

    // Establecer la fecha actual desde el servidor

    $zona = new DateTimeZone("America/Bogota");

    $fechaHoy = (new DateTimeImmutable("now", $zona))
        ->format("Y-m-d");

    if ($fechaHoy < $prestamo["fechaPrestamo"]) {

        http_response_code(400);

        throw new Exception(
            "No puedes registrar un pago antes de la fecha del préstamo"
        );
    }

   

    $sql = "INSERT INTO pagos_cuotas
            (valorCuota, fechaPago, idPrestamo)
            VALUES
            (:valorCuota, :fechaPago, :idPrestamo)";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":valorCuota" => $valor,
        ":fechaPago" => $fechaHoy,
        ":idPrestamo" => $idPrestamo
    ]);

    http_response_code(201);

    echo json_encode([
        "success" => true,
        "mensaje" => "Pago registrado correctamente"
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