let videoStream;
let faceDetectionInterval;

// 1. Cargar modelos
async function loadModels() {
  try {
    $("#loadingOverlay").show();

    await Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri("/models"),
      faceapi.nets.faceLandmark68Net.loadFromUri("/models"),
      faceapi.nets.faceRecognitionNet.loadFromUri("/models"),
      faceapi.nets.ageGenderNet.loadFromUri("/models"),
      faceapi.nets.faceExpressionNet.loadFromUri("/models"),
    ]);

    $("#loadingOverlay").hide();
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
    video.setAttribute("playsinline", "");
    $("#cameraPreview").empty().append(video);

    videoStream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: "user",
        width: { ideal: 640 },
        height: { ideal: 480 },
      },
    });

    video.srcObject = videoStream;

    await new Promise((resolve) => {
      video.onloadedmetadata = () => {
        video.width = video.videoWidth;
        video.height = video.videoHeight;
        video.play();
        resolve();
      };
    });

    video.width = $("#cameraPreview").width();
    video.height = $("#cameraPreview").height();

    faceDetectionInterval = setInterval(async () => {
      const detections = await faceapi
        .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceExpressions()
        .withAgeAndGender()
        .withFaceDescriptors();

      if (!detections || detections.length === 0) return;

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
      .withFaceExpressions()
      .withAgeAndGender()
      .withFaceDescriptors();

    if (detections.length > 0) {
      $("#captureButton").html(
        '<i class="fas fa-spinner fa-spin"></i> Procesando...'
      );

      $("#faceData").val(
        JSON.stringify({
          descriptor: detections[0].descriptor,
          gender: detections[0].gender,
          age: detections[0].age,
          expressions: detections[0].expressions,
        })
      );

      $("#cameraSection").hide();
      $("#contentSection").show();
      $("#sendEmailForm").show();
      $("#submitButton").show();

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

// 4. Formatear datos
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

// 5. Limpiar recursos
function cleanUpResources() {
  if (videoStream) {
    videoStream.getTracks().forEach((track) => {
      track.stop();
      videoStream.removeTrack(track);
    });
  }
  clearInterval(faceDetectionInterval);
  $("#cameraPreview").empty();
  console.log("Recursos liberados correctamente");
}

// 6. Evento submit
$("#sendEmailForm").on("submit", function (event) {
  event.preventDefault();
  cleanUpResources();
});

// 7. Inicializar
$(document).ready(() => {
  loadModels();
});
