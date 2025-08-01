<?php
// api/bands_endpoint.php - Handler for bands API endpoints

// Function to handle band API requests
function handleBandsRequest($db, $method, $pageId, $bandId = null, $data = null) {
    require_once 'bands.php';
    $controller = new BandsController($db);
    
    try {
        if ($bandId === null) {
            // Request for all bands on a page
            switch ($method) {
                case 'GET':
                    return $controller->getBands($pageId);
                case 'POST':
                    // Create a new band from form data
                    $bandData = isset($_POST['band_data']) ? json_decode($_POST['band_data'], true) : $data;
                    return $controller->createBand($pageId, $bandData);
                default:
                    http_response_code(405);
                    return ['status' => 'error', 'message' => 'Method not allowed'];
            }
        } else {
            // Request for a specific band
            switch ($method) {
                case 'GET':
                    return $controller->getBand($pageId, $bandId);
                case 'PUT':
                    // Update an existing band
                    $bandData = isset($_POST['band_data']) ? json_decode($_POST['band_data'], true) : $data;
                    return $controller->updateBand($pageId, $bandId, $bandData);
                case 'DELETE':
                    return $controller->deleteBand($pageId, $bandId);
                default:
                    http_response_code(405);
                    return ['status' => 'error', 'message' => 'Method not allowed'];
            }
        }
    } catch (Exception $e) {
        http_response_code(500);
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

// Handle image uploads for bands
function handleBandImageUploads($controller, $pageId, $bandId = null) {
    // Process product image if it exists
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $result = $controller->handleProductImageUpload($_FILES['product_image'], $pageId, $bandId);
        if (!$result) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Failed to upload product image'];
        }
    }
    
    // Process slide images if they exist
    if (isset($_FILES['slide_images'])) {
        $slideImages = $_FILES['slide_images'];
        foreach ($slideImages as $index => $slideImage) {
            if ($slideImage['error'] === UPLOAD_ERR_OK) {
                $result = $controller->handleSlideImageUpload($slideImage, $pageId, $bandId, $index);
                if (!$result) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => "Failed to upload slide image for index $index"];
                }
            }
        }
    }
    
    return ['status' => 'success'];
}

// Helper function to modify api/index.php to correctly handle bands API endpoints
function modifyApiIndex() {
    $indexPath = __DIR__ . '/index.php';
    $content = file_get_contents($indexPath);
    
    // Check if the bands API endpoint is already defined
    if (strpos($content, 'bands\/([a-zA-Z0-9_-]+)') !== false) {
        return; // Already added
    }
    
    // Add the bands endpoint routing
    $pattern = '/switch \(true\) \{/';
    $replacement = "switch (true) {\n        // Bands API\n        case (preg_match('/^bands\\/([a-zA-Z0-9_-]+)$/i', \$endpoint, \$matches) ? true : false):\n            require_once 'bands.php';\n            require_once 'bands_endpoint.php';\n            \$pageId = \$matches[1];\n            \$result = handleBandsRequest(\$db, \$method, \$pageId, null, \$data);\n            echo json_encode(\$result);\n            break;\n            \n        case (preg_match('/^bands\\/([a-zA-Z0-9_-]+)\\/([a-zA-Z0-9_-]+)$/i', \$endpoint, \$matches) ? true : false):\n            require_once 'bands.php';\n            require_once 'bands_endpoint.php';\n            \$pageId = \$matches[1];\n            \$bandId = \$matches[2];\n            \$result = handleBandsRequest(\$db, \$method, \$pageId, \$bandId, \$data);\n            echo json_encode(\$result);\n            break;\n            ";
    
    $modifiedContent = preg_replace($pattern, $replacement, $content);
    
    if ($modifiedContent !== $content) {
        file_put_contents($indexPath, $modifiedContent);
    }
}

// Don't run this automatically - it's meant to be called from the main api/index.php
// modifyApiIndex();
