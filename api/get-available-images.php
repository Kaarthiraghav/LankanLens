<?php
/**
 * API: Get Available Images
 * Returns list of image files available in /assets/images/
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth_helper.php';

requireAdmin();

header('Content-Type: application/json');

$images_dir = __DIR__ . '/../assets/images';
$available_images = [];

function scanImagesRecursive($dir, $base_path = '/assets/images') {
    $images = [];
    if (!is_dir($dir)) {
        return $images;
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $full_path = $dir . DIRECTORY_SEPARATOR . $item;
        $relative_path = $base_path . '/' . $item;

        if (is_dir($full_path)) {
            $images = array_merge($images, scanImagesRecursive($full_path, $relative_path));
        } else {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $images[] = [
                    'path' => str_replace('\\', '/', $relative_path),
                    'filename' => $item,
                    'size' => filesize($full_path)
                ];
            }
        }
    }

    return $images;
}

if (!is_dir($images_dir)) {
    echo json_encode(['success' => false, 'error' => 'Images directory not found']);
    exit;
}

$available_images = scanImagesRecursive($images_dir);
usort($available_images, function($a, $b) {
    return strcmp($a['path'], $b['path']);
});

echo json_encode([
    'success' => true,
    'count' => count($available_images),
    'images' => $available_images
]);
?>
