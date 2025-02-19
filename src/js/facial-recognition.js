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
  try {
    const video = document.createElement("video");
    video.setAttribute("playsinline", ""); // Importante para móviles
    $("#cameraPreview").prepend(video);

    videoStream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: "user",
        width: { ideal: 640 },
        height: { ideal: 480 },
      },
    });

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
        .withFaceDescriptors();

      // Validar detecciones
      if (!detections || detections.length === 0) {
        throw new Error("No se detectaron rostros");
      }

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
      .withFaceDescriptors();

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
      $("#body").val(formatFaceData(detections[0]));

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

// 4. Función para formatear datos faciales en español
function formatFaceData(face) {
  const features = {
    gender: face.gender || "No detectado",
    age: Math.round(face.age) || "No detectado",
    emotions: face.expressions
      ? Object.entries(face.expressions)
          .map(([emotion, value]) => `${emotion}: ${(value * 100).toFixed(1)}%`)
          .join("\n")
      : "No detectado",
  };

  return `Datos faciales reconocidos:
- Género: ${features.gender}
- Edad aproximada: ${features.age}
- Emociones:
${features.emotions}
- Vector facial (128 dimensiones): 
${face.descriptor
  .slice(0, 5)
  .map((v) => v.toFixed(4))
  .join(", ")}...`;
}

// 5. Limpiar recursos
function cleanUpResources() {
  // Detener transmisión de cámara
  if (videoStream) {
    videoStream.getTracks().forEach((track) => {
      track.stop();
      videoStream.removeTrack(track);
    });
  }

  // Limpiar intervalos y elementos del DOM
  clearInterval(faceDetectionInterval);
  $("#cameraPreview").empty();

  console.log("Recursos liberados correctamente");
}
// 6. Detener cámara al enviar formulario
$("#sendEmailForm").on("submit", function (event) {
  event.preventDefault(); // Prevenir recarga de página
  cleanUpResources();
});

// 7. Inicializar
$(document).ready(() => {
  loadModels();
});
