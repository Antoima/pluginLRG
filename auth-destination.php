<?php
session_start();
// Configuración necesaria para PHP (sin JS aquí)
require '/home/dh_292vea/configuracion/config.php';
?>
<!DOCTYPE html>
<html>
<body>
<script>
    // Obtener parámetros del hash
    const hash = window.location.hash.substring(1);
    const params = new URLSearchParams(hash);
    const accessToken = params.get('access_token');

    if (accessToken && window.opener) {
        // Obtener email y enviar datos
        fetch("https://www.googleapis.com/oauth2/v1/userinfo?alt=json", {
            headers: { "Authorization": `Bearer ${accessToken}` }
        })
        .then(response => response.json())
        .then(data => {
            // ✅ Enviar datos a la ventana principal
            window.opener.postMessage({
                action: "destinationAuthenticated",
                accessToken: accessToken,
                email: data.email,
                refresh_token: data.refresh_token // Si está disponible
            }, "*");
            window.close();
        })
        .catch(error => {
            console.error('Error:', error);
            window.opener.postMessage({
                action: "authError",
                error: "Error en autenticación: " + error.message
            }, "*");
            window.close();
        });
    } else {
        window.opener.postMessage({
            action: "authError",
            error: "Falta token de acceso"
        }, "*");
        window.close();
    }
</script>
</body>
</html>
