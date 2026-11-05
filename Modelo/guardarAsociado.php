<?php
header('Content-Type: application/json; charset=utf-8');
Require_once __DIR__ . '/../Modelo/Asociado.php';

try{if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        http_response_code(400);
        throw new Exception("Metodo HTTP no permitido");//Saltar catch y mostrar mensaje error
   }
   $json = file_get_contents('php://input');//leer el contendio JSON de la solicitud enviada por fetch
   
   $datos = json_decode($json, true);//decodificar el JSON en un array asociativo PHP

   if (!is_array($datos)|| json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        throw new Exception("Datos inválidos");
    }

    $campos=["documento","nombres","apellidos","fechaNacimiento","genero","direccionResidencia","ciudad","telefono","email"];
    foreach($campos as $campo){
        if(!isset($datos[$campo])){
            http_response_code(400);
            throw new Exception("Falta el campo:". $campo); 
        }
      
    }

    if(!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)=== false){
        http_response_code(400);
        throw new Exception("Email inválido");
    }  

    $asociado = new Asociado($datos);

    $asociado->guardar();

    $resultado = $asociado->guardar();

    if($resultado){
        http_response_code(201);
        echo json_encode(["success"=> true,"mensaje" =>"Asociado guardado exitosamente"]);
    }else{
        http_response_code(500);
        echo json_encode(["success" => false, "mensaje" => "Error al guardar el asociado"]);
    }

}catch(PDOException $e){
    if($e->getCode() == 23000){//codigo de error para datos duplicados en MYSQL
        http_response_code(409);//conflicto
        echo json_encode(["success" => false, "mensaje" => "El documento ya existe"]);
    }else{
        http_response_code(500);
        echo json_encode(["success" => false, "mensaje" => "Error de base de datos: " . $e->getMessage()]);
    }

}catch (Exception $e){//errores dwe codigo por ejemplo metodo HTTP,darta inválida.
    http_response_code(400);
    echo json_encode(["success" => false, "mensaje" => $e->getMessage()]);
   
}



