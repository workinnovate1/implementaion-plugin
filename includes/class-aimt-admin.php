<?php

class AIMT_Admin {
     public function __construct(){
          add_action('admin_menu' , array($this , 'register_menu'));
     }
     public function register_menu(){
          add_menu_page(
            'AI Multi-Language',
            'AI Multi-Language',
            'manage_options',
            'aimt-settings',
            array( $this, 'settings_page' ),
            'dashicons-translation',
            80
        );
        
     }
      public function settings_page() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aimt_languages_nonce']) && wp_verify_nonce($_POST['aimt_languages_nonce'], 'aimt_save_languages')) {
            $langs = array_map('sanitize_text_field', array_filter(explode(',', $_POST['aimt_languages'])));
            update_option('aimt_default_languages', $langs);
            echo '<div class="updated"><p>Languages updated!</p></div>';
        }

        $languages = get_option('aimt_default_languages', array('en','fr','de'));
        ?>
        <div class="wrap">
            <h1>AI Multi-Language Translation Settings</h1>
            <form method="post">
                <?php wp_nonce_field('aimt_save_languages', 'aimt_languages_nonce'); ?>
                <label>Enter languages, comma separated:</label>
                <input type="text" name="aimt_languages" value="<?php echo esc_attr(implode(',', $languages)); ?>" style="width:100%;">
                <p><input type="submit" class="button button-primary" value="Save Languages"></p>
            </form>
            <h2>Current Languages:</h2>
            <ul>
                <?php foreach ($languages as $lang) {
                    echo '<li>' . esc_html($lang) . '</li>';
                } ?>
            </ul>
        </div>
        <?php
    }
}