<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faceData = json_decode($_POST['face_data'], true);

    if (!empty($faceData)) {
        // Aquí puedes guardar los datos del rostro en una base de datos o compararlos con otros
        echo "Rostro capturado correctamente.";
    } else {
        echo "Error: No se detectó ningún rostro.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Enviar Correo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.min.css" integrity="sha512-Yn5Z4XxNnXXE8Y+h/H1fwG/2qax2MxG9GeUOWL6CYDCSp4rTFwUpOZ1PS6JOuZaPBawASndfrlWYx8RGKgILhg==" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha512-MoRNloxbStBcD8z3M/2BmnT+rg4IsMxPkXaGh2zD6LGNNFE80W3onsAhRcMAMrSoyWL9xD7Ert0men7vR8LUZg==" crossorigin="anonymous">
    <link rel="stylesheet" href="src/css/styles.css" />
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo $recaptchaSiteKey; ?>"></script>
    <style>
        #cameraPreview { width: 300px; height: 300px; border: 2px solid #ccc; }
        #captureButton { margin-top: 10px; }
    </style>
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
          <input type="text" class="form-control" id="subject" name="subject" required />
        </div>
        <div class="form-group">
          <label for="body">Cuerpo del mensaje:</label>
          <textarea class="form-control" id="body" name="body" rows="5" required></textarea>
        </div>
        <div id="recaptchaError" class="text-danger" style="display: none; margin-top: 10px"></div>
                    <!-- Sección de reconocimiento facial -->
                    <div class="form-group">
                <label>Verificación Facial:</label>
                <div id="cameraPreview"></div>
                <button type="button" id="captureButton" class="btn btn-secondary">Capturar Rostro</button>
                <input type="hidden" id="faceData" name="face_data">
            </div>
        <button type="submit" class="btn btn-primary btn-block">Enviar</button>     
      </form>
    </div>

    <div id="loading" class="d-none">
      <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Cargando...</span>
      </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.all.min.js" integrity="sha512-em9sd3gU/F3r7Xwm6gmW9yqCTBMrtF32wxHRQ8XS4MxW+tdW2mi16ZfbEj+i8iEhHCdgGnUWwSF+RX3WJiSjJA==" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha512-M5KW3ztuIICmVIhjSqXe01oV2bpe248gOxqmlcYrEzAvws7Pw3z6BK0iGbrwvdrUQUhi3eXgtxp5I8PDo9YfjQ==" crossorigin="anonymous"></script>
    <script src="src/js/sendEmail.js"></script>
          <!-- Agrega face-api.js -->
          <script src="https://cdn.jsdelivr.net/npm/face-api.js/dist/face-api.min.js"></script>
    <script>
        // Cargar modelos
        async function loadModels() {
            await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
            await faceapi.nets.faceLandmark68Net.loadFromUri('/models');
            await faceapi.nets.faceRecognitionNet.loadFromUri('/models');
        }

        // Iniciar cámara
        async function startCamera() {
            const video = document.createElement('video');
            document.getElementById('cameraPreview').appendChild(video);

            const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
            video.srcObject = stream;
            video.play();

            return video;
        }

        // Capturar rostro
        async function captureFace() {
            const video = await startCamera();
            const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptors();

            if (detections.length > 0) {
                const faceData = detections[0].descriptor;
                document.getElementById('faceData').value = JSON.stringify(faceData);
                alert('Rostro capturado correctamente.');
            } else {
                alert('No se detectó ningún rostro.');
            }
        }

        // Inicializar
        loadModels().then(() => {
            document.getElementById('captureButton').addEventListener('click', captureFace);
        });
    </script>
  </body>
</html>