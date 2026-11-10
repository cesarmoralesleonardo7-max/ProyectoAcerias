
<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../Modelo/Asociado.php';

try {

    // Verificar el método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception("Método HTTP no permitido");
    }

    // Recibir los datos enviados desde JavaScript
    $json = file_get_contents('php://input');

    // Convertir el JSON en un array de PHP
    $datos = json_decode($json, true);

    // Verificar que el JSON sea válido
    if (!is_array($datos) || json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        throw new Exception("Datos inválidos o JSON vacío");
    }

    // Campos obligatorios
    $campos = [
        "documento",
        "nombres",
        "apellidos",
        "fechaNacimiento",
        "genero",
        "direccionResidencia",
        "ciudad",
        "telefono",
        "email",
        "password"
    ];

    // Verificar que lleguen todos los campos
    foreach ($campos as $campo) {

        if (!isset($datos[$campo]) || trim((string)$datos[$campo]) === '') {
            http_response_code(400);
            throw new Exception("Falta el campo: " . $campo);
        }
    }

    // Validar el correo electrónico
    if (filter_var($datos['email'], FILTER_VALIDATE_EMAIL) === false) {
        http_response_code(400);
        throw new Exception("Email inválido");
    }

    // Crear el objeto asociado
    $asociado = new Asociado($datos);

    // Guardar una sola vez en la base de datos
    $resultado = $asociado->guardar();

    if ($resultado) {

        http_response_code(201);

        echo json_encode([
            "success" => true,
            "mensaje" => "Asociado guardado exitosamente"
        ]);

    } else {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "mensaje" => "Error al guardar el asociado"
        ]);
    }

} catch (PDOException $e) {

    if ($e->getCode() == 23000) {

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "mensaje" => "El documento ya existe"
        ]);

    } else {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "mensaje" => "Error de base de datos: " . $e->getMessage()
        ]);
    }

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ]);
}
