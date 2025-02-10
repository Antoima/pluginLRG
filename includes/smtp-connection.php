<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar PHPMailer
require '../vendor/autoload.php';

function verificar_conexion_smtp($host, $puerto, $usuario, $contraseña) {
    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $usuario;
        $mail->Password = $contraseña;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $puerto;

        // Intentar enviar un correo de prueba
        $mail->setFrom($usuario, 'Prueba');
        $mail->addAddress($usuario);
        $mail->Subject = 'Prueba de conexión SMTP';
        $mail->Body = 'Este es un correo de prueba para verificar la conexión SMTP.';

        $mail->send();
        return json_encode(['status' => 'success', 'message' => 'Conexión SMTP exitosa y correo enviado.']);
    } catch (Exception $e) {
        return json_encode(['status' => 'error', 'message' => "Error en la conexión SMTP: {$mail->ErrorInfo}"]);
    }
}