<?php

declare(strict_types=1);

const UPLOAD_PASSWORD_HASH = '$2y$12$KwAVHe1/.izjfUo/0JM8e.2mHUNVxfkzGezuFeFvQl5RrfGP6csoG';
const UPLOAD_DIR = __DIR__ . '/../uploads/';
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx', 'zip'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Passwort prüfen
    $pwd = $_POST['password'] ?? '';
    if (!password_verify($pwd, UPLOAD_PASSWORD_HASH)) {
        $error = 'Falsches Passwort.';
    } else {
        // 2. Upload-Fehler abfangen
        $fileError = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($fileError !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'Datei überschreitet das Server-Limit.',
                UPLOAD_ERR_FORM_SIZE => 'Datei überschreitet das Formular-Limit.',
                UPLOAD_ERR_PARTIAL => 'Upload wurde unterbrochen.',
                UPLOAD_ERR_NO_FILE => 'Keine Datei ausgewählt.',
                UPLOAD_ERR_NO_TMP_DIR => 'Temporäres Verzeichnis fehlt.',
                UPLOAD_ERR_CANT_WRITE => 'Schreibfehler auf dem Server.',
                UPLOAD_ERR_EXTENSION => 'Upload durch PHP-Erweiterung blockiert.'
            ];
            $error = $messages[$fileError] ?? 'Unbekannter Upload-Fehler.';
        } else {
            $file = $_FILES['file'];

            // 3. Größe prüfen
            if ($file['size'] > MAX_FILE_SIZE) {
                $error = 'Datei ist zu groß (max. 10 MB).';
            } elseif (!is_uploaded_file($file['tmp_name'])) {
                $error = 'Ungültige Datei.';
            } else {
                // 4. Extension prüfen
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
                    $error = 'Nur Bilder (JPG, PNG, GIF, WebP) und Textdateien (TXT, CSV, MD, JSON, XML) sind erlaubt.';
                } else {
                    // 5. MIME-Type prüfen (Schutz vor getarnten Dateien!)
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $isImage = str_starts_with($mime, 'image/');
                    $isText  = in_array($mime, ['text/plain', 'text/csv', 'application/json', 'application/xml', 'text/xml', 'text/markdown'], true);

                    if (!$isImage && !$isText) {
                        $error = 'Ungültiger Dateiinhalt. Bitte nur echte Bilder oder Textdateien hochladen.';
                    } else {
                        // Verzeichnis erstellen, falls nicht vorhanden
                        if (!is_dir(UPLOAD_DIR)) {
                            mkdir(UPLOAD_DIR, 0750, true);
                        }

                        // Sicheren, nicht erratbaren Dateinamen generieren
                        $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
                        $targetPath = UPLOAD_DIR . $safeName;

                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            $success = 'Datei erfolgreich hochgeladen: ' . htmlspecialchars($file['name']);
                        } else {
                            $error = 'Fehler beim Speichern der Datei.';
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geschützter Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-6 col-lg-5">
            <h1 class="h3">Beiträge zum Sommerfest</h1>
            <p class="lead">Beitrag einreichen</p>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="password" class="form-label">Passwort</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="file" class="form-label">Datei</label>
                            <input type="file" class="form-control" id="file" name="file"
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.txt,.csv,.md,.json,.xml" required>
                            <div class="form-text">Erlaubt: Bilder & Textdateien (max. 10 MB)</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Hochladen</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row justify-content-center mt-3">
        <div class="col-6 col-lg-5">
            <p class="text-muted">Mit Klick auf <strong>Hochladen</strong> erklärst du, dass du alle Rechte an dem Material hast und dieses im Rahmen des Sommerfests am Treptow-Kolleg Berlin gezeigt werden darf.</p>
            <p>Weitere Informationen zum Sommerfest findet im <a href="https://www.alumni-portal.org/veranstaltungen/details/abschluss-sommerfest-des-treptow-kollegs" target="_blank">Alumni-Portal</a>.</p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>