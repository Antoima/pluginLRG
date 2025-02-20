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
        die('Error en cURL: ' . curl_error($ch));  // Log detallado en caso de error de cURL
    }

    $emails = json_decode($response, true);
    if (isset($emails['error'])) {
        die('Error en API de Gmail: ' . $emails['error']['message']);  // Log detallado si la API de Gmail devuelve un error
    }

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
        die('Error en cURL: ' . curl_error($ch));  // Log detallado en caso de error de cURL
    }

    $emailData = json_decode($response, true);
    if (isset($emailData['error'])) {
        die('Error en la API de Gmail al obtener el contenido del correo: ' . $emailData['error']['message']);  // Log si la API de Gmail devuelve un error
    }

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

    // Log para depuración antes de enviar el correo
    echo "Correo a enviar (base64): " . $emailData['raw'] . "\n";  

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    if ($response === false) {
        die('Error en cURL al enviar el correo: ' . curl_error($ch));  // Log si hay error en cURL
    }

    $responseData = json_decode($response, true);
    if (isset($responseData['error'])) {
        die('Error en la API de Gmail al enviar el correo: ' . $responseData['error']['message']);  // Log si la API de Gmail devuelve un error
    }

    curl_close($ch);

    // Si tiene ID de mensaje, el correo fue enviado
    return isset($responseData['id']);
}


// Función principal para realizar la migración de correos
function migrateEmails($sourceToken, $destinationToken) {
    // Obtener los correos desde la cuenta fuente
    echo "Obteniendo correos desde la cuenta fuente...\n";
    $emails = getEmails($sourceToken);

    echo "Número de correos obtenidos: " . count($emails) . "\n";
    
    // Procesar cada correo
    foreach ($emails as $email) {
        $emailId = $email['id'];

        // Obtener el contenido del correo
        echo "Obteniendo contenido del correo ID: $emailId...\n";
        $rawEmail = getEmailContent($emailId, $sourceToken);
        
        if ($rawEmail) {
            echo "Enviando correo ID: $emailId...\n";
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


// Aquí se ejecuta la migración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sourceToken = $_POST['sourceAccessToken'] ?? null;
    $destinationToken = $_POST['destinationAccessToken'] ?? null;

    if ($sourceToken && $destinationToken) {
        migrateEmails($sourceToken, $destinationToken);
    } else {
        echo "Faltan tokens de acceso para la migración.";
    }
}
?>
