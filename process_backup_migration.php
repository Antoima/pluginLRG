<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['access_token'])) {
    echo json_encode(["status" => "error", "message" => "No autenticado."]);
    exit();
}

// Obtener tokens
$sourceToken = $_POST['accessToken'];
$destinationToken = $_POST['destinationAccessToken'] ?? null;
$sourceEmail = $_POST['sourceEmail'];
$destinationEmail = $_POST['destinationEmail'];

// Validar
if (empty($sourceEmail)) {
    echo json_encode(["status" => "error", "message" => "Cuenta de origen requerida."]);
    exit();
}

try {
    // 1. Obtener correos de origen
    $emails = getEmails($sourceToken);

    // 2. Exportar a .mbox
    exportEmailsToMbox($sourceToken, $emails);

    // 3. Migrar a destino (si hay token)
    if ($destinationEmail && $destinationToken) {
        migrateEmails($sourceToken, $destinationToken, $emails);
    }

    echo json_encode(["status" => "success", "message" => "Proceso completado."]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

// Funciones
function getEmails($token) {
    $url = "https://www.googleapis.com/gmail/v1/users/me/messages";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $response['messages'] ?? [];
}

function exportEmailsToMbox($token, $emails) {
    $mboxContent = "";
    foreach ($emails as $email) {
        $messageId = $email['id'];
        $url = "https://www.googleapis.com/gmail/v1/users/me/messages/$messageId?format=raw";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $emailData = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $rawEmail = base64_decode(strtr($emailData['raw'], '-_', '+/'));
        $mboxContent .= "From - " . date('r') . "\n" . $rawEmail . "\n\n";
    }
    file_put_contents("backup.mbox", $mboxContent);
}

function migrateEmails($sourceToken, $destinationToken, $emails) {
    foreach ($emails as $email) {
        $messageId = $email['id'];
        $url = "https://www.googleapis.com/gmail/v1/users/me/messages/$messageId?format=raw";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $sourceToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $emailData = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $rawEmail = base64_decode(strtr($emailData['raw'], '-_', '+/'));

        // Enviar con token de destino
        $ch = curl_init("https://www.googleapis.com/gmail/v1/users/me/messages/send");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $destinationToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["raw" => base64_encode($rawEmail)]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($response['error'])) {
            throw new Exception("Error al migrar correo: " . $response['error']['message']);
        }
    }
}