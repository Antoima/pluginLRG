<?php
require_once 'smtp-connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'];
    $puerto = $_POST['puerto'];
    $usuario = $_POST['usuario'];
    $contraseña = $_POST['contraseña'];

    $resultado = verificar_conexion_smtp($host, $puerto, $usuario, $contraseña);
    echo $resultado;
}