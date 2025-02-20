<?php
session_start();
?>
<!DOCTYPE html>
<html>
<body>
<script>
    const hash = window.location.hash.substring(1);
    const params = new URLSearchParams(hash);
    const accessToken = params.get('access_token');

    if (accessToken && window.opener) {
        // Obtener el email usando el token
        fetch("https://www.googleapis.com/oauth2/v1/userinfo?alt=json", {
            headers: {
                "Authorization": `Bearer ${accessToken}`
            }
        })
        .then(response => response.json())
        .then(data => {
            // Enviar el token y el email a la ventana principal
            window.opener.postMessage({
                action: "destinationAuthenticated",
                accessToken: accessToken,
                email: data.email // <-- Enviar el email
            }, "*");
            window.close();
        })
        .catch(error => {
            // Enviar un mensaje de error si falla
            window.opener.postMessage({
                action: "authError",
                error: "Error al obtener el email"
            }, "*");
            window.close();
        });
    } else {
        // Cerrar la ventana si no hay token
        window.close();
    }
</script>
</body>
</html>