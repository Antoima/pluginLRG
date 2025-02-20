<?php
session_start();

echo "<script>
    const accessToken = localStorage.getItem('access_token');
    if (!accessToken) {
        window.location.href = 'index.php';
    }
</script>";
?>

<?php
// Incluir config.php al inicio del archivo
$config = require '/home/dh_292vea/configuracion/config.php';
$googleClientId = $config['google_client_id'];
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
        .container { margin-top: 50px; }
        .form-group { margin-bottom: 20px; }
        .progress { margin-top: 20px; display: none; }
        .authenticated {
            border: 2px solid #28a745 !important;
            background-color: #f8fff9 !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Email Backup & Migration Tool</h1>
        <form id="backupMigrationForm">
            <div class="form-group">
                <label for="sourceEmail">Source Email Account (Backup from)</label>
                <input type="email" class="form-control" id="sourceEmail" name="sourceEmail" required readonly>
            </div>
            <div class="form-group">
                <label for="destinationEmail">Destination Email Account (Migrate to) (Optional)</label>
                <div class="input-group">
                    <input type="email" class="form-control" id="destinationEmail" name="destinationEmail" readonly>
                    <div class="input-group-append">
                        <button type="button" id="authDestinationBtn" class="btn btn-primary">
                            Autenticar
                        </button>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Start Backup/Migration</button>
        </form>

        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 0%;">0%</div>
        </div>

        <div id="downloadBackup" class="text-center mt-4" style="display: none;">
            <a href="backup.mbox" class="btn btn-success btn-lg" download>
                <i class="fas fa-download"></i> Descargar Respaldo
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.all.min.js"></script>
    <script>
        $(document).ready(function () {
            const accessToken = localStorage.getItem('access_token');
            let destinationAccessToken = localStorage.getItem('destination_access_token');

            // Escuchar mensajes desde auth-destination.php
            window.addEventListener('message', (event) => {
                if (event.data.action === 'destinationAuthenticated') {
                    $("#destinationEmail")
                        .addClass("authenticated")
                        .val("Cuenta autenticada ✔️");
                    $("#authDestinationBtn")
                        .prop("disabled", true)
                        .text("Autenticado");
                    localStorage.setItem('destination_access_token', event.data.accessToken);
                }
            });

            // Obtener el correo del usuario autenticado
            function getUserInfo(accessToken) {
                return $.ajax({
                    url: "https://www.googleapis.com/oauth2/v1/userinfo?alt=json",
                    type: "GET",
                    headers: {
                        Authorization: `Bearer ${accessToken}`,
                    },
                });
            }

            // Verificar y renovar el token si es necesario
            async function getValidAccessToken() {
                let accessToken = localStorage.getItem('access_token');
                const refreshToken = localStorage.getItem('refresh_token');

                if (!accessToken) {
                    throw new Error("No hay token de acceso disponible.");
                }

                // Verificar si el token es válido
                try {
                    await getUserInfo(accessToken);
                    return accessToken;
                } catch (error) {
                    // Si el token ha expirado, renovarlo
                    if (refreshToken) {
                        const response = await fetch("https://oauth2.googleapis.com/token", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded",
                            },
                            body: new URLSearchParams({
                                client_id: "<?php echo $googleClientId; ?>",
                                client_secret: "<?php echo $config['google_client_secret']; ?>",
                                refresh_token: refreshToken,
                                grant_type: "refresh_token",
                            }),
                        });

                        const data = await response.json();
                        accessToken = data.access_token;
                        localStorage.setItem('access_token', accessToken);
                        return accessToken;
                    } else {
                        throw new Error("El token ha expirado y no hay refresh_token disponible.");
                    }
                }
            }

            // Autocompletar correo de origen
            getValidAccessToken()
                .then((accessToken) => {
                    return getUserInfo(accessToken);
                })
                .then((response) => {
                    $("#sourceEmail").val(response.email);
                })
                .catch((error) => {
                    Swal.fire("Error", "No se pudo obtener la información del usuario.", "error");
                    console.error("Error:", error);
                });

            // Autenticar cuenta de destino
            $("#authDestinationBtn").click(() => {
                const clientId = "<?php echo $googleClientId; ?>"; // Usar el client_id desde PHP
                const redirectUri = encodeURIComponent("https://pl.luisguevara.net/auth-destination.php"); // Codificar la URL
                const scope = "https://www.googleapis.com/auth/gmail.send";
                
                // Construir la URL sin saltos de línea ni espacios
                const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${clientId}&redirect_uri=${redirectUri}&response_type=token&scope=${scope}&state=destination&prompt=select_account`;
                
                window.location.href = authUrl;
            });

            // Verificar si ya hay token de destino
            if (destinationAccessToken) {
                $("#destinationEmail").addClass("authenticated").val("Cuenta autenticada ✔️");
                $("#authDestinationBtn").prop("disabled", true);
            }

            // Enviar formulario
            $("#backupMigrationForm").on("submit", function (e) {
                e.preventDefault();
                const destinationEmail = $("#destinationEmail").val();

                if (destinationEmail && !destinationAccessToken) {
                    Swal.fire("Error", "Primero autentica la cuenta de destino.", "error");
                    return;
                }

                $(".progress").show();
                $.ajax({
                    url: "process_backup_migration.php",
                    type: "POST",
                    data: {
                        sourceEmail: $("#sourceEmail").val(),
                        destinationEmail: destinationEmail,
                        accessToken: accessToken,
                        destinationAccessToken: destinationAccessToken
                    },
                    success: (response) => {
                        const result = JSON.parse(response);
                        Swal.fire(result.status === "success" ? "Éxito" : "Error", result.message, result.status);
                        if (result.status === "success") $("#downloadBackup").show();
                    },
                    error: () => Swal.fire("Error", "Error de conexión con el servidor.", "error")
                });
            });
        });
    </script>
</body>
</html>