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
              try {
                const response = JSON.parse(data);
                if (response.success) {
                  sendEmail(accessToken, to, subject, body);
                } else {
                  showError(response.message);
                }
              } catch (e) {
                showError("Respuesta inválida del servidor.");
              }
            })
            .fail(() => {
              showError("Error de conexión.");
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
