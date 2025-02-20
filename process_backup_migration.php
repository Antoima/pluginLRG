<?php
require_once 'log.php';
session_start();

// Función para obtener correos procesados desde el archivo
function getProcessedEmails() {
    $filePath = 'processed_emails.json';
    
    // Si el archivo no existe, se crea vacío
    if (!file_exists($filePath)) {
        file_put_contents($filePath, json_encode([]));
    }

    // Leer y decodificar el contenido del archivo JSON
    $processed = file_get_contents($filePath);
    return json_decode($processed, true) ?? [];  
}

// Función para guardar un correo procesado en el archivo
function saveProcessedEmail($emailId, $status = false) {
    $processedEmails = getProcessedEmails();
    
    // Si el correo no está en la lista, lo agregamos
    if (!isset($processedEmails[$emailId])) {
        $processedEmails[$emailId] = ['status' => $status];
        file_put_contents('processed_emails.json', json_encode($processedEmails));
    }
    // Si ya está procesado y se quiere cambiar el estado
    if ($status !== false) {
        $processedEmails[$emailId]['status'] = $status;
        file_put_contents('processed_emails.json', json_encode($processedEmails));
    }
}

// Función para hacer la solicitud cURL y obtener datos de Gmail
function makeApiRequest($url, $token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        global $logger;
        $logger->error("Error en cURL: " . curl_error($ch));
        throw new Exception("Error en cURL: " . curl_error($ch));
    }
    curl_close($ch);
    
    return json_decode($response, true);
}

// Función para obtener los correos de Gmail
function getEmails($token) {
    $url = "https://www.googleapis.com/gmail/v1/users/me/messages";
    return makeApiRequest($url, $token)['messages'] ?? [];
}

// Función para procesar los correos
function processEmails($emails, $sourceToken, $destinationToken) {
    global $logger;
    
    $mboxContent = '';
    $totalEmails = count($emails);
    
    foreach ($emails as $index => $email) {
        $emailId = $email['id'];

        // Verificar si el correo ya ha sido procesado
        if (isset($processedEmails[$emailId]) && $processedEmails[$emailId]['status'] === true) {
            $logger->info("Correo ID $emailId ya procesado. Saltando...");
            continue;
        }

        // Actualizar el progreso
        $_SESSION['progress'] = 25 + (($index / $totalEmails) * 25);
        session_write_close();

        $logger->info("Procesando correo ID: $emailId");

        // Obtener el contenido del correo
        $emailData = makeApiRequest("https://www.googleapis.com/gmail/v1/users/me/messages/$emailId?format=raw", $sourceToken);

        if (isset($emailData['raw'])) {
            $rawEmail = base64_decode(strtr($emailData['raw'], '-_', '+/'));
            $mboxContent .= "From - " . date('r') . "\n" . $rawEmail . "\n\n";
        } else {
            $logger->warning("No se encontró 'raw' en la respuesta para el mensaje ID: $emailId");
        }

        // Guardar el correo como procesado pero con status false
        saveProcessedEmail($emailId, false);  // Marcar como procesado, pero no enviado aún
    }
    
    return $mboxContent;
}

