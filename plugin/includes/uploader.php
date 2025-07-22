<?php
if (!defined('ABSPATH')) exit;

class WP_GDrive_Uploader {

    public static function upload_files() {
        $folder = get_option('wp_gdrive_uploader_folder');
        $allowed_types = array_map('trim', explode(',', get_option('wp_gdrive_uploader_mimetypes')));
        $drive_folder_id = get_option('wp_gdrive_uploader_drive_folder');

        if (!is_dir($folder)) return 'Invalid folder.';

        $client = WP_GDrive_Client::get_client();
        if (!$client) return 'Google Drive client not authenticated.';

        $service = new Google_Service_Drive($client);
        $files = scandir($folder);
        $count = 0;

        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) continue;

            $full_path = trailingslashit($folder) . $file;
            if (!is_file($full_path)) continue;

            $mime_type = mime_content_type($full_path);
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            if (!in_array($ext, $allowed_types)) {
                error_log("GDrive Uploader: Skipping file due to MIME type: $file ($mime_type)");
                continue;
            }

            // Search for a file with the same name in the target folder
            $query = sprintf(
                "name='%s' and '%s' in parents and trashed=false",
                addslashes($file),
                $drive_folder_id
            );
            $response = $service->files->listFiles([
                'q' => $query,
                'spaces' => 'drive',
                'fields' => 'files(id, modifiedTime)'
            ]);

            $existing = count($response->files) > 0 ? $response->files[0] : null;

            if ($existing) {
                $server_time = filemtime($full_path);
                $drive_time = strtotime($existing->getModifiedTime());

                if ($server_time <= $drive_time) {
                    error_log("GDrive Uploader: Skipping $file (not newer than Drive version)");
                    continue;
                }

                // Update existing file
                try {
                    $drive_file = new Google_Service_Drive_DriveFile();
                    $service->files->update($existing->getId(), $drive_file, [
                        'data' => file_get_contents($full_path),
                        'mimeType' => $mime_type,
                        'uploadType' => 'multipart'
                    ]);
                    $count++;
                    error_log("GDrive Uploader: Updated $file (newer than Drive version)");
                } catch (Exception $e) {
                    error_log("GDrive Uploader: Update failed for $file: " . $e->getMessage());
                }

            } else {
                // Upload new file
                try {
                    $drive_file = new Google_Service_Drive_DriveFile([
                        'name' => $file,
                        'parents' => [$drive_folder_id]
                    ]);

                    $service->files->create($drive_file, [
                        'data' => file_get_contents($full_path),
                        'mimeType' => $mime_type,
                        'uploadType' => 'multipart'
                    ]);
                    $count++;
                    error_log("GDrive Uploader: Uploaded new file $file");
                } catch (Exception $e) {
                    error_log("GDrive Uploader: Upload failed for $file: " . $e->getMessage());
                }
            }
        }

        return "$count file(s) uploaded or updated.";
    }

}