<?php
$baseDir = __DIR__ . '/website';

if (!file_exists($baseDir)) {
    mkdir($baseDir, 0777, true);
}

function getFileList($dir, $baseDir) {
    $fileArray = array();
    foreach (glob($dir . '/*') as $file) {
        $relativePath = str_replace($baseDir . '/', '', $file);
        if (is_dir($file)) {
            $fileArray[] = ['path' => $relativePath, 'is_dir' => true, 'modified' => filemtime($file)];
            $fileArray = array_merge($fileArray, getFileList($file, $baseDir));
        } else {
            $fileArray[] = ['path' => $relativePath, 'is_dir' => false, 'modified' => filemtime($file)];
        }
    }
    return $fileArray;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['file'])) {
        uploadFile($baseDir);
    } elseif (!empty($_POST['dir'])) {
        createDirectory($baseDir);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'No file or directory specified']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    updateFile($baseDir, $data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['file'])) {
        downloadFile($baseDir);
    } else {
        $fileList = getFileList($baseDir, $baseDir);
        echo json_encode($fileList);
    }
    exit;
}

function uploadFile($baseDir) {
    $file = $_FILES['file'];
    $filePath = $baseDir . '/' . $_POST['path'] . '/' . basename($file['name']);

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        http_response_code(200);
        echo json_encode(['message' => 'File uploaded successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to upload file']);
    }
}

function createDirectory($baseDir) {
    $dirPath = $baseDir . '/' . $_POST['dir'];
    if (!file_exists($dirPath) && mkdir($dirPath, 0777, true)) {
        http_response_code(200);
        echo json_encode(['message' => 'Directory created successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to create directory']);
    }
}

function updateFile($baseDir, $data) {
    if (!empty($data['file_name']) && !empty($data['content'])) {
        $filePath = $baseDir . '/' . $data['file_name'];

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        if (file_put_contents($filePath, $data['content']) !== false) {
            http_response_code(200);
            echo json_encode(['message' => 'File updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to update file']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid parameters']);
    }
}

function downloadFile($baseDir) {
    $filePath = $baseDir . '/' . $_GET['file'];

    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($filePath));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'File not found']);
    }
}
?>