// Función para enviar los correos procesados al destino
function sendEmailsToDestination($emails, $sourceToken, $destinationToken, $destinationEmail) {
    global $logger;
    
    foreach ($emails as $index => $email) {
        $emailId = $email['id'];
        
        // Verificar si el correo ya ha sido procesado
        if (isset($processedEmails[$emailId]) && $processedEmails[$emailId]['status'] === true) {
            $logger->info("Correo ID $emailId ya procesado. Saltando...");
            continue;
        }

        $_SESSION['progress'] = 50 + (($index / count($emails)) * 50);
        session_write_close();

        $logger->info("Enviando correo ID: $emailId");

        // Obtener el contenido del correo desde la cuenta de origen
        $emailData = makeApiRequest("https://www.googleapis.com/gmail/v1/users/me/messages/$emailId?format=raw", $sourceToken);
        
        if (isset($emailData['raw'])) {
            $rawEmail = base64_decode(strtr($emailData['raw'], '-_', '+/'));

            // Log para verificar el contenido del correo
            $logger->debug("Contenido del correo (raw): " . print_r($rawEmail, true));

            // Crear el objeto para enviar el correo
            $emailDataToSend = ["raw" => base64_encode($rawEmail)];

            // Modificar el destinatario del correo para enviarlo al correo 2 (destino)
            // Esto es importante para que el correo se envíe a la cuenta de destino
            $rawEmailModified = str_replace("To: .*", "To: $destinationEmail", $rawEmail); // Reemplazamos el campo 'To'

            // Crear el objeto para enviar el correo modificado
            $emailDataToSend = ["raw" => base64_encode($rawEmailModified)];

            // Enviar el correo a la cuenta de destino (pero desde el correo 1, no el destino)
            $ch = curl_init("https://www.googleapis.com/gmail/v1/users/me/messages/send");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $sourceToken", // Usar el token de la cuenta de origen
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailDataToSend));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);

            // Log para verificar la respuesta de la API
            $logger->debug("Respuesta al enviar correo: " . $response);

            if (curl_errno($ch)) {
                $logger->error("Error en cURL al enviar correo: " . curl_error($ch));
                throw new Exception("Error en cURL al enviar correo: " . curl_error($ch));
            }

            $responseData = json_decode($response, true);
            curl_close($ch);

            if (isset($responseData['error'])) {
                $logger->error("Error al migrar correo: " . print_r($responseData, true));
                throw new Exception("Error al migrar correo: " . print_r($responseData, true));
            }

            // Una vez enviado el correo, cambiar el status a true
            saveProcessedEmail($emailId, true);

            $logger->info("Correo enviado ID $emailId: " . $response);
        } else {
            $logger->warning("No se encontró 'raw' para el correo ID: $emailId");
        }
    }
}

// Función para manejar el flujo principal de la migración
function handleMigration($sourceToken, $destinationToken, $sourceEmail, $destinationEmail) {
    global $logger;

    // Obtener los correos de la cuenta de origen
    $emails = getEmails($sourceToken);
    $totalEmails = count($emails);
    $logger->info("Total de correos obtenidos de la cuenta de origen: $totalEmails");

    // Procesar los correos y generar el archivo de respaldo
    $mboxContent = processEmails($emails, $sourceToken, $destinationToken);
    file_put_contents("backup.mbox", $mboxContent);
    $logger->info("Respaldo generado con éxito.");

    // Si existe una cuenta de destino, enviar los correos
    if ($destinationEmail && $destinationToken) {
        sendEmailsToDestination($emails, $sourceToken, $destinationToken);
        $logger->info("Correos enviados a la cuenta de destino.");
    }

    $_SESSION['progress'] = 100;
    session_write_close();
    $logger->info("Proceso de migración completado exitosamente.");
    echo json_encode(["status" => "success", "message" => "Proceso completado."]);
}

// Verificar si el token de destino es válido
function checkDestinationToken($destinationToken) {
    $url = "https://www.googleapis.com/gmail/v1/users/me/profile";
    $response = makeApiRequest($url, $destinationToken);
    
    // Si hay un error, el token es inválido
    if (isset($response['error'])) {
        global $logger;
        $logger->error("Error en el token de destino: " . print_r($response, true));
        throw new Exception("Error en el token de destino.");
    }

    return $response;
}

header('Content-Type: application/json');

$sourceToken = $_POST['accessToken'] ?? null;
$destinationToken = $_POST['destinationAccessToken'] ?? null;
$sourceEmail = $_POST['sourceEmail'] ?? null;
$destinationEmail = $_POST['destinationEmail'] ?? null;

if (empty($sourceToken)) {
    $logger->error("Token de origen no proporcionado.");
    echo json_encode(["status" => "error", "message" => "Token de origen no proporcionado."]);
    exit();
}

if ($destinationEmail && empty($destinationToken)) {
    $logger->error("Token de destino no proporcionado.");
    echo json_encode(["status" => "error", "message" => "Token de destino no proporcionado."]);
    exit();
}

// Iniciar el proceso de migración
try {
    handleMigration($sourceToken, $destinationToken, $sourceEmail, $destinationEmail);
} catch (Exception $e) {
    $logger->error("Error en el proceso: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
