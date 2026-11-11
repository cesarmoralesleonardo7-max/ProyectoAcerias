<?php

header("content-type: application/json; charset=utf-8"); //Manejar la respuesta en un json

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    //Saltar a catch para mostrar mensaje el error

    
    throw new Exception("Método HTTP no permitido");
    
}

session_start(); //Trae lo datos que ya estan giardados
$_SESSION =[]; 

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );

}
session_destroy(); //Destruye todos los datos de la sesion
echo json_encode([
    "success" => true
]);