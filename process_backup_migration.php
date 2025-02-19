<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['access_token'])) {
    echo json_encode(["status" => "error", "message" => "No autenticado."]);
    exit();
}

// Obtener los datos del formulario
$sourceEmail = $_POST['sourceEmail'] ?? '';
$destinationEmail = $_POST['destinationEmail'] ?? '';
$accessToken = $_POST['accessToken'] ?? '';

if (empty($sourceEmail)) {
    echo json_encode(["status" => "error", "message" => "La cuenta de origen es requerida."]);
    exit();
}

// Lógica para realizar la copia de seguridad y migración
try {
    // 1. Obtener los correos de la cuenta de origen
    $emails = getEmails($accessToken);

    // 2. Exportar los correos a un archivo de respaldo
    exportEmailsToMbox($accessToken, $emails);

    // 3. Migrar los correos a la cuenta de destino (si se proporciona)
    if (!empty($destinationEmail)) {
        migrateEmails($accessToken, $emails, $destinationEmail);
    }

    // Respuesta exitosa
    echo json_encode([
        "status" => "success",
        "message" => "Copia de seguridad y migración completadas correctamente.",
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error: " . $e->getMessage(),
    ]);
}

// Función para obtener los correos
function getEmails($accessToken, $userId = 'me') {
    $url = "https://www.googleapis.com/gmail/v1/users/{$userId}/messages";
    $headers = [
        "Authorization: Bearer {$accessToken}",
        "Accept: application/json",
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $emails = json_decode($response, true);
    return $emails['messages'] ?? [];
}

// Función para exportar los correos a un archivo .mbox
function exportEmailsToMbox($accessToken, $emails, $userId = 'me') {
    $mboxContent = "";

    foreach ($emails as $email) {
        $messageId = $email['id'];
        $url = "https://www.googleapis.com/gmail/v1/users/{$userId}/messages/{$messageId}?format=raw";
        $headers = [
            "Authorization: Bearer {$accessToken}",
            "Accept: application/json",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $emailData = json_decode($response, true);
        $rawEmail = base64_decode(str_replace(['-', '_'], ['+', '/'], $emailData['raw']));

        $mboxContent .= "From - " . date('r') . "\n";
        $mboxContent .= $rawEmail . "\n\n";
    }

    file_put_contents("backup.mbox", $mboxContent);
}

// Función para migrar los correos a la cuenta de destino
function migrateEmails($accessToken, $emails, $destinationEmail, $userId = 'me') {
    foreach ($emails as $email) {
        $messageId = $email['id'];
        $url = "https://www.googleapis.com/gmail/v1/users/{$userId}/messages/{$messageId}?format=raw";
        $headers = [
            "Authorization: Bearer {$accessToken}",
            "Accept: application/json",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $emailData = json_decode($response, true);
        $rawEmail = base64_decode(str_replace(['-', '_'], ['+', '/'], $emailData['raw']));

        $sendUrl = "https://www.googleapis.com/gmail/v1/users/me/messages/send";
        $sendHeaders = [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json",
        ];

        $sendData = [
            'raw' => base64_encode($rawEmail),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sendUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $sendHeaders);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($sendData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $sendResponse = curl_exec($ch);
        curl_close($ch);

        $sendResult = json_decode($sendResponse, true);
        if (isset($sendResult['error'])) {
            throw new Exception("Error al enviar el correo: " . $sendResult['error']['message']);
        }
    }
}