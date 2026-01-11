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

        self::normalize_column_names( $table_name, $wpdb );

        add_option('aimt_default_languages', array('en'), '', 'yes'); 
        $default_translation_languages = array('es', 'fr', 'de'); 
        add_option('aimt_translation_languages', $default_translation_languages, '', 'yes');

        add_option('aimt_api_key', '', '', 'yes');
        add_option('aimt_translation_mode', 'manual', '', 'yes');
        add_option('aimt_auto_sync', 0, '', 'yes');
        add_option('aimt_show_onboarding', 1, '', 'yes');
        add_option('aimt_last_sync', '', '', 'yes');
    
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

    private static function normalize_column_names( $table_name, $wpdb ) {
        if ( get_option('aimt_column_case_migrated') ) {
            return;
        }

        $exists = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $table_name) );
        if ( $exists !== $table_name ) {
            return;
        }

        $columns = $wpdb->get_col( "SHOW COLUMNS FROM `{$table_name}`" );
        if ( empty( $columns ) ) {
            return;
        }

        $map = array(
            'Id'               => "CHANGE `Id` `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT",
            'String'           => "CHANGE `String` `string` LONGTEXT NOT NULL",
            'lang'             => false, 
            'translated_Lang'  => "CHANGE `translated_Lang` `translated_lang` VARCHAR(10) NOT NULL",
            'Translated_string'=> "CHANGE `Translated_string` `translated_string` LONGTEXT NOT NULL",
            'Created_at'       => "CHANGE `Created_at` `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP",
            'Updated_at'       => "CHANGE `Updated_at` `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        );

        $sqls = array();
        foreach ( $map as $old => $change_sql ) {
            if ( $change_sql && in_array( $old, $columns, true ) ) {
                $sqls[] = "ALTER TABLE `{$table_name}` {$change_sql};";
            }
        }

        foreach ( $sqls as $sql ) {
            $wpdb->query( $sql );
        }

        update_option('aimt_column_case_migrated', 1, false);
    }
}