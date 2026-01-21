<?php
/*
 * Plugin Name: AI Multi-language Translation
 * Description: Multilanguage translation plugin powered by AI., supported by elemetor and.
 * Plugin URI: https://Workinnovate.com/
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Workinnovate
 * Author URI: https://Workinnovate.com/
 * License: GPLv2 or later
 * Text-domain: ai-multilanguage-translation
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . 'includes/class-aimt-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-aimt-activator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-aimt-translations.php';
add_action('init', 'register_test_post_types');

class MultilanguagePlugin
{
    public function activate()
    {
        AIMT_Activator::activate();
    }

    // public function deactivate() {
    // }

    // public static function uninstall() {
    // }
}


if (class_exists('MultilanguagePlugin')) {
    $multilanguagePlugin = new MultilanguagePlugin();
}


// these post types are only for testing purpose
function register_test_post_types()
{
  
    register_post_type('books', array(
        'label'        => 'Books',
        'public'       => true,
        'show_in_menu' => true,
        'supports'     => array('title', 'editor', 'thumbnail'),
        'has_archive'  => true,
    ));
    // Shoes
    register_post_type('shoes', array(
        'label'        => 'Shoes',
        'public'       => true,
        'show_in_menu' => true,
        'supports'     => array('title', 'editor', 'thumbnail'),
        'has_archive'  => true,
    ));
    // Clothes
    register_post_type('clothes', array(
        'label'        => 'Clothes',
        'public'       => true,
        'show_in_menu' => true,
        'supports'     => array('title', 'editor', 'thumbnail'),
        'has_archive'  => true,
    ));
    register_post_type('Test1', array(
        'label'        => 'Start Test',
        'public'       => true,
        'show_in_menu' => true,
        'supports'     => array('title', 'editor', 'thumbnail'),
        'has_archive'  => true,
    ));
}



// Activation
register_activation_hook(__FILE__, array($multilanguagePlugin, 'activate'));

add_action('admin_init', function () {

    if (get_option('aimt_show_onboarding')) {
        delete_option('aimt_show_onboarding');
        wp_safe_redirect(admin_url('admin.php?page=aimt-configrations'));
        exit;
    }
});

if (is_admin()) {
    new AIMT_Admin();
}

new AIMT_Translations();

// Deactivation
// register_deactivation_hook(__FILE__, array($multilanguagePlugin, 'deactivate'));

// Uninstall
// register_uninstall_hook(__FILE__, array('MultilanguagePlugin', 'uninstall'));
