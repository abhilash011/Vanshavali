<?php
header('Content-Type: application/json');

// Set images directory
$imagesDir = __DIR__ . '/images/';

if (isset($_POST['old_id']) && isset($_POST['new_id'])) {
    $oldId = $_POST['old_id'];
    $newId = $_POST['new_id'];
    
    // Find file with old ID (could be any image extension)
    $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    $oldFile = null;
    $extension = null;
    
    foreach ($extensions as $ext) {
        if (file_exists($imagesDir . $oldId . '.' . $ext)) {
            $oldFile = $imagesDir . $oldId . '.' . $ext;
            $extension = $ext;
            break;
        }
    }
    
    if ($oldFile && $extension) {
        $newFile = $imagesDir . $newId . '.' . $extension;
        
        if (rename($oldFile, $newFile)) {
            echo json_encode([
                'success' => true,
                'path' => '/images/' . $newId . '.' . $extension
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to rename file'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Original file not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
}
?>