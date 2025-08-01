<?php
// install_checker.php - Verify that all required files are in place

function checkInstallation() {
    $basePath = __DIR__;
    $results = [
        'status' => 'success',
        'message' => 'Installation looks good!',
        'files' => [],
        'suggestions' => [],
        'errors' => []
    ];
    
    // Define required files
    $requiredFiles = [
        'admin/band_editor.html',
        'admin/bands.html',
        'admin/css/admin-style.css',
        'admin/js/admin-common.js',
        'admin/js/band-editor.js',
        'admin/layout.html',
        'api/.htaccess',
        'api/bands.php',
        'api/bands_endpoint.php',
        'api/global_styles.php',
        'api/index.php',
        'api/layout.php',
        'api/orders.php',
        'api/products.php',
        'api/upload.php',
        'config.php',
        'db.php',
        '.htaccess'
    ];
    
    // Check if files exist
    foreach ($requiredFiles as $file) {
        $path = $basePath . '/' . $file;
        $results['files'][$file] = file_exists($path);
        
        if (!$results['files'][$file]) {
            $results['status'] = 'warning';
            $results['errors'][] = "Missing file: $file";
        }
    }
    
    // Check if API routing is properly set up
    $apiIndexPath = $basePath . '/api/index.php';
    if (file_exists($apiIndexPath)) {
        $apiIndexContent = file_get_contents($apiIndexPath);
        if (strpos($apiIndexContent, 'bands\/([a-zA-Z0-9_-]+)') === false) {
            $results['suggestions'][] = "The bands endpoint is not properly configured in api/index.php. Consider adding the appropriate routing.";
        }
    }
    
    // Check database connection
    if (file_exists($basePath . '/config.php') && file_exists($basePath . '/db.php')) {
        require_once $basePath . '/config.php';
        require_once $basePath . '/db.php';
        
        try {
            $db = Database::getInstance();
            $results['database'] = [
                'connected' => true,
                'message' => 'Successfully connected to database'
            ];
            
            // Check if required tables exist
            $tables = [
                'layout_config',
                'layout_bands',
                'products',
                'product_variations',
                'product_images',
                'orders',
                'order_items'
            ];
            
            $tablesExist = [];
            foreach ($tables as $table) {
                try {
                    $query = "SHOW TABLES LIKE '$table'";
                    $result = $db->select($query);
                    $tablesExist[$table] = !empty($result);
                    
                    if (!$tablesExist[$table]) {
                        $results['suggestions'][] = "Table '$table' does not exist in the database.";
                    }
                } catch (Exception $e) {
                    $tablesExist[$table] = false;
                    $results['errors'][] = "Error checking table '$table': " . $e->getMessage();
                }
            }
            
            $results['database']['tables'] = $tablesExist;
            
        } catch (Exception $e) {
            $results['database'] = [
                'connected' => false,
                'message' => 'Failed to connect to database: ' . $e->getMessage()
            ];
            $results['status'] = 'error';
            $results['errors'][] = 'Database connection failed: ' . $e->getMessage();
        }
    } else {
        $results['database'] = [
            'connected' => false,
            'message' => 'Missing config.php or db.php files'
        ];
    }
    
    // Check for writable directories
    $writableDirs = [
        'uploads',
        'uploads/images',
        'uploads/images/bands',
        'uploads/images/bands/slideshow',
        'uploads/images/bands/slideshow/slides',
        'uploads/images/bands/product'
    ];
    
    foreach ($writableDirs as $dir) {
        $path = $basePath . '/' . $dir;
        
        if (!file_exists($path)) {
            // Try to create directory
            if (!mkdir($path, 0755, true)) {
                $results['errors'][] = "Directory '$dir' does not exist and could not be created.";
            } else {
                $results['suggestions'][] = "Created directory '$dir'.";
            }
        }
        
        if (file_exists($path) && !is_writable($path)) {
            $results['errors'][] = "Directory '$dir' is not writable.";
        }
    }
    
    // Update overall status based on errors
    if (!empty($results['errors'])) {
        $results['status'] = 'error';
        $results['message'] = 'There are issues with the installation that need to be fixed.';
    } else if (!empty($results['suggestions'])) {
        $results['status'] = 'warning';
        $results['message'] = 'Installation is functional but there are some suggestions for improvement.';
    }
    
    return $results;
}

// Run the check
$results = checkInstallation();

// Output results
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
