<?php
// process_backup_migration.php

// ✅ Validar token desde POST (no sesión)
$sourceToken = $_POST['accessToken'] ?? null;

if (empty($sourceToken)) {
    echo json_encode(["status" => "error", "message" => "Token no proporcionado."]);
    exit();
}

// Obtener tokens desde POST
$sourceToken = $_POST['accessToken'] ?? null;
$destinationToken = $_POST['destinationAccessToken'] ?? null;
$sourceEmail = $_POST['sourceEmail'];
$destinationEmail = $_POST['destinationEmail'];

// Validar tokens
if (empty($sourceToken)) {
    echo json_encode(["status" => "error", "message" => "Token de origen no proporcionado."]);
    exit();
}

if ($destinationEmail && empty($destinationToken)) {
    echo json_encode(["status" => "error", "message" => "Token de destino no proporcionado."]);
    exit();
}

// Configurar el tipo de contenido como JSON
header('Content-Type: application/json');

// Obtener datos POST
$sourceToken = $_POST['accessToken'];
$destinationToken = $_POST['destinationAccessToken'] ?? null;
$sourceEmail = $_POST['sourceEmail'];
$destinationEmail = $_POST['destinationEmail'];

// Inicializar sesión para progreso
$_SESSION['progress'] = 0;
session_write_close(); // Liberar el bloqueo de sesión

try {
    // 1. Obtener correos
    $emails = getEmails($sourceToken);
    $totalEmails = count($emails);

    // Imprimir los correos obtenidos
    echo "<pre>" . print_r($emails, true) . "</pre>"; // Mostrar los correos obtenidos

    // 2. Exportar a .mbox
    $mboxContent = "";
    foreach ($emails as $index => $email) {
        // Actualizar progreso (25%)
        $_SESSION['progress'] = 25 + (($index / $totalEmails) * 25);
        session_write_close();

        echo "Procesando correo: " . print_r($email, true) . "<br>"; // Mostrar correo procesado

        $messageId = $email['id'];
        $url = "https://www.googleapis.com/gmail/v1/users/me/messages/$messageId?format=raw";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $sourceToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $emailData = json_decode(curl_exec($ch), true);
        curl_close($ch);

        echo "Respuesta de la API para el correo: " . print_r($emailData, true) . "<br>"; // Mostrar respuesta de la API

        if (isset($emailData['raw'])) {
            $rawEmail = base64_decode(strtr($emailData['raw'], '-_', '+/'));
            $mboxContent .= "From - " . date('r') . "\n" . $rawEmail . "\n\n";
        } else {
            echo "No se encontró 'raw' en la respuesta para el mensaje ID: $messageId<br>";
        }
    }

    // Verificar el contenido antes de escribir
    echo "Contenido del archivo de respaldo:<br><pre>" . htmlspecialchars($mboxContent) . "</pre>";

    // Escribir el archivo de respaldo
    file_put_contents("backup.mbox", $mboxContent);

    // 3. Migrar correos
    if ($destinationEmail && $destinationToken) {
        foreach ($emails as $index => $email) {
            // Actualizar progreso (50% + 50%)
            $_SESSION['progress'] = 50 + (($index / $totalEmails) * 50);
            session_write_close();

            $messageId = $email['id'];
            $url = "https://www.googleapis.com/gmail/v1/users/me/messages/$messageId?format=raw";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $sourceToken"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $emailData = json_decode(curl_exec($ch), true);
            curl_close($ch);

            echo "Respuesta de la API para migración: " . print_r($emailData, true) . "<br>"; // Mostrar respuesta de la API

            if (isset($emailData['raw'])) {
                $rawEmail = base64_decode(strtr($emailData['raw'], '-_', '+/'));
                if (empty($rawEmail)) {
                    echo "Correo vacío para el mensaje: " . print_r($email, true) . "<br>";
                } else {
                    $mboxContent .= "From - " . date('r') . "\n" . $rawEmail . "\n\n";
                }

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

                echo "Respuesta del envío: " . print_r($response, true) . "<br>"; // Mostrar respuesta del envío

                if (isset($response['error'])) {
                    throw new Exception("Error al migrar correo: " . $response['error']['message']);
                }
            } else {
                echo "No se encontró 'raw' para migrar el correo con ID: $messageId<br>";
            }
        }
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

    echo "Respuesta de la API para obtener correos: " . print_r($response, true) . "<br>"; // Mostrar respuesta para la obtención de correos

    return $response['messages'] ?? [];
}

?>