<?php
// Cargar la configuración desde el archivo config.php
$config = require '/home/dh_292vea/configuracion/config.php';
$recaptchaSiteKey = $config['recaptcha_site_key'];
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Enviar Correo</title>
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
    <link rel="stylesheet" href="src/css/styles.css" />
    <!-- Incluir el script de reCAPTCHA con la clave dinámica -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo $recaptchaSiteKey; ?>"></script>
  </head>
  <body>
    <div class="container mt-5">
      <h1 class="text-center">Enviar Correo</h1>
      <form id="sendEmailForm" class="mt-4">
        <div class="form-group">
          <label for="to">Para:</label>
          <input type="email" class="form-control" id="to" name="to" required />
        </div>
        <div class="form-group">
          <label for="subject">Asunto:</label>
          <input
            type="text"
            class="form-control"
            id="subject"
            name="subject"
            required
          />
        </div>
        <div class="form-group">
          <label for="body">Cuerpo del mensaje:</label>
          <textarea
            class="form-control"
            id="body"
            name="body"
            rows="5"
            required
          ></textarea>
        </div>
        <div
          id="recaptchaError"
          class="text-danger"
          style="display: none; margin-top: 10px"
        ></div>
        <button type="submit" class="btn btn-primary btn-block">Enviar</button>
      </form>
    </div>

    <div id="loading" class="d-none">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Cargando...</span>
      </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-..." crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.all.min.js" integrity="sha512-..." crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha512-..." crossorigin="anonymous"></script>
    <script src="src/js/sendEmail.js"></script>
  </body>
</html>