<?php
session_start();
?>
<!DOCTYPE html>
<html>
<body>
<script>
    // Extraer parámetros del hash de la URL
    const hash = window.location.hash.substring(1);
    const params = new URLSearchParams(hash);
    const accessToken = params.get('access_token');
    const error = params.get('error');

    if (error) {
        window.opener.postMessage({
            action: 'authError',
            error: error
        }, '*');
        window.close();
    } else if (accessToken && window.opener) {
        // Obtener el correo electrónico del usuario autenticado
        fetch("https://www.googleapis.com/oauth2/v1/userinfo?alt=json", {
            headers: {
                Authorization: `Bearer ${accessToken}`
            }
        })
        .then(response => response.json())
        .then(data => {
            window.opener.postMessage({
                action: 'destinationAuthenticated',
                accessToken: accessToken,
                email: data.email // Enviar el correo electrónico
            }, '*');
            window.close();
        })
        .catch(error => {
            window.opener.postMessage({
                action: 'authError',
                error: 'No se pudo obtener el correo electrónico.'
            }, '*');
            window.close();
        });
    } else {
        document.write('Error: Parámetros de autenticación no válidos.');
    }
</script>
</body>
</html>