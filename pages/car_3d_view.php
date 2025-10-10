<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}

// Check if vehicle ID is set
if (!isset($_GET['vehicle_id']) || empty($_GET['vehicle_id']) || !is_numeric($_GET['vehicle_id'])) {
    header("Location: car_menu.php");
    exit;
}

$vehicle_id = (int)$_GET['vehicle_id'];

// Fetch vehicle data from database
try {
    $stmt_vehicle = $connect->prepare("SELECT * FROM vehicles WHERE id = ? AND availability_status = 'available'");
    $stmt_vehicle->execute([$vehicle_id]);
    $vehicle = $stmt_vehicle->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        header("Location: car_menu.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: car_menu.php");
    exit;
}

// Fetch user details for header
$stmt = $connect->prepare("SELECT * FROM accounts WHERE Id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$displayName = !empty($user['FirstName']) ? $user['FirstName'] : $user['Username'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D View - <?php echo htmlspecialchars($vehicle['model_name']); ?> - Mitsubishi Motors</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Model Viewer -->
    <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/4.0.0/model-viewer.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 25%, #2d1b1b 50%, #8b0000 75%, #b80000 100%);
            min-height: 100vh;
            color: white;
            overflow-x: hidden;
        }

        .header {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            position: relative;
            z-index: 100;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            width: 60px;
            height: auto;
            filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.3));
        }

        .brand-text {
            font-size: 1.4rem;
            font-weight: 700;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #b80000;
            font-size: 1.2rem;
        }

        .welcome-text {
            font-size: 1rem;
            font-weight: 500;
        }

        .logout-btn {
            background: linear-gradient(45deg, #d60000, #b30000);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(214, 0, 0, 0.3);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(214, 0, 0, 0.5);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 5;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.1);
            color: #ffd700;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .back-btn:hover {
            background: #ffd700;
            color: #1a1a1a;
        }

        .viewer-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            overflow: hidden;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .card-header {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.05));
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .vehicle-info h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: #ffd700;
            margin-bottom: 5px;
        }

        .vehicle-info p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .view-toggle {
            display: flex;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 25px;
            padding: 4px;
            gap: 4px;
        }

        .toggle-btn {
            background: transparent;
            color: rgba(255, 255, 255, 0.7);
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .toggle-btn.active {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
        }

        .viewer-container {
            position: relative;
            height: 70vh;
            background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 12px;
        }

        model-viewer {
            width: 100%;
            height: 100%;
            background-color: transparent;
            --poster-color: transparent;
        }

        .fallback-360-viewer {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .fallback-image {
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
            filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.5));
            transition: all 0.3s ease;
            background: transparent;
        }

        .image-carousel {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .carousel-image {
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
            filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.5));
            transition: all 0.3s ease;
        }

        .carousel-controls {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: #ffd700;
            border: none;
            padding: 15px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .carousel-controls:hover {
            background: #ffd700;
            color: #1a1a1a;
        }

        .carousel-prev {
            left: 20px;
        }

        .carousel-next {
            right: 20px;
        }

        .controls-panel {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 50px;
            padding: 15px 25px;
            display: flex;
            gap: 15px;
            align-items: center;
            border: 1px solid rgba(255, 215, 0, 0.2);
            z-index: 20;
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .control-btn,
        .rotation-btn {
            background: rgba(255, 255, 255, 0.1);
            color: #ffd700;
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 8px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .control-btn:hover,
        .rotation-btn:hover {
            background: #ffd700;
            color: #1a1a1a;
        }

        .rotation-btn {
            padding: 10px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-panel {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 20px;
            max-width: 300px;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .info-panel h3 {
            color: #ffd700;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .info-panel p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
            padding: 3px 0;
            border-left: 2px solid #ffd700;
            padding-left: 10px;
            margin-bottom: 5px;
        }

        .loading-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 215, 0, 0.3);
            border-top: 3px solid #ffd700;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive Design */
        @media (max-width: 575px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .user-section {
                flex-direction: column;
                gap: 10px;
            }

            .container {
                padding: 15px;
            }

            .card-header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .vehicle-info h1 {
                font-size: 1.3rem;
            }

            .viewer-container {
                height: 50vh;
            }

            .controls-panel {
                flex-direction: column;
                gap: 10px;
                padding: 15px;
                border-radius: 20px;
            }

            .control-group {
                justify-content: center;
            }

            .info-panel {
                position: static;
                margin-top: 20px;
                max-width: none;
            }
        }

        @media (min-width: 576px) and (max-width: 767px) {
            .card-header {
                flex-direction: column;
                align-items: center;
            }

            .viewer-container {
                height: 60vh;
            }

            .info-panel {
                top: 15px;
                right: 15px;
                max-width: 250px;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .viewer-container {
                height: 65vh;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="logo-section">
            <img src="../includes/images/Mitsubishi_logo.png" alt="Mitsubishi Logo" class="logo">
            <div class="brand-text">MITSUBISHI MOTORS</div>
        </div>
        <div class="user-section">
            <div class="user-avatar"><?php echo strtoupper(substr($displayName, 0, 1)); ?></div>
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($displayName); ?>!</span>
            <button class="logout-btn" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </header>

    <div class="container">
        <a href="car_details.php?id=<?php echo $vehicle_id; ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Details
        </a>

        <div class="viewer-card">
            <div class="card-header">
                <div class="vehicle-info">
                    <h1><?php echo htmlspecialchars($vehicle['model_name']); ?> 3D View</h1>
                    <p>Interactive 360° Vehicle Viewing Experience</p>
                </div>
                <div class="view-toggle">
                    <button class="toggle-btn active" data-view="exterior">
                        <i class="fas fa-car"></i> Exterior
                    </button>
                    <button class="toggle-btn" data-view="interior">
                        <i class="fas fa-chair"></i> Interior
                    </button>
                </div>
            </div>

            <div class="viewer-container">
                <div class="loading-screen" id="loadingScreen">
                    <div class="spinner"></div>
                    <p>Loading 3D Model...</p>
                </div>

                <!-- Google Model Viewer for 3D models -->
                <model-viewer id="model-viewer" 
                    alt="<?php echo htmlspecialchars($vehicle['model_name']); ?> 3D Model"
                    src="" 
                    camera-controls 
                    touch-action="pan-y"
                    auto-rotate
                    shadow-intensity="1"
                    camera-orbit="0deg 75deg 3.75m"
                    style="display: none;">
                </model-viewer>

                <!-- Fallback 360 Image Carousel -->
                <div class="fallback-360-viewer" id="fallback-viewer">
                    <div class="image-carousel" id="image-carousel">
                        <img class="carousel-image" id="carousel-image" src="" alt="360° View" style="display: none;">
                        <button class="carousel-controls carousel-prev" id="prev-btn" onclick="previousImage()">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="carousel-controls carousel-next" id="next-btn" onclick="nextImage()">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="fallback-message" id="fallback-message" style="text-align: center; color: rgba(255,255,255,0.7);">
                        <i class="fas fa-cube" style="font-size: 3rem; margin-bottom: 20px; color: #ffd700;"></i>
                        <p>3D model not available for this vehicle.</p>
                        <p>Showing 360° images instead.</p>
                    </div>
                </div>

                <div class="controls-panel">
                    <div class="control-group">
                        <span style="color: #ffd700; font-size: 0.8rem;">Auto Rotate:</span>
                        <button class="control-btn" id="autoRotateBtn">
                            <i class="fas fa-sync"></i> <span>Start</span>
                        </button>
                    </div>

                    <div class="control-group">
                        <span style="color: #ffd700; font-size: 0.8rem;">Manual:</span>
                        <div class="rotation-controls">
                            <button class="rotation-btn" id="rotateLeft">
                                <i class="fas fa-undo"></i>
                            </button>
                            <button class="rotation-btn" id="rotateRight">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </div>

                    <div class="control-group">
                        <button class="control-btn" id="zoomIn">
                            <i class="fas fa-search-plus"></i> Zoom In
                        </button>
                        <button class="control-btn" id="zoomOut">
                            <i class="fas fa-search-minus"></i> Zoom Out
                        </button>
                        <button class="control-btn" id="resetView">
                            <i class="fas fa-sync"></i> Reset
                        </button>
                    </div>
                </div>

                <div class="info-panel">
                    <h3 id="viewTitle">Exterior View</h3>
                    <p id="viewDescription">Explore the exterior design and features of the <?php echo htmlspecialchars($vehicle['model_name']); ?>.</p>
                    <ul class="feature-list" id="featureList">
                        <li>LED Headlights</li>
                        <li>Alloy Wheels</li>
                        <li>Chrome Accents</li>
                        <li>Panoramic Sunroof</li>
                        <li>Rear Spoiler</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentImageIndex = 0;
        let images360 = [];
        let autoRotateInterval = null;
        let isAutoRotating = false;
        const vehicleId = <?php echo $vehicle_id; ?>;

        // Initialize the viewer on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeViewer();
        });

        async function initializeViewer() {
            try {
                // Use vehicle data from PHP instead of making API call
                const view360Data = <?php echo json_encode($vehicle['view_360_images'] ?? ''); ?>;
                
                if (view360Data) {
                    // Parse the view_360_images data (it might be JSON string or array)
                    let view360Files = [];
                    if (typeof view360Data === 'string') {
                        try {
                            view360Files = JSON.parse(view360Data);
                        } catch (e) {
                            // If it's not JSON, treat as single file path
                            view360Files = [view360Data];
                        }
                    } else if (Array.isArray(view360Data)) {
                        view360Files = view360Data;
                    } else {
                        view360Files = [view360Data];
                    }
                    
                    // Check if we have any 3D model files (GLB/GLTF)
                    const modelFiles = view360Files.filter(filePath => 
                        filePath && filePath.toLowerCase().endsWith('.glb') || filePath.toLowerCase().endsWith('.gltf')
                    );
                    
                    if (modelFiles.length > 0) {
                        // Load the first 3D model file
                        console.log('Loading 3D model:', modelFiles[0]);
                        await loadModelFromPath(modelFiles[0]);
                    } else {
                        // Check for image files
                        const imageFiles = view360Files.filter(filePath => 
                            filePath && filePath.toLowerCase().match(/\.(jpg|jpeg|png|gif|webp)$/)
                        );
                        
                        if (imageFiles.length > 0) {
                            await setup360ImageCarouselFromPaths(imageFiles);
                        } else {
                            showFallbackMessage();
                        }
                    }
                } else {
                    // No 360 data available, show fallback message
                    showFallbackMessage();
                }
            } catch (error) {
                console.error('Error initializing viewer:', error);
                showFallbackMessage();
            }
        }

        async function loadModelFromPath(modelPath) {
            try {
                // Convert absolute path to web path
                let webPath = modelPath.replace(/\\/g, '/').replace(/^.*\/htdocs\/Mitsubishi/, '');
                
                // Ensure the web path starts with a forward slash
                if (!webPath.startsWith('/')) {
                    webPath = '/' + webPath;
                }
                
                // Create full URL with correct port
                const baseUrl = window.location.protocol + '//' + window.location.hostname + ':8000';
                const fullUrl = baseUrl + webPath;
                
                console.log('Loading 3D model from path:', fullUrl);
                
                const modelViewer = document.getElementById('model-viewer');
                const fallbackViewer = document.getElementById('fallback-viewer');
                
                // Set the model source to the full URL
                modelViewer.src = fullUrl;
                modelViewer.style.display = 'block';
                fallbackViewer.style.display = 'none';
                
                // Hide loading screen
                document.getElementById('loadingScreen').style.display = 'none';
                
                // Setup model viewer controls
                setupModelViewerControls(modelViewer);
            } catch (error) {
                console.error('Error loading 3D model:', error);
                showFallbackMessage();
            }
        }
        
        function setupModelViewer(modelData) {
            const modelViewer = document.getElementById('model-viewer');
            const fallbackViewer = document.getElementById('fallback-viewer');
            
            // Create blob URL for the model
            let blob;
            if (modelData instanceof ArrayBuffer) {
                blob = new Blob([modelData], { type: 'model/gltf-binary' });
            } else {
                blob = new Blob([modelData], { type: 'model/gltf-binary' });
            }
            const modelUrl = URL.createObjectURL(blob);
            
            modelViewer.src = modelUrl;
            modelViewer.style.display = 'block';
            fallbackViewer.style.display = 'none';
            
            // Hide loading screen
            document.getElementById('loadingScreen').style.display = 'none';
            
            // Setup model viewer controls
            setupModelViewerControls(modelViewer);
        }

        async function setup360ImageCarouselFromPaths(imagePaths) {
            try {
                // Convert absolute paths to web paths with correct base URL
                const baseUrl = window.location.protocol + '//' + window.location.hostname + ':8000';
                images360 = imagePaths.map(path => {
                    let webPath = path.replace(/\\/g, '/').replace(/^.*\/htdocs\/Mitsubishi/, '');
                    if (!webPath.startsWith('/')) {
                        webPath = '/' + webPath;
                    }
                    return baseUrl + webPath;
                });
                
                if (images360.length > 0) {
                    showImageCarouselFromPaths();
                } else {
                    showFallbackMessage();
                }
            } catch (error) {
                console.error('Error setting up 360 image carousel:', error);
                showFallbackMessage();
            }
        }
        
        async function setup360ImageCarousel() {
            try {
                // Fetch 360 images from the database
                const response = await fetch(`get_360_images.php?vehicle_id=${vehicleId}`);
                const result = await response.json();
                
                if (result.success && result.images && result.images.length > 0) {
                    images360 = result.images;
                    showImageCarousel();
                } else {
                    showFallbackMessage();
                }
            } catch (error) {
                console.error('Error loading 360 images:', error);
                showFallbackMessage();
            }
        }

        function showImageCarouselFromPaths() {
            const modelViewer = document.getElementById('model-viewer');
            const fallbackViewer = document.getElementById('fallback-viewer');
            const carouselImage = document.getElementById('carousel-image');
            const fallbackMessage = document.getElementById('fallback-message');
            
            modelViewer.style.display = 'none';
            fallbackViewer.style.display = 'flex';
            fallbackMessage.style.display = 'none';
            
            if (images360.length > 0) {
                carouselImage.src = images360[0]; // Full URL with correct port
                carouselImage.style.display = 'block';
                
                // Show/hide navigation buttons
                document.getElementById('prev-btn').style.display = images360.length > 1 ? 'block' : 'none';
                document.getElementById('next-btn').style.display = images360.length > 1 ? 'block' : 'none';
            }
            
            // Hide loading screen
            document.getElementById('loadingScreen').style.display = 'none';
            
            // Setup carousel controls
            setupCarouselControls();
        }
        
        function showImageCarousel() {
            const modelViewer = document.getElementById('model-viewer');
            const fallbackViewer = document.getElementById('fallback-viewer');
            const carouselImage = document.getElementById('carousel-image');
            const fallbackMessage = document.getElementById('fallback-message');
            
            modelViewer.style.display = 'none';
            fallbackViewer.style.display = 'flex';
            fallbackMessage.style.display = 'none';
            
            if (images360.length > 0) {
                carouselImage.src = `data:image/jpeg;base64,${images360[0]}`;
                carouselImage.style.display = 'block';
                
                // Show/hide navigation buttons
                document.getElementById('prev-btn').style.display = images360.length > 1 ? 'block' : 'none';
                document.getElementById('next-btn').style.display = images360.length > 1 ? 'block' : 'none';
            }
            
            // Hide loading screen
            document.getElementById('loadingScreen').style.display = 'none';
            
            // Setup carousel controls
            setupCarouselControls();
        }

        function showFallbackMessage() {
            const modelViewer = document.getElementById('model-viewer');
            const fallbackViewer = document.getElementById('fallback-viewer');
            const carouselImage = document.getElementById('carousel-image');
            const fallbackMessage = document.getElementById('fallback-message');
            
            modelViewer.style.display = 'none';
            fallbackViewer.style.display = 'flex';
            carouselImage.style.display = 'none';
            fallbackMessage.style.display = 'block';
            
            // Hide navigation buttons
            document.getElementById('prev-btn').style.display = 'none';
            document.getElementById('next-btn').style.display = 'none';
            
            // Hide loading screen
            document.getElementById('loadingScreen').style.display = 'none';
        }

        function setupModelViewerControls(modelViewer) {
            // Auto-rotate control
            document.getElementById('autoRotateBtn').addEventListener('click', function() {
                isAutoRotating = !isAutoRotating;
                modelViewer.autoRotate = isAutoRotating;
                this.querySelector('span').textContent = isAutoRotating ? 'Stop' : 'Start';
            });

            // Manual rotation controls
            document.getElementById('rotateLeft').addEventListener('click', function() {
                const currentOrbit = modelViewer.getCameraOrbit();
                modelViewer.cameraOrbit = `${currentOrbit.theta - 0.5}rad ${currentOrbit.phi}rad ${currentOrbit.radius}m`;
            });

            document.getElementById('rotateRight').addEventListener('click', function() {
                const currentOrbit = modelViewer.getCameraOrbit();
                modelViewer.cameraOrbit = `${currentOrbit.theta + 0.5}rad ${currentOrbit.phi}rad ${currentOrbit.radius}m`;
            });

            // Reset view
            document.getElementById('resetView').addEventListener('click', function() {
                modelViewer.resetTurntableRotation();
                modelViewer.jumpCameraToGoal();
            });

            // Zoom controls (handled by model-viewer's camera-controls)
            document.getElementById('zoomIn').addEventListener('click', function() {
                const currentOrbit = modelViewer.getCameraOrbit();
                modelViewer.cameraOrbit = `${currentOrbit.theta}rad ${currentOrbit.phi}rad ${Math.max(currentOrbit.radius * 0.8, 2.5)}m`;
            });

            document.getElementById('zoomOut').addEventListener('click', function() {
                const currentOrbit = modelViewer.getCameraOrbit();
                modelViewer.cameraOrbit = `${currentOrbit.theta}rad ${currentOrbit.phi}rad ${currentOrbit.radius * 1.2}m`;
            });
        }

        function setupCarouselControls() {
            // Auto-rotate control for carousel
            document.getElementById('autoRotateBtn').addEventListener('click', function() {
                isAutoRotating = !isAutoRotating;
                if (isAutoRotating) {
                    startAutoRotateCarousel();
                    this.querySelector('span').textContent = 'Stop';
                } else {
                    stopAutoRotateCarousel();
                    this.querySelector('span').textContent = 'Start';
                }
            });

            // Manual rotation controls
            document.getElementById('rotateLeft').addEventListener('click', previousImage);
            document.getElementById('rotateRight').addEventListener('click', nextImage);

            // Reset view
            document.getElementById('resetView').addEventListener('click', function() {
                currentImageIndex = 0;
                updateCarouselImage();
            });

            // Zoom controls (not applicable for images, but we can show a message)
            document.getElementById('zoomIn').addEventListener('click', function() {
                // Could implement image zoom here if needed
                console.log('Zoom in - not implemented for image carousel');
            });

            document.getElementById('zoomOut').addEventListener('click', function() {
                // Could implement image zoom here if needed
                console.log('Zoom out - not implemented for image carousel');
            });
        }

        function previousImage() {
            if (images360.length > 1) {
                currentImageIndex = (currentImageIndex - 1 + images360.length) % images360.length;
                updateCarouselImage();
            }
        }

        function nextImage() {
            if (images360.length > 1) {
                currentImageIndex = (currentImageIndex + 1) % images360.length;
                updateCarouselImage();
            }
        }

        function updateCarouselImage() {
            const carouselImage = document.getElementById('carousel-image');
            if (images360.length > 0) {
                carouselImage.src = `data:image/jpeg;base64,${images360[currentImageIndex]}`;
            }
        }

        function startAutoRotateCarousel() {
            if (images360.length > 1) {
                autoRotateInterval = setInterval(nextImage, 2000); // Change image every 2 seconds
            }
        }

        function stopAutoRotateCarousel() {
            if (autoRotateInterval) {
                clearInterval(autoRotateInterval);
                autoRotateInterval = null;
            }
        }

        // View toggle functionality
        const viewBtns = document.querySelectorAll('.toggle-btn');
        viewBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;
                viewBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Update info panel
                const viewTitle = document.getElementById('viewTitle');
                const viewDescription = document.getElementById('viewDescription');
                const featureList = document.getElementById('featureList');

                if (view === 'exterior') {
                    viewTitle.textContent = 'Exterior View';
                    viewDescription.textContent = 'Explore the exterior design and features of the vehicle.';
                    featureList.innerHTML = `
                        <li><i class="fas fa-car"></i> Aerodynamic Design</li>
                        <li><i class="fas fa-lightbulb"></i> LED Headlights</li>
                        <li><i class="fas fa-shield-alt"></i> Safety Features</li>
                        <li><i class="fas fa-cog"></i> Alloy Wheels</li>
                    `;
                    
                    // Set exterior camera view for model viewer
                    const modelViewer = document.getElementById('model-viewer');
                    if (modelViewer && modelViewer.style.display !== 'none') {
                        modelViewer.cameraOrbit = '0deg 75deg 3.75m';
                    }
                } else if (view === 'interior') {
                    viewTitle.textContent = 'Interior View';
                    viewDescription.textContent = 'Discover the comfort and technology inside the vehicle.';
                    featureList.innerHTML = `
                        <li><i class="fas fa-chair"></i> Premium Seating</li>
                        <li><i class="fas fa-tv"></i> Infotainment System</li>
                        <li><i class="fas fa-snowflake"></i> Climate Control</li>
                        <li><i class="fas fa-volume-up"></i> Premium Audio</li>
                    `;
                    
                    // Set interior camera view for model viewer
                    const modelViewer = document.getElementById('model-viewer');
                    if (modelViewer && modelViewer.style.display !== 'none') {
                        modelViewer.cameraOrbit = '0deg 90deg 0.3m';
                    }
                }
            });
        });

    </script>


    </script>
</body>

</html>