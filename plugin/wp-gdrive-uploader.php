<?php
/*
Plugin Name: WP Google Drive Uploader
Description: Uploads files from a server folder to a specified Google Drive folder.
Version: 1.0
Author: Elvis Sedić
 */

if (!defined('ABSPATH')) exit;

define('WP_GDRIVE_PLUGIN_PATH', plugin_dir_path(__FILE__));

require_once WP_GDRIVE_PLUGIN_PATH . 'vendor/autoload.php';

require_once WP_GDRIVE_PLUGIN_PATH . 'includes/drive-client.php';
require_once WP_GDRIVE_PLUGIN_PATH . 'includes/admin-page.php';
require_once WP_GDRIVE_PLUGIN_PATH . 'includes/uploader.php';

new WP_GDrive_FileUploader();


add_filter('cron_schedules', function($schedules) {
    $schedules['every_5_minutes'] = [
        'interval' => 300,
        'display'  => __('Every 3 Minutes')
    ];
    return $schedules;
});


register_activation_hook(__FILE__, function() {
    if (!wp_next_scheduled('wp_gdrive_upload_cron')) {
        wp_schedule_event(time(), 'every_5_minutes', 'wp_gdrive_upload_cron');
    }
});

register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('wp_gdrive_upload_cron');
});

add_action('wp_gdrive_upload_cron', function() {
    WP_GDrive_Uploader::upload_files();
});
