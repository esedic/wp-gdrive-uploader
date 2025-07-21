<?php
if (!defined('ABSPATH')) exit;

class WP_GDrive_Uploader {
    public static function upload_files() {
        $folder = get_option(WP_GDrive_FileUploader::OPTION_FOLDER);
        $allowed = array_map('trim', explode(',', get_option(WP_GDrive_FileUploader::OPTION_TYPES)));
        $drive_folder_id = get_option(WP_GDrive_FileUploader::OPTION_DRIVE_FOLDER);

        if (!is_dir($folder)) return "Invalid folder path.";

        $client = WP_GDrive_Client::get_client();
        if (!$client) return "Not connected to Google Drive.";

        $service = new Google_Service_Drive($client);
        $files = scandir($folder);
        $uploaded = 0;

        foreach ($files as $file) {
            $path = $folder . DIRECTORY_SEPARATOR . $file;
            if (!is_file($path)) continue;
            $mime = mime_content_type($path);
            if (!in_array($mime, $allowed)) continue;

            $gfile = new Google_Service_Drive_DriveFile();
            $gfile->setName($file);
            if ($drive_folder_id) $gfile->setParents([$drive_folder_id]);

            $service->files->create($gfile, [
                'data' => file_get_contents($path),
                'mimeType' => $mime,
                'uploadType' => 'multipart',
            ]);
            $uploaded++;
        }

        return "$uploaded file(s) uploaded.";
    }
}
