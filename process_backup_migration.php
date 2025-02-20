<?php
// Incluir el archivo de configuración del log
require_once 'log.php'; // Asegúrate de que la ruta es correcta

session_start(); // Asegúrate de que la sesión esté iniciada

// Validar token desde POST (no sesión)
$sourceToken = $_POST['accessToken'] ?? null;
if (empty($sourceToken)) {
    // Log error
    $logger->error("Token no proporcionado.");
    echo json_encode(["status" => "error", "message" => "Token no proporcionado."]);
    exit();
}

// Obtener tokens desde POST
$destinationToken = $_POST['destinationAccessToken'] ?? null;
$sourceEmail = $_POST['sourceEmail'];
$destinationEmail = $_POST['destinationEmail'];

// Validar tokens
if (empty($sourceToken)) {
    // Log error
    $logger->error("Token de origen no proporcionado.");
    echo json_encode(["status" => "error", "message" => "Token de origen no proporcionado."]);
    exit();
}

if ($destinationEmail && empty($destinationToken)) {
    // Log error
    $logger->error("Token de destino no proporcionado.");
    echo json_encode(["status" => "error", "message" => "Token de destino no proporcionado."]);
    exit();
}

// Asegurarte de que la variable de sesión 'processedEmails' exista
if (!isset($_SESSION['processedEmails'])) {
    $_SESSION['processedEmails'] = []; // Inicializa la lista de correos procesados
}

// Configurar el tipo de contenido como JSON
header('Content-Type: application/json');

// Inicializar sesión para progreso
$_SESSION['progress'] = 0;
session_write_close(); // Liberar el bloqueo de sesión

try {
    // 1. Obtener correos
    $emails = getEmails($sourceToken);
    $totalEmails = count($emails);

    // Log: Correos obtenidos
    $logger->info("Correos obtenidos: " . count($emails));

    // 2. Exportar a .mbox
    $mboxContent = "";
    foreach ($emails as $index => $email) {
        // Si el correo ya fue procesado, saltamos al siguiente
        if (in_array($email['id'], $_SESSION['processedEmails'])) {
            $logger->info("Correo ID " . $email['id'] . " ya procesado. Saltando...");
            continue; // No procesamos este correo, pasamos al siguiente
        }

        // Actualizar progreso (25%)
        $_SESSION['progress'] = 25 + (($index / $totalEmails) * 25);
        session_write_close();

        // Log: Procesando correo
        $logger->info("Procesando correo ID: " . $email['id']);

        $messageId = $email['id'];
        $url = "https://www.googleapis.com/gmail/v1/users/me/messages/$messageId?format=raw";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $sourceToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $emailData = json_decode(curl_exec($ch), true);
        curl_close($ch);

        // Log: Respuesta de la API
        $logger->info("Respuesta de la API para el correo: " . print_r($emailData, true));

        if (isset($emailData['raw'])) {
            $rawEmail = base64_decode(strtr($emailData['raw'], '-_', '+/'));
            $mboxContent .= "From - " . date('r') . "\n" . $rawEmail . "\n\n";
        } else {
            $logger->warning("No se encontró 'raw' en la respuesta para el mensaje ID: $messageId");
        }

        // Marcar el correo como procesado
        $_SESSION['processedEmails'][] = $email['id']; // Guardamos el ID del correo procesado
    }

    // Verificar el contenido antes de escribir
    $logger->info("Contenido del archivo de respaldo generado.");

    // Escribir el archivo de respaldo
    file_put_contents("backup.mbox", $mboxContent);

    // 3. Migrar correos
if ($destinationEmail && $destinationToken) {
    foreach ($emails as $index => $email) {
        // Si el correo ya fue procesado, saltamos al siguiente
        if (in_array($email['id'], $_SESSION['processedEmails'])) {
            $logger->info("Correo ID " . $email['id'] . " ya procesado. Saltando...");
            continue; // No procesamos este correo, pasamos al siguiente
        }

        // Actualizar progreso (50%)
        $_SESSION['progress'] = 50 + (($index / $totalEmails) * 50);
        session_write_close();

        $messageId = $email['id'];
        $url = "https://www.googleapis.com/gmail/v1/users/me/messages/$messageId?format=raw";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $sourceToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $emailData = json_decode(curl_exec($ch), true);
        curl_close($ch);

        // Log: Respuesta de la API para migración
        $logger->info("Respuesta de la API para migración del correo ID: " . $email['id']);

        if (isset($emailData['raw'])) {
            // Decodificar el contenido 'raw' del correo
            $rawEmail = base64_decode(strtr($emailData['raw'], '-_', '+/'));

            if (empty($rawEmail)) {
                $logger->warning("Correo vacío para el mensaje: " . print_r($email, true));
            } else {
                // Crear los datos del correo a enviar
                $emailDataToSend = [
                    "raw" => base64_encode($rawEmail), // Base64 encode del correo original
                ];

                // Enviar el correo con el token de destino
                $ch = curl_init("https://www.googleapis.com/gmail/v1/users/me/messages/send");
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $destinationToken", // Token de destino
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailDataToSend));  // Enviar el correo usando raw
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = json_decode(curl_exec($ch), true);
                curl_close($ch);

                // Verificar la respuesta del envío
                if (isset($response['error'])) {
                    $logger->error("Error al migrar correo: " . print_r($response, true));
                    throw new Exception("Error al migrar correo: " . $response['error']['message']);
                } else {
                    $logger->info("Correo ID " . $email['id'] . " migrado exitosamente.");
                }
            }
        } else {
            $logger->warning("No se encontró 'raw' para migrar el correo con ID: " . $email['id']);
        }

        // Marcar el correo como procesado
        $_SESSION['processedEmails'][] = $email['id'];
    }
}

    $logger->info("Proceso completado exitosamente.");
    echo json_encode(["status" => "success", "message" => "Proceso completado."]);

} catch (Exception $e) {
    // Log: Excepción
    $logger->error("Excepción capturada: " . $e->getMessage());
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

    // Log: Respuesta de la API para obtener correos
    global $logger;
    $logger->info("Respuesta de la API para obtener correos: " . print_r($response, true));

    return $response['messages'] ?? [];
}
?>
