<?php

class AIMT_Activator {

    public static function activate() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'aimt_string_translations';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            string LONGTEXT NOT NULL,
            lang VARCHAR(10) NOT NULL,
            translated_lang VARCHAR(10) NOT NULL,
            translated_string LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY lang_translated_lang_idx (lang, translated_lang)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    
        $example = array(
            'string' => 'The Only Firm Focused Solely On Implementation',
            'lang' => 'en',
            'translated_lang' => 'es',
            'translated_string' => 'La única empresa centrada exclusivamente en la implementación',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name ) {
            $wpdb->insert( $table_name, $example, array('%s','%s','%s','%s','%s','%s') );
        }

        set_transient('aimt_activate_redirect', true, 60);
    }
}
