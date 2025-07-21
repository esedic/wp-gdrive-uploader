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
