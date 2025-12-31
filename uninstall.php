<?php
// Security check
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

global $wpdb;

// Drop translations table
$table_name = $wpdb->prefix . 'aimt_translations';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Remove default languages option
delete_option('aimt_default_languages');
