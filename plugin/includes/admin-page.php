<?php
if (!defined('ABSPATH')) exit;

class WP_GDrive_FileUploader {
    const OPTION_FOLDER = 'wp_gdrive_uploader_folder';
    const OPTION_TYPES = 'wp_gdrive_uploader_mimetypes';
    const OPTION_DRIVE_FOLDER = 'wp_gdrive_uploader_drive_folder';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_wp_gdrive_upload_manual', [$this, 'handle_manual_upload']);
        add_action('admin_post_wp_gdrive_auth', ['WP_GDrive_Client', 'handle_auth_callback']);
    }

    public function add_admin_menu() {
        add_options_page('Google Drive Uploader', 'Drive Uploader', 'manage_options', 'wp-gdrive-uploader', [$this, 'admin_page']);
    }

    public function register_settings() {
        register_setting('wp_gdrive_settings', self::OPTION_FOLDER);
        register_setting('wp_gdrive_settings', self::OPTION_TYPES);
        register_setting('wp_gdrive_settings', self::OPTION_DRIVE_FOLDER);
    }

    public function admin_page() {
        $auth_url = WP_GDrive_Client::get_auth_url();
        $token = get_option(WP_GDrive_Client::OPTION_ACCESS_TOKEN);
        $is_connected = $token && !empty($token['access_token']);
        ?>
        <div class="wrap">
            <h1>Google Drive Uploader</h1>
            <?php if ($is_connected): ?>
                <p><strong>Status:</strong> Connected to Google Drive ✅</p>
            <?php else: ?>
                <p><a href="<?php echo esc_url($auth_url); ?>" class="button button-primary">Connect Google Drive</a></p>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('wp_gdrive_settings'); ?>
                <table class="form-table">
                    <tr><th scope="row">Server Folder</th><td><input type="text" name="<?php echo self::OPTION_FOLDER; ?>" value="<?php echo esc_attr(get_option(self::OPTION_FOLDER)); ?>" class="regular-text"></td></tr>
                    <tr><th scope="row">Allowed File Types</th><td><input type="text" name="<?php echo self::OPTION_TYPES; ?>" value="<?php echo esc_attr(get_option(self::OPTION_TYPES)); ?>" class="regular-text"></td></tr>
                    <tr><th scope="row">Drive Folder ID</th><td><input type="text" name="<?php echo self::OPTION_DRIVE_FOLDER; ?>" value="<?php echo esc_attr(get_option(self::OPTION_DRIVE_FOLDER)); ?>" class="regular-text"></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function handle_manual_upload() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $result = WP_GDrive_Uploader::upload_files();
        wp_redirect(admin_url('admin.php?page=wp-gdrive-uploader&upload_result=' . urlencode($result)));
        exit;
    }
}
