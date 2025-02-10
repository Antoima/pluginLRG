<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Probar Conexión SMTP</title>
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.min.css"
    />
    <link
      rel="stylesheet"
      href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
    />
  </head>
  <body>
    <div class="container mt-5">
      <h1 class="text-center">Probar Conexión SMTP</h1>
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
          <input type="email" class="form-control" id="usuario" name="usuario" required />
        </div>
        <div class="form-group">
          <label for="contraseña">Contraseña:</label>
          <input type="password" class="form-control" id="contraseña" name="contraseña" required />
        </div>
        <button type="submit" class="btn btn-primary btn-block">Probar Conexión</button>
      </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.all.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
      $(document).ready(function () {
        const puertosPorHost = {
          "smtp.gmail.com": ["587 (TLS)", "465 (SSL)"],
          "smtp.office365.com": ["587 (TLS)"],
          "smtp.mail.yahoo.com": ["587 (TLS)", "465 (SSL)"],
          "mail.smtp2go.com": ["2525 (TLS)", "587 (TLS)", "465 (SSL)"]
        };

        function actualizarPuertos() {
          const hostSeleccionado = $("#host").val();
          const puertos = puertosPorHost[hostSeleccionado] || [];
          const puertoSelect = $("#puerto");
          puertoSelect.empty();
          puertos.forEach(puerto => {
            puertoSelect.append(new Option(puerto, puerto.split(" ")[0]));
          });
        }

        $("#host").on("change", actualizarPuertos);
        actualizarPuertos(); // Llamar al cargar la página para establecer los puertos iniciales

        $("#smtpForm").on("submit", function (event) {
          event.preventDefault();
          $.ajax({
            url: "includes/hp.php",
            type: "POST",
            data: $(this).serialize(),
            success: function (response) {
              const result = JSON.parse(response);
              if (result.status === 'success') {
                console.log("Conexión SMTP exitosa:", result.message);
                Swal.fire({
                  icon: 'success',
                  title: 'Éxito',
                  text: result.message,
                });
              } else {
                console.error("Error en la conexión SMTP:", result.message);
                Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: "Hubo un problema al intentar conectar con el servidor SMTP. Por favor, revisa los datos e inténtalo de nuevo.",
                });
              }
            },
            error: function (xhr, status, error) {
              console.error("Error en la conexión SMTP:", xhr.responseText);
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: "Hubo un problema al intentar conectar con el servidor SMTP. Por favor, revisa los datos e inténtalo de nuevo.",
              });
            },
          });
        });
      });
    </script>
  </body>
</html>