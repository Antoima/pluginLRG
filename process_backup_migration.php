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
function saveProcessedEmail($emailId) {
    $processedEmails = getProcessedEmails();
    
    // Si el correo no está en la lista, lo agregamos
    if (!in_array($emailId, $processedEmails)) {
        $processedEmails[] = $emailId;
        file_put_contents('processed_emails.json', json_encode($processedEmails));
        global $logger;
        $logger->info("Correo ID $emailId marcado como procesado.");
    }
}

// Función para truncar las respuestas largas
function truncateResponse($response) {
    return substr(print_r($response, true), 0, 500); // Trunca a 500 caracteres
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
    
    $responseData = json_decode($response, true);
    
    // Depuración adicional: Logueamos la respuesta de la API truncada
    global $logger;
    if ($responseData === null) {
        $logger->error("Respuesta de la API no válida: " . truncateResponse($response));
    } else {
        $logger->debug("Respuesta de la API (truncada): " . truncateResponse($responseData));
    }
    
    return $responseData;
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
        if (in_array($emailId, getProcessedEmails())) {
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

        saveProcessedEmail($emailId);  // Marcar como procesado
    }
    
    return $mboxContent;
}

// Función para enviar los correos procesados al destino
function sendEmailsToDestination($emails, $sourceToken, $destinationToken) {
    global $logger;
    
    foreach ($emails as $index => $email) {
        $emailId = $email['id'];
        
        // Verificar si el correo ya ha sido procesado
        if (in_array($emailId, getProcessedEmails())) {
            $logger->info("Correo ID $emailId ya procesado. Saltando...");
            continue;
        }

        $_SESSION['progress'] = 50 + (($index / count($emails)) * 50);
        session_write_close();

        $logger->info("Enviando correo ID: $emailId");

        // Obtener el contenido del correo
        $emailData = makeApiRequest("https://www.googleapis.com/gmail/v1/users/me/messages/$emailId?format=raw", $sourceToken);
        
        if (isset($emailData['raw'])) {
            // El 'raw' contiene los datos codificados en base64, lo que está bien
            $rawEmail = base64_decode(strtr($emailData['raw'], '-_', '+/'));

            // Crear el objeto para enviar el correo
            $emailDataToSend = ["raw" => base64_encode($rawEmail)];

            // Log para ver los datos que estamos enviando a la API
            $logger->debug("URL de solicitud para enviar correo: https://www.googleapis.com/gmail/v1/users/me/messages/send");
            $logger->debug("Enviando datos del correo ID $emailId: " . print_r($emailDataToSend, true));  // Datos del correo

            // Enviar el correo a la cuenta de destino
            $ch = curl_init("https://www.googleapis.com/gmail/v1/users/me/messages/send");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $destinationToken",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailDataToSend));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Solicitar respuesta cURL
            $response = curl_exec($ch);

            // Verifica si cURL tiene algún error
            if ($response === false) {
                $logger->error("Error en cURL al enviar correo ID $emailId: " . curl_error($ch));
            } else {
                $responseData = json_decode($response, true);
                $logger->debug("Respuesta completa al enviar correo ID $emailId: " . print_r($responseData, true));  // Respuesta completa
            }

            // Comprobamos si hubo un error en la respuesta
            if (curl_errno($ch)) {
                $logger->error("Error en cURL al enviar correo ID $emailId: " . curl_error($ch));
                throw new Exception("Error en cURL al enviar correo ID $emailId: " . curl_error($ch));
            }

            if (isset($responseData['error'])) {
                $logger->error("Error al migrar correo ID $emailId: " . print_r($responseData, true));
                throw new Exception("Error al migrar correo ID $emailId: " . print_r($responseData, true));
            }

            $logger->info("Correo enviado exitosamente ID $emailId.");
            saveProcessedEmail($emailId);  // Marcar como procesado solo después de que se haya enviado
        } else {
            $logger->warning("No se encontró 'raw' para el correo ID: $emailId");
        }
    }
}

// Función para manejar el flujo principal de la migración
function handleMigration($sourceToken, $destinationToken, $sourceEmail, $destinationEmail) {
    global $logger;

    // Verificar los tokens antes de continuar
    try {
        checkSourceToken($sourceToken); // Verificar token de origen
        $logger->info("Token de origen verificado con éxito.");
        
        if ($destinationEmail && $destinationToken) {
            checkDestinationToken($destinationToken); // Verificar token de destino
            $logger->info("Token de destino verificado con éxito.");
        }
    } catch (Exception $e) {
        $logger->error("Error al verificar tokens: " . $e->getMessage());
        echo json_encode(["status" => "error", "message" => "Error al verificar los tokens."]);
        exit();
    }

    // Obtener los correos de la cuenta de origen
    $emails = getEmails($sourceToken);
    $totalEmails = count($emails);
    $logger->info("Total de correos obtenidos de la cuenta de origen: $totalEmails");

    // Procesar los correos y generar el archivo de respaldo
    $mboxContent = processEmails($emails, $sourceToken, $destinationToken);
    file_put_contents("backup.mbox", $mboxContent);
    $logger->info("Respaldo generado con éxito.");

    // Enviar los correos procesados al destino
    sendEmailsToDestination($emails, $sourceToken, $destinationToken);

    $_SESSION['progress'] = 100;
    session_write_close();
    $logger->info("Proceso de migración completado exitosamente.");
    echo json_encode(["status" => "success", "message" => "Proceso completado."]);
}

// Función para verificar el token de destino
function checkDestinationToken($destinationToken) {
    global $logger;
    $url = "https://www.googleapis.com/gmail/v1/users/me/profile";
    $response = makeApiRequest($url, $destinationToken);
    
    // Si hay un error, el token de destino es inválido
    if (isset($response['error'])) {
        $logger->error("Error en el token de destino: " . print_r($response, true));
        throw new Exception("Error en el token de destino.");
    }

    return $response;
}


// Función para verificar el token de origen
function checkSourceToken($sourceToken) {
    global $logger;
    $url = "https://www.googleapis.com/gmail/v1/users/me/profile";
    $response = makeApiRequest($url, $sourceToken);

    // Si hay un error, el token de origen es inválido
    if (isset($response['error'])) {
        $logger->error("Error en el token de origen: " . print_r($response, true));
        throw new Exception("Error en el token de origen.");
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
