<?php

class AIMT_Activator {

    public static function activate() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'aimt_translations';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            language_code VARCHAR(10) NOT NULL,
            translation_text LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_lang (post_id, language_code)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        add_option( 'aimt_default_languages', array( 'en', 'fr', 'de' ) );

       add_option('aimt_show_onboarding', 1);

    }
}
