<?php
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // 20MB
define('UPLOAD_PATH', __DIR__ . '/../uploads/notes/');
define('THUMB_PATH',  __DIR__ . '/../uploads/thumbnails/');

$ALLOWED_MIME_TYPES = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

function uploadNote($file) {
    global $ALLOWED_MIME_TYPES;

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error occurred.'];
    }

    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File exceeds 20MB limit.'];
    }

    // Check real MIME type
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file type. Only PDF, DOCX, JPG, PNG allowed.'];
    }

    // Generate unique filename
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uuid     = bin2hex(random_bytes(16));
    $filename = $uuid . '.' . $ext;
    $destPath = UPLOAD_PATH . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'error' => 'Failed to save file.'];
    }

    // Generate thumbnail if PDF
    $thumbnail = null;
    if ($mimeType === 'application/pdf') {
        $thumbnail = generatePDFThumbnail($destPath, $uuid);
    }

    return [
        'success'   => true,
        'filename'  => $filename,
        'mime'      => $mimeType,
        'size'      => $file['size'],
        'thumbnail' => $thumbnail
    ];
}

function generatePDFThumbnail($pdfPath, $uuid) {
    if (!extension_loaded('imagick')) {
        return null; // fallback: no thumbnail
    }
    try {
        $img = new Imagick();
        $img->setResolution(150, 150);
        $img->readImage($pdfPath . '[0]');
        $img->flattenImages();
        $img->setImageFormat('jpg');
        $img->thumbnailImage(300, 400, true);
        $thumbName = $uuid . '.jpg';
        $img->writeImage(THUMB_PATH . $thumbName);
        $img->destroy();
        return $thumbName;
    } catch (Exception $e) {
        return null;
    }
}
?>