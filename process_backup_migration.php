<?php
require_once 'log.php';
session_start();

// Función para hacer la solicitud a la API de Gmail y recuperar correos de una cuenta
function getEmails($token) {
    $url = "https://www.googleapis.com/gmail/v1/users/me/messages";
    $headers = [
        "Authorization: Bearer $token",
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    if ($response === false) {
        die('Error en cURL: ' . curl_error($ch));
    }
    
    $emails = json_decode($response, true);
    curl_close($ch);

    return $emails['messages'] ?? [];
}

// Función para recuperar el contenido de un correo
function getEmailContent($emailId, $token) {
    $url = "https://www.googleapis.com/gmail/v1/users/me/messages/$emailId?format=raw";
    $headers = [
        "Authorization: Bearer $token",
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    if ($response === false) {
        die('Error en cURL: ' . curl_error($ch));
    }
    
    $emailData = json_decode($response, true);
    curl_close($ch);

    return $emailData['raw'] ?? null;
}

// Función para enviar un correo a la cuenta de destino
function sendEmail($rawEmail, $destinationToken) {
    $url = "https://www.googleapis.com/gmail/v1/users/me/messages/send";
    $headers = [
        "Authorization: Bearer $destinationToken",
        "Content-Type: application/json",
    ];

    $emailData = [
        'raw' => base64_encode($rawEmail),
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    if ($response === false) {
        die('Error en cURL: ' . curl_error($ch));
    }

    $responseData = json_decode($response, true);
    curl_close($ch);

    // Si tiene ID de mensaje, el correo fue enviado
    return isset($responseData['id']);
}

// Función principal para realizar la migración de correos
function migrateEmails($sourceToken, $destinationToken) {
    // Obtener los correos desde la cuenta fuente
    $emails = getEmails($sourceToken);
    
    // Procesar cada correo
    foreach ($emails as $email) {
        $emailId = $email['id'];

        // Obtener el contenido del correo
        $rawEmail = getEmailContent($emailId, $sourceToken);
        
        if ($rawEmail) {
            // Enviar el correo al destino
            $sent = sendEmail(base64_decode(strtr($rawEmail, '-_', '+/')), $destinationToken);
            
            if ($sent) {
                echo "Correo ID $emailId enviado exitosamente.\n";
            } else {
                echo "Error al enviar el correo ID $emailId.\n";
            }
        } else {
            echo "Error al obtener el contenido del correo ID $emailId.\n";
        }
    }

    echo "Proceso de migración completado.\n";
}

?>
