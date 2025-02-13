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
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.min.css"
      integrity="sha512-..."
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
      integrity="sha512-..."
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="src/css/styles.css"
    />
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <meta name="google-signin-client_id" content="<?php echo $googleClientId; ?>">
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-..." crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.all.min.js" integrity="sha512-..." crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha512-..." crossorigin="anonymous"></script>
    <script src="src/js/scripts.js"></script>
    <script src="src/js/sendEmail.js"></script>
  </body>
</html>