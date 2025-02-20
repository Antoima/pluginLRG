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

// Función para crear un archivo de respaldo .mbox
function createBackupFile($emails, $token) {
    $backupFileName = 'backup.mbox';
    $file = fopen($backupFileName, 'w');

    foreach ($emails as $email) {
        $emailId = $email['id'];

        // Obtener el contenido del correo
        $rawEmail = getEmailContent($emailId, $token);
        
        if ($rawEmail) {
            // Escribir el correo en el archivo
            fwrite($file, base64_decode(strtr($rawEmail, '-_', '+/')));
        }
    }

    fclose($file);
    return $backupFileName;
}

// Función principal para recuperar correos y generar el archivo de respaldo
function backupEmails($token) {
    // Obtener los correos desde la cuenta fuente
    $emails = getEmails($token);
    echo "Número de correos obtenidos: " . count($emails) . "\n";
    
    // Crear el archivo de respaldo .mbox
    $backupFileName = createBackupFile($emails, $token);
    
    return $backupFileName;
}

// Aquí se ejecuta la recuperación de correos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sourceToken = $_POST['sourceAccessToken'] ?? null;

    if ($sourceToken) {
        $backupFileName = backupEmails($sourceToken);
        // Pasar el archivo generado para su descarga
        echo "Respaldo creado: <a href='$backupFileName' download>Descargar Respaldo</a>";
    } else {
        echo "Falta el token de acceso para la cuenta de origen.";
    }
}
?>
