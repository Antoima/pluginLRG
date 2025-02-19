<?php
session_start();

// Verificar si el token de acceso está en localStorage
echo "<script>
    const accessToken = localStorage.getItem('access_token');
    if (!accessToken) {
        window.location.href = 'index.php'; // Redirigir si no está autenticado
    }
</script>";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Backup & Migration Tool</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .container {
            margin-top: 50px;
        }
        .form-group {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Email Backup & Migration Tool</h1>
        <form id="backupMigrationForm">
            <div class="form-group">
                <label for="sourceEmail">Source Email Account (Backup from)</label>
                <input type="email" class="form-control" id="sourceEmail" name="sourceEmail" required>
            </div>
            <div class="form-group">
                <label for="destinationEmail">Destination Email Account (Migrate to) (Optional)</label>
                <input type="email" class="form-control" id="destinationEmail" name="destinationEmail">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Start Backup/Migration</button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.all.min.js"></script>
    <script>
        $(document).ready(function () {
            // Obtener el token de acceso desde localStorage
            const accessToken = localStorage.getItem('access_token');

            $("#backupMigrationForm").on("submit", function (event) {
                event.preventDefault();

                const sourceEmail = $("#sourceEmail").val();
                const destinationEmail = $("#destinationEmail").val();

                if (!sourceEmail) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Por favor, ingresa la cuenta de origen.",
                    });
                    return;
                }

                Swal.fire({
                    title: "Procesando...",
                    text: "Por favor, espera mientras realizamos la copia de seguridad y migración.",
                    icon: "info",
                    showConfirmButton: false,
                    allowOutsideClick: false,
                });

                // Enviar los datos al servidor
                $.ajax({
                    url: "process_backup_migration.php",
                    type: "POST",
                    data: {
                        sourceEmail: sourceEmail,
                        destinationEmail: destinationEmail,
                        accessToken: accessToken
                    },
                    success: function (response) {
                        const result = JSON.parse(response);
                        if (result.status === "success") {
                            Swal.fire({
                                icon: "success",
                                title: "Éxito",
                                text: result.message,
                            });
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: result.message || "Hubo un problema al procesar la solicitud.",
                            });
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Hubo un problema al conectar con el servidor.",
                        });
                    },
                });
            });
        });
    </script>
</body>
</html>