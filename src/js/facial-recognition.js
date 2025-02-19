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
      faceapi.nets.ageGenderNet.loadFromUri("/models"), // Nuevo
      faceapi.nets.faceExpressionNet.loadFromUri("/models"), // Nuevo
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
    $("#cameraPreview").empty().append(video);

    videoStream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: "user",
        width: { ideal: 640 },
        height: { ideal: 480 },
      },
    });

    video.srcObject = videoStream;
    //await video.play();
    // Esperar a que la cámara esté lista
    await new Promise((resolve) => {
      video.onloadedmetadata = () => {
        video.width = video.videoWidth;
        video.height = video.videoHeight;
        video.play();
        resolve();
      };
    });

    // Ajustar tamaño de la cámara
    video.width = $("#cameraPreview").width();
    video.height = $("#cameraPreview").height();

    // Detección en tiempo real
    faceDetectionInterval = setInterval(async () => {
      const detections = await faceapi
        .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceExpressions() // Detección de emociones
        .withAgeAndGender() // Detección de edad y género
        .withFaceDescriptors();

      // Validación segura
      if (!detections || detections.length === 0) return;

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
    console.error("Error en cámara:", error);
    Swal.fire("Error", "No se pudo acceder a la cámara", "error");
  }
}

// 3. Capturar rostro
$("#captureButton").click(async () => {
  try {
    const video = $("#cameraPreview video")[0];
    const detections = await faceapi
      .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
      .withFaceLandmarks()
      .withFaceExpressions() // <- Agregar
      .withAgeAndGender() // <- Agregar
      .withFaceDescriptors();

    if (detections.length > 0) {
      $("#captureButton").html(
        '<i class="fas fa-spinner fa-spin"></i> Procesando...'
      );

      // Guardar todos los datos faciales
      $("#faceData").val(
        JSON.stringify({
          descriptor: detections[0].descriptor,
          gender: detections[0].gender,
          age: detections[0].age,
          expressions: detections[0].expressions,
        })
      );

      // Ocultar cámara y mostrar formulario
      $("#cameraSection").hide();
      $("#contentSection").show();
      $("#sendEmailForm").show();
      $("#submitButton").show();

      // Mostrar datos formateados
      $("#body").val(
        formatFaceData({
          gender: detections[0].gender,
          age: detections[0].age,
          expressions: detections[0].expressions,
        })
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

// 4. Función para formatear datos faciales
function formatFaceData(face) {
  const genderMap = {
    male: "Masculino",
    female: "Femenino",
  };

  return `Datos faciales reconocidos:
- Género: ${genderMap[face.gender] || "No detectado"}
- Edad aproximada: ${face.age ? Math.round(face.age) + " años" : "No detectado"}
- Emociones principales: 
${
  face.expressions
    ? Object.entries(face.expressions)
        .sort(([, a], [, b]) => b - a)
        .slice(0, 3)
        .map(([emotion, value]) => `• ${emotion}: ${(value * 100).toFixed(1)}%`)
        .join("\n")
    : "No detectado"
}`;
}

// En facial-recognition.js
$("#faceData").val(
  JSON.stringify({
    descriptor: detections[0].descriptor,
    gender: detections[0].gender,
    age: detections[0].age,
    expressions: detections[0].expressions,
  })
);

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
