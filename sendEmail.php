<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faceData = json_decode($_POST['face_data'], true);

    if (!empty($faceData)) {
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        #cameraPreview { 
            width: 100%; 
            height: 400px; 
            border: 2px solid #ccc;
            position: relative;
        }
        #captureButton { 
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
        }
        #sendEmailForm, #submitButton { display: none; }
        #modelLoading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }
        .face-box {
            position: absolute;
            border: 2px solid #00ff00;
            background: rgba(0, 255, 0, 0.1);
        }
        @media (max-width: 768px) {
            #cameraPreview { height: 300px; }
        }
    </style>
</head>
<body>
    <!-- Spinner de carga -->
    <div id="modelLoading" class="spinner-border text-primary" role="status">
        <span class="sr-only">Cargando modelos...</span>
    </div>

    <div class="container mt-5">
        <h1 class="text-center mb-4">Enviar Correo</h1>
        
        <!-- Sección de cámara -->
        <div id="cameraSection">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Verificación Facial</h5>
                    <div id="cameraPreview">
                        <button type="button" id="captureButton" class="btn btn-primary btn-lg">
                            <i class="fas fa-camera"></i> Capturar Rostro
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario oculto inicialmente -->
        <form id="sendEmailForm" class="mt-4" method="POST">
            <input type="hidden" id="faceData" name="face_data">
            
            <div class="form-group">
                <label for="to">Destinatario:</label>
                <input type="email" class="form-control" id="to" name="to" required>
            </div>
            
            <div class="form-group">
                <label for="subject">Asunto:</label>
                <input type="text" class="form-control" id="subject" name="subject" required>
            </div>
            
            <div class="form-group">
                <label for="body">Mensaje:</label>
                <textarea class="form-control" id="body" name="body" rows="5" required></textarea>
            </div>
            
            <button type="submit" id="submitButton" class="btn btn-success btn-block">
                <i class="fas fa-paper-plane"></i> Enviar Correo
            </button>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js/dist/face-api.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    <script>
        let videoStream;
        let faceDetectionInterval;

        // 1. Cargar modelos con spinner
        async function loadModels() {
            try {
                $('#modelLoading').show();
                
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('/models')
                ]);
                
                $('#modelLoading').hide();
                Swal.fire('Modelos cargados', 'La IA está lista para reconocer rostros', 'success');
                startFaceDetection();
                
            } catch (error) {
                $('#modelLoading').hide();
                Swal.fire('Error', `Error cargando modelos: ${error.message}`, 'error');
            }
        }

        // 2. Detección en tiempo real con feedback visual
        async function startFaceDetection() {
            const video = document.createElement('video');
            $('#cameraPreview').prepend(video);
            
            try {
                videoStream = await navigator.mediaDevices.getUserMedia({ video: {} });
                video.srcObject = videoStream;
                await video.play();

                // Dibujar detecciones en tiempo real
                faceDetectionInterval = setInterval(async () => {
                    const detections = await faceapi.detectAllFaces(
                        video, 
                        new faceapi.TinyFaceDetectorOptions()
                    ).withFaceLandmarks();
                    
                    // Limpiar canvas anterior
                    const canvas = faceapi.createCanvasFromMedia(video);
                    $('#cameraPreview canvas').remove();
                    $('#cameraPreview').append(canvas);
                    
                    // Ajustar tamaño
                    const displaySize = { width: video.width, height: video.height };
                    faceapi.matchDimensions(canvas, displaySize);
                    
                    // Dibujar resultados
                    const resizedDetections = faceapi.resizeResults(detections, displaySize);
                    faceapi.draw.drawDetections(canvas, resizedDetections);
                    faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);
                    
                }, 100);

            } catch (error) {
                Swal.fire('Error', `Error de cámara: ${error.message}`, 'error');
            }
        }

        // 3. Capturar rostro
        $('#captureButton').click(async () => {
            try {
                const video = $('#cameraPreview video')[0];
                const detections = await faceapi.detectAllFaces(
                    video, 
                    new faceapi.TinyFaceDetectorOptions()
                ).withFaceDescriptors();

                if (detections.length > 0) {
                    $('#captureButton').html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
                    
                    // Obtener descriptor facial
                    const faceData = detections[0].descriptor;
                    $('#faceData').val(JSON.stringify(faceData));
                    
                    // Mostrar formulario
                    $('#cameraSection').hide();
                    $('#sendEmailForm').show();
                    $('#submitButton').show();
                    
                    // Mostrar datos en el mensaje
                    $('#body').val(`Datos faciales reconocidos:\n${JSON.stringify(faceData, null, 2)}`);
                    
                    Swal.fire('¡Éxito!', 'Rostro reconocido correctamente', 'success');
                    
                } else {
                    Swal.fire('Error', 'No se detectó ningún rostro', 'error');
                }
                
            } catch (error) {
                Swal.fire('Error', `Error en reconocimiento: ${error.message}`, 'error');
            } finally {
                $('#captureButton').html('<i class="fas fa-camera"></i> Capturar Rostro');
            }
        });

        // 4. Detener cámara al enviar formulario
        $('#sendEmailForm').submit(() => {
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
            }
            clearInterval(faceDetectionInterval);
        });

        // Inicializar
        $(document).ready(() => {
            loadModels();
            $('#modelLoading').hide(); // Ocultar spinner inicialmente
        });
    </script>
</body>
</html>