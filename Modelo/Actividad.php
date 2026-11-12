<?php

require_once __DIR__ . '/../Config/Conexion.php';

class Actividad
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Conexion();
        $this->conexion=$conexionDb->conectar();

    }
    public function listar actividades(){
        $sql = "SELECT * FROM actividades ORDER BY fechaEvento ASC";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    public function guardar(){
        $sql = "INSERT INTO actividades (nombre,fechaEvento,municipio)
        VALUES (:nombreActividad, :fechaEvento, :municipio)";
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':nombreActividad' => datos['nombreActividad'],
            ':fechaEvento' => datos['fechaEvento'],
            ':municipio' => datos['municipio']
        ]);
 }
    public function eliminar($idActividad){
        $sql = "DELETE FROM actividades WHERE idActividad = :idActividad";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':idActividad' => $idActividad]);
        return $consulta->rowCount();
    }

    public function editar($datos){
        $sql= "UPDATE actividades SET nombre = :nombreActividad, fechaEvento = :fechaEvento, municipio = :municipio,totalRecaudado = :totalRecaudado WHERE idActividad = :idActividad";
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':nombreActividad' => $datos['nombreActividad'],
            ':fechaEvento' => $datos['fechaEvento'],
            ':municipio' => $datos['municipio'],
            ':totalRecaudado' => $datos['totalRecaudado'],
            ':idActividad' => $datos['idActividad']
        ]);

        if($consulta->rowCount() > 0){
            return true;
        }
        $verificar = $this->conexion->prepare("SELECT 1 * FROM actividades WHERE idActividad = :idActividad");
        $verificar->execute([':idActividad' => $datos['idActividad']]); 

        return(bool)$verificar->fetchColumn();
    }

}