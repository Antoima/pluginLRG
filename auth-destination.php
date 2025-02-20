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
        window.opener.postMessage({
            action: 'destinationAuthenticated',
            accessToken: accessToken
        }, '*');
        window.close();
    } else {
        document.write('Error: Parámetros de autenticación no válidos.');
    }
</script>
</body>
</html>