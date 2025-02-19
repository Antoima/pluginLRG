let videoStream;
let faceDetectionInterval;

// 1. Cargar modelos
async function loadModels() {
  try {
    $("#loadingOverlay").show(); // Mostrar spinner

    await Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri("/models"),
      faceapi.nets.faceLandmark68Net.loadFromUri("/models"),
      faceapi.nets.faceRecognitionNet.loadFromUri("/models"),
    ]);

    $("#loadingOverlay").hide(); // Ocultar spinner
    Swal.fire(
      "Modelos cargados",
      "La IA está lista para reconocer rostros",
      "success"
    );
    startFaceDetection();
  } catch (error) {
    $("#loadingOverlay").hide();
    Swal.fire("Error", `Error cargando modelos: ${error.message}`, "error");
  }
}

// 2. Iniciar cámara y detección
async function startFaceDetection() {
  const video = document.createElement("video");
  $("#cameraPreview").prepend(video);

  try {
    videoStream = await navigator.mediaDevices.getUserMedia({ video: {} });
    video.srcObject = videoStream;
    await video.play();

    // Ajustar tamaño de la cámara
    video.width = $("#cameraPreview").width();
    video.height = $("#cameraPreview").height();

    // Detección en tiempo real
    faceDetectionInterval = setInterval(async () => {
      const detections = await faceapi
        .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptors(); // Corrección aquí

      // Dibujar resultados
      const canvas = faceapi.createCanvasFromMedia(video);
      $("#cameraPreview canvas").remove();
      $("#cameraPreview").append(canvas);

      const displaySize = { width: video.width, height: video.height };
      faceapi.matchDimensions(canvas, displaySize);

      const resizedDetections = faceapi.resizeResults(detections, displaySize);
      faceapi.draw.drawDetections(canvas, resizedDetections);
      faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);
    }, 100);
  } catch (error) {
    Swal.fire("Error", `Error de cámara: ${error.message}`, "error");
  }
}

// 3. Capturar rostro
$("#captureButton").click(async () => {
  try {
    const video = $("#cameraPreview video")[0];
    const detections = await faceapi
      .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
      .withFaceLandmarks()
      .withFaceDescriptors(); // Corrección aquí

    if (detections.length > 0) {
      $("#captureButton").html(
        '<i class="fas fa-spinner fa-spin"></i> Procesando...'
      );

      const faceData = detections[0].descriptor;
      $("#faceData").val(JSON.stringify(faceData));

      // Ocultar cámara y mostrar formulario
      $("#cameraSection").hide();
      $("#contentSection").show();
      $("#sendEmailForm").show();
      $("#submitButton").show();

      // Mostrar datos en el mensaje
      $("#body").val(
        `Datos faciales reconocidos:\n${JSON.stringify(faceData, null, 2)}`
      );

      Swal.fire("¡Éxito!", "Rostro reconocido correctamente", "success");
    } else {
      Swal.fire("Error", "No se detectó ningún rostro", "error");
    }
  } catch (error) {
    Swal.fire("Error", `Error en reconocimiento: ${error.message}`, "error");
  } finally {
    $("#captureButton").html('<i class="fas fa-camera"></i> Capturar Rostro');
  }
});

// 4. Detener cámara al enviar formulario
$("#sendEmailForm").submit(() => {
  if (videoStream) {
    videoStream.getTracks().forEach((track) => track.stop());
  }
  clearInterval(faceDetectionInterval);
});

// Inicializar
$(document).ready(() => {
  loadModels();
});
