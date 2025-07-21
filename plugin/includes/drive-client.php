<?php
if (!defined('ABSPATH')) exit;

class WP_GDrive_Client {
    const OPTION_ACCESS_TOKEN = 'wp_gdrive_uploader_token';

    public static function get_client() {
        $client = new Google_Client();
        $client->setApplicationName('WP GDrive Uploader');
        $client->setScopes(Google_Service_Drive::DRIVE);
        $client->setAuthConfig(WP_GDRIVE_PLUGIN_PATH . 'client_secret.json');
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $token = get_option(self::OPTION_ACCESS_TOKEN);
        if ($token) $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                update_option(self::OPTION_ACCESS_TOKEN, $client->getAccessToken());
            } else {
                return null;
            }
        }

        return $client;
    }

    public static function get_auth_url() {
        $client = new Google_Client();
        $client->setAuthConfig(WP_GDRIVE_PLUGIN_PATH . 'client_secret.json');
        $client->setRedirectUri(admin_url('admin-post.php?action=wp_gdrive_auth'));
        $client->addScope(Google_Service_Drive::DRIVE);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        return $client->createAuthUrl();
    }

    public static function handle_auth_callback() {
        if (!isset($_GET['code'])) wp_die('Missing auth code');
        $client = new Google_Client();
        $client->setAuthConfig(WP_GDRIVE_PLUGIN_PATH . 'client_secret.json');
        $client->setRedirectUri(admin_url('admin-post.php?action=wp_gdrive_auth'));
        $client->addScope(Google_Service_Drive::DRIVE);
        $client->setAccessType('offline');
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) wp_die('Auth failed: ' . esc_html($token['error_description']));
        update_option(self::OPTION_ACCESS_TOKEN, $token);
        wp_redirect(admin_url('options-general.php?page=wp-gdrive-uploader'));
        exit;
    }
}