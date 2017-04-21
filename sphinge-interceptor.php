<?php
/*
Plugin Name: Sphinge Interceptor
Plugin URI: https://sphinge.io/extensions/interceptor
Description: Intercepts errors and sends them to your Sphinge dashboard.
Version: 1.0.0-beta
Author: Sphinge
Author URI: https://sphinge.io
*/

// get sphinge config file
$config_file_exists = include ABSPATH.'sphinge'.DIRECTORY_SEPARATOR.'config.php';

if (!$config_file_exists) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><b>Sphinge Interceptor &mdash; </b>'.__('You must install Sphinge for WordPress first and provide your dashboard URL.', 'sphinge').'</div>';
    });
} elseif (!defined('SPHINGE_URL')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><b>Sphinge Interceptor &mdash; </b>'.__('You must provide your dashboard URL.', 'sphinge').'</div>';
    });
} else {
    /**
     * Everything's fine, let's go and set error handlers
     */
    set_error_handler('intercept_error');
    register_shutdown_function('intercept_fatal_error');
    set_exception_handler('intercept_uncaught_exception');
}

/**
 * Intercepts errors and sends them
 *
 * @param  [type] $errno   [description]
 * @param  [type] $errstr  [description]
 * @param  [type] $errfile [description]
 * @param  [type] $errline [description]
 *
 * @return void
 */
function intercept_error($errno, $errstr, $errfile, $errline) {
    sendError([
        'website_secret_key' => SPHINGE_KEY,
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
}

/**
 * Intercepts fatal errors that are not managed by intercept_error and sends them
 *
 * @return void
 */
function intercept_fatal_error() {
    $lastError = error_get_last();

    if (in_array($lastError['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        sendError([
            'website_secret_key' => SPHINGE_KEY,
            'type' => $lastError['type'],
            'message' => $lastError['message'],
            'file' => $lastError['file'],
            'line' => $lastError['line']
        ]);
    }
}

/**
 * Intercepts uncaught exceptions and sends them
 *
 * @param  Exception $exception Intercepted exception
 *
 * @return void
 */
function intercept_uncaught_exception($exception) {
    sendError([
        'website_secret_key' => SPHINGE_KEY,
        'type' => 'Uncaught Exception',
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ]);
}

/**
 * Sends an error to Sphinge
 *
 * @param  array  $error The error that will be sent
 *
 * @return void
 */
function sendError(array $error) {
    $response = wp_remote_post(SPHINGE_URL.'/api/intercept', ['body' => json_encode($error)]);
}
