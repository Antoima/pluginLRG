$(document).ready(function () {
  const params = new URLSearchParams(window.location.search);
  const accessToken = params.get("access_token");

  $("#sendEmailForm").on("submit", function (event) {
    event.preventDefault();

    grecaptcha.ready(() => {
      grecaptcha
        .execute(recaptchaSiteKey, { action: "submit" })
        .then((token) => {
          const to = $("#to").val();
          const subject = $("#subject").val();
          const body = $("#body").val();

          // Verificar reCAPTCHA
          $.post("verifyRecaptcha.php", { recaptchaResponse: token })
            .done((data) => {
              console.log("Respuesta del servidor:", data); // Depuración

              // Verificar si data es un objeto o una cadena JSON
              let response;
              if (typeof data === "string") {
                try {
                  response = JSON.parse(data);
                } catch (e) {
                  showError(`Respuesta inválida: ${data}`);
                  return;
                }
              } else if (typeof data === "object") {
                response = data; // data ya es un objeto
              } else {
                showError(`Respuesta inválida: ${data}`);
                return;
              }

              // Manejar la respuesta
              if (response.success) {
                sendEmail(accessToken, to, subject, body);
              } else {
                showError(`Error de reCAPTCHA: ${response.message}`);
              }
            })
            .fail((xhr) => {
              showError(`Error de conexión: ${xhr.statusText}`);
            });
        });
    });
  });

  function sendEmail(accessToken, to, subject, body) {
    const email = [
      `To: ${to}`,
      'Content-Type: text/plain; charset="UTF-8"',
      "MIME-Version: 1.0",
      `Subject: ${subject}`,
      "",
      body,
    ].join("\n");

    const base64Email = btoa(unescape(encodeURIComponent(email)))
      .replace(/\+/g, "-")
      .replace(/\//g, "_")
      .replace(/=+$/, "");

    $.ajax({
      url: "https://www.googleapis.com/gmail/v1/users/me/messages/send",
      type: "POST",
      contentType: "application/json",
      headers: { Authorization: `Bearer ${accessToken}` },
      data: JSON.stringify({ raw: base64Email }),
      success: () => {
        Swal.fire("Éxito", "Correo enviado correctamente.", "success");
        resetUI();
      },
      error: (xhr) => {
        const errorMsg =
          xhr.responseJSON?.error?.message || "Error desconocido.";
        Swal.fire("Error", `Falló el envío: ${errorMsg}`, "error");
      },
    });
  }

  function showError(message) {
    $("#recaptchaError").text(message).show();
    console.error("Error:", message);
  }

  function resetUI() {
    $("#sendEmailForm")[0].reset();
    $("#contentSection").hide();
    $("#cameraSection").show();
  }
});
