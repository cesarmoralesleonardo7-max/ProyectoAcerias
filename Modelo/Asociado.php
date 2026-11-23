<?php

require_once __DIR__ . '/../Config/conexion.php';

class Asociado
{
    private $documento;
    private $nombres;
    private $apellidos;
    private $fechaNacimiento;
    private $genero;
    private $direccionResidencia;
    private $ciudad;
    private $telefono;
    private $email;
    private $password;

    private $conexion;

    public function __construct($datos = null)
    {
        if ($datos !== null) {
            $this->documento = $datos['documento'];
            $this->nombres = $datos['nombres'];
            $this->apellidos = $datos['apellidos'];
            $this->fechaNacimiento = $datos['fechaNacimiento'];
            $this->genero = $datos['genero'];
            $this->direccionResidencia = $datos['direccionResidencia'];
            $this->ciudad = $datos['ciudad'];
            $this->telefono = $datos['telefono'];
            $this->email = $datos['email'];
            $this->password = password_hash($datos['password'], PASSWORD_DEFAULT);
        }

        $conexionDb = new Conexion();
        $this->conexion = $conexionDb->conectar();
    }

    public function guardar()
    {
        $sql = "INSERT INTO asociados (documento, nombres, apellidos,
                fechaNacimiento, genero, direccionResidencia, ciudad,
                telefono, email, password)
                VALUES (:documento, :nombres, :apellidos,
                :fechaNacimiento, :genero, :direccionResidencia,
                :ciudad, :telefono, :email, :password)";

        $consulta = $this->conexion->prepare($sql);

        return $consulta->execute([
            ':documento' => $this->documento,
            ':nombres' => $this->nombres,
            ':apellidos' => $this->apellidos,
            ':fechaNacimiento' => $this->fechaNacimiento,
            ':genero' => $this->genero,
            ':direccionResidencia' => $this->direccionResidencia,
            ':ciudad' => $this->ciudad,
            ':telefono' => $this->telefono,
            ':email' => $this->email,
            ':password' => $this->password
        ]);
    }

    public function obtenerAsociados()
    {
        $sql = "SELECT documento, nombres, apellidos,
                fechaNacimiento, genero, direccionResidencia,
                ciudad, telefono, email, rol
                FROM asociados ORDER BY nombres ASC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    public function filtrarGenero($genero)
    {
        $sql = "SELECT documento, nombres, apellidos,
                fechaNacimiento, genero, direccionResidencia,
                ciudad, telefono, email, rol
                FROM asociados WHERE genero = :genero";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':genero' => $genero
        ]);

        return $consulta->fetchAll();
    }

    public function filtrarCiudad($ciudad)
    {
        $sql = "SELECT documento, nombres, apellidos,
                fechaNacimiento, genero, direccionResidencia,
                ciudad, telefono, email, rol
                FROM asociados WHERE ciudad = :ciudad";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':ciudad' => $ciudad
        ]);

        return $consulta->fetchAll();
    }

    public function editar($datos)
    {
        $sql = "UPDATE asociados SET
                nombres = :nombres,
                apellidos = :apellidos,
                fechaNacimiento = :fechaNacimiento,
                genero = :genero,
                direccionResidencia = :direccionResidencia,
                ciudad = :ciudad,
                telefono = :telefono,
                email = :email
                WHERE documento = :documento";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':nombres' => $datos['nombres'],
            ':apellidos' => $datos['apellidos'],
            ':fechaNacimiento' => $datos['fechaNacimiento'],
            ':genero' => $datos['genero'],
            ':direccionResidencia' => $datos['direccionResidencia'],
            ':ciudad' => $datos['ciudad'],
            ':telefono' => $datos['telefono'],
            ':email' => $datos['email'],
            ':documento' => $datos['documento']
        ]);

        if ($consulta->rowCount() > 0) {
            return true;
        }

        $verificar = $this->conexion->prepare(
            "SELECT documento FROM asociados
            WHERE documento = :documento"
        );

        $verificar->execute([
            ':documento' => $datos['documento']
        ]);

        if ($verificar->fetch()) {
            return true;
        }

        return false;
    }

    public function eliminar($documento)
    {
        $sql = "DELETE FROM asociados
                WHERE documento = :documento";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':documento' => $documento
        ]);

        return $consulta->rowCount() > 0;
    }
}