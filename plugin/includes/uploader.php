<?php
if (!defined('ABSPATH')) exit;

class WP_GDrive_Uploader {
    public static function upload_files() {
        $folder = get_option(WP_GDrive_FileUploader::OPTION_FOLDER);
        $types = explode(',', get_option(WP_GDrive_FileUploader::OPTION_TYPES));
        $drive_folder_id = get_option(WP_GDrive_FileUploader::OPTION_DRIVE_FOLDER);

        if (!is_dir($folder)) {
            error_log("GDrive Uploader: Server folder not found: $folder");
            return 'Server folder not found.';
        }

        $client = WP_GDrive_Client::get_client();
        if (!$client) {
            error_log("GDrive Uploader: Google client not authenticated.");
            return 'Google client not authenticated.';
        }

        $service = new Google_Service_Drive($client);
        $uploaded = 0;

        foreach (scandir($folder) as $file) {
            if (in_array($file, ['.', '..'])) continue;
            $path = $folder . '/' . $file;
            $mime = mime_content_type($path);
            if (!in_array($mime, $types)) {
                error_log("GDrive Uploader: Skipping file due to MIME type: $file ($mime)");
                continue;
            }

            try {
                $gfile = new Google_Service_Drive_DriveFile([
                    'name' => $file,
                    'parents' => [$drive_folder_id]
                ]);
                $data = file_get_contents($path);
                $service->files->create($gfile, [
                    'data' => $data,
                    'uploadType' => 'multipart',
                    'fields' => 'id'
                ]);
                $uploaded++;
                error_log("GDrive Uploader: Uploaded $file");
            } catch (Exception $e) {
                error_log("GDrive Uploader: Failed to upload $file – " . $e->getMessage());
            }
        }

        return "$uploaded file(s) uploaded.";
    }
}