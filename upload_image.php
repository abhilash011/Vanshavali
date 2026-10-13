<?php
header('Content-Type: application/json');

// Set upload directory
$uploadDir = __DIR__ . '/images/';

// Create directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle the upload
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $tmpName = $_FILES['image']['tmp_name'];
    $imageInfo = getimagesize($tmpName);
    
    // Check if file is actually an image
    if ($imageInfo === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Uploaded file is not a valid image'
        ]);
        exit;
    }
    
    // Determine ID to use for filename
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = $_POST['id'];
    } else {
        // Use temporary ID if we don't have one yet
        $id = 'temp';
    }
    
    // Create filename with extension
    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    if (empty($extension)) {
        // Get extension from mime type if original doesn't have one
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp'
        ];
        $extension = $mimeToExt[$imageInfo['mime']] ?? 'jpg';
    }
    
    $filename = $id . '.' . $extension;
    $filePath = $uploadDir . $filename;
    
    // Save file
    if (move_uploaded_file($tmpName, $filePath)) {
        // Return success with path relative to website root
        echo json_encode([
            'success' => true,
            'path' => './images/' . $filename,
            'id' => $id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save uploaded file'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No file uploaded or error occurred'
    ]);
}
?>