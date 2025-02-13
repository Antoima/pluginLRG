<?php
// Cargar la configuración desde el archivo config.php
$config = require '/home/dh_292vea/configuracion/config.php';

// Rutas para la verificación de firma digital
$ruta_clave_publica = '/home/dh_292vea/claves_publicas/public_key.pem'; // Ruta a la clave pública
$ruta_firma = '/home/dh_292vea/firmas/firma.bin'; // Ruta a la firma generada

// Cargar la clave pública
$clave_publica = openssl_pkey_get_public(file_get_contents($ruta_clave_publica));
if (!$clave_publica) {
    die("Error al cargar la clave pública.");
} else {
    echo "<script>console.log('Clave pública cargada correctamente.');</script>";
}

// Cargar la firma
$firma = file_get_contents($ruta_firma);
if (!$firma) {
    die("Error al cargar la firma.");
} else {
    echo "<script>console.log('Firma cargada correctamente.');</script>";
}

// Cargar el contenido del archivo actual
$datos = file_get_contents(__FILE__);
if (!$datos) {
    die("Error al cargar el archivo.");
} else {
    echo "<script>console.log('Archivo cargado correctamente.');</script>";
}

// Verificar la firma
$resultado = openssl_verify($datos, $firma, $clave_publica, OPENSSL_ALGO_SHA256);

if ($resultado === 1) {
    echo "<script>console.log('La firma es válida. El archivo no ha sido modificado.');</script>";
} elseif ($resultado === 0) {
    echo "<script>console.error('¡Advertencia! El archivo ha sido modificado. Acceso denegado.');</script>";
    die("¡Advertencia! El archivo ha sido modificado. Acceso denegado.");
} else {
    die("Error al verificar la firma.");
}

// Continuar con la carga normal de la página
$googleClientId = $config['google_client_id'];
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Probar Conexión SMTP</title>
    <meta name="description" content="Página para probar la conexión SMTP con diferentes proveedores de correo electrónico." />
    <meta name="keywords" content="SMTP, conexión, correo electrónico, Gmail, Outlook, Yahoo, SMTP2GO" />
    <meta name="robots" content="index, follow" />
    <meta property="og:title" content="Probar Conexión SMTP" />
    <meta property="og:description" content="Página para probar la conexión SMTP con diferentes proveedores de correo electrónico." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://pl.luisguevara.net/probar-conexion-smtp" />
    <meta property="og:image" content="https://pl.luisguevara.net/imagen.jpg" />
    <link rel="canonical" href="https://pl.luisguevara.net/probar-conexion-smtp" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.min.css" integrity="sha512-Yn5Z4XxNnXXE8Y+h/H1fwG/2qax2MxG9GeUOWL6CYDCSp4rTFwUpOZ1PS6JOuZaPBawASndfrlWYx8RGKgILhg==" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha512-MoRNloxbStBcD8z3M/2BmnT+rg4IsMxPkXaGh2zD6LGNNFE80W3onsAhRcMAMrSoyWL9xD7Ert0men7vR8LUZg==" crossorigin="anonymous">
    <link
      rel="stylesheet"
      href="src/css/styles.css"
    />
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <meta name="google-signin-client_id" content="<?php echo $googleClientId; ?>">
    <script>
      // Pasar la variable PHP a JavaScript
      const googleClientId = "<?php echo $googleClientId; ?>";
    </script>
  </head>
  <body>
    <div id="loading">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Cargando...</span>
      </div>
    </div>
    <div class="container mt-5">
      <h1 class="text-center">Lo veo y lo quiero, SMTP</h1>
      <form id="smtpForm" class="mt-4">
        <div class="form-group">
          <label for="host">Host:</label>
          <select class="form-control" id="host" name="host" required>
            <option value="smtp.gmail.com">Gmail (smtp.gmail.com)</option>
            <option value="smtp.office365.com">Outlook (smtp.office365.com)</option>
            <option value="smtp.mail.yahoo.com">Yahoo (smtp.mail.yahoo.com)</option>
            <option value="mail.smtp2go.com">SMTP2GO (mail.smtp2go.com)</option>
          </select>
        </div>
        <div class="form-group">
          <label for="puerto">Puerto:</label>
          <select class="form-control" id="puerto" name="puerto" required>
            <!-- Los puertos se actualizarán dinámicamente -->
          </select>
        </div>
        <div class="form-group">
          <label for="usuario">Usuario:</label>
          <input type="email" class="form-control" id="usuario" name="usuario" required disabled />
        </div>
        <div class="form-group">
          <label for="contraseña">Contraseña:</label>
          <input type="password" class="form-control" id="contraseña" name="contraseña" required disabled />
        </div>
        <button type="button" id="checkGoogleConnection" class="btn btn-google btn-block mt-2">
          <span id="googleButtonText">Comprobar Conexión con Google</span>
          <div id="googleButtonSpinner" class="spinner-border spinner-border-sm text-light d-none" role="status">
            <span class="sr-only">Cargando...</span>
          </div>
        </button>
      </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.all.min.js" integrity="sha512-em9sd3gU/F3r7Xwm6gmW9yqCTBMrtF32wxHRQ8XS4MxW+tdW2mi16ZfbEj+i8iEhHCdgGnUWwSF+RX3WJiSjJA==" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha512-M5KW3ztuIICmVIhjSqXe01oV2bpe248gOxqmlcYrEzAvws7Pw3z6BK0iGbrwvdrUQUhi3eXgtxp5I8PDo9YfjQ==" crossorigin="anonymous"></script>
     <script src="src/js/scripts.js"></script>
    <script src="src/js/sendEmail.js"></script>
  </body>
</html>