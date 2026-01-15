<?php

class AIMT_Admin {
    public function __construct(){
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('save_post', array($this, 'aimt_show_alert_on_post_save'), 10, 3);
        add_action('wp_ajax_aimt_clear_alert_flag', array($this, 'aimt_clear_alert_flag'));
        add_action('wp_ajax_aimt_save_onboarding', array($this, 'save_onboarding_state'));
        add_action('wp_ajax_aimt_load_onboarding', array($this, 'load_onboarding_state'));
        add_action('wp_ajax_aimt_clear_onboarding', array($this, 'clear_onboarding_state'));
    }

    public function aimt_show_alert_on_post_save($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if (!is_admin()) return;

        if (!in_array($post->post_type, ['post', 'page'])) return;
        if (isset($post->post_status) && $post->post_status !== 'publish') {
            return;
        }

        update_post_meta($post_id, '_aimt_show_alert', 1);
    }

    public function aimt_clear_alert_flag() {
        check_ajax_referer('aimt_alert_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        delete_post_meta($post_id, '_aimt_show_alert');

        wp_send_json_success();
    }

    public function save_onboarding_state() {
        check_ajax_referer('aimt_onboarding_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $state = isset($_POST['state']) ? json_decode(stripslashes($_POST['state']), true) : null;
        
        if ($state) {
            $sanitized_state = $this->sanitize_onboarding_state($state);
            
            update_option('aimt_onboarding_state', $sanitized_state, false);
            
            $this->save_individual_options($sanitized_state);
            
            wp_send_json_success(array(
                'message' => 'State saved successfully',
                'state' => $sanitized_state
            ));
        } else {
            wp_send_json_error('Invalid state data');
        }
    }

    public function load_onboarding_state() {
        check_ajax_referer('aimt_onboarding_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $state = get_option('aimt_onboarding_state', array());
        
        wp_send_json_success(array(
            'state' => $state,
            'exists' => !empty($state)
        ));
    }

    public function clear_onboarding_state() {
        check_ajax_referer('aimt_onboarding_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        delete_option('aimt_onboarding_state');
        
        $this->clear_individual_options();
        
        wp_send_json_success('State cleared successfully');
    }

    private function sanitize_onboarding_state($state) {
        $sanitized = array();
        
        $sanitized['step'] = sanitize_text_field($state['step'] ?? 'languages');
        
        $sanitized['selectedLanguages'] = array();
        if (isset($state['selectedLanguages']) && is_array($state['selectedLanguages'])) {
            foreach ($state['selectedLanguages'] as $code => $name) {
                $clean_code = sanitize_text_field($code);
                $clean_name = sanitize_text_field($name);
                if ($clean_code && $clean_name) {
                    $sanitized['selectedLanguages'][$clean_code] = $clean_name;
                }
            }
        }
        
        $sanitized['translationLanguages'] = array();
        if (isset($state['translationLanguages']) && is_array($state['translationLanguages'])) {
            foreach ($state['translationLanguages'] as $code => $name) {
                $clean_code = sanitize_text_field($code);
                $clean_name = sanitize_text_field($name);
                if ($clean_code && $clean_name) {
                    $sanitized['translationLanguages'][$clean_code] = $clean_name;
                }
            }
        }
        
        $sanitized['postTypes'] = array();
        if (isset($state['postTypes']) && is_array($state['postTypes'])) {
            foreach ($state['postTypes'] as $pt) {
                $clean = sanitize_text_field($pt);
                if ($clean) $sanitized['postTypes'][] = $clean;
            }
        } else {
            if (!empty($state['postType'])) {
                $clean = sanitize_text_field($state['postType']);
                if ($clean) $sanitized['postTypes'][] = $clean;
            }
        }
        
        $sanitized['wpmlKey'] = sanitize_text_field($state['wpmlKey'] ?? '');
        $sanitized['translationMode'] = sanitize_text_field($state['translationMode'] ?? '');
        
        return $sanitized;
    }

    private function save_individual_options($state) {
        if (!empty($state['selectedLanguages'])) {
            update_option('aimt_default_languages', array_keys($state['selectedLanguages']));
        }
        
        if (!empty($state['translationLanguages'])) {
            update_option('aimt_translation_languages', array_keys($state['translationLanguages']));
        }
        
        if (!empty($state['postTypes'])) {
            update_option('aimt_selected_post_types', array_values($state['postTypes']));
        }
        
        update_option('aimt_translation_mode', $state['translationMode']);
        
        if (!empty($state['wpmlKey'])) {
            update_option('aimt_api_key', sanitize_text_field($state['wpmlKey']));
        }

    }

    private function clear_individual_options() {
        delete_option('aimt_default_languages');
        delete_option('aimt_translation_languages');
        delete_option('aimt_selected_post_types');
        // delete_option('aimt_url_format');
        delete_option('aimt_translation_mode');
        delete_option('aimt_wpml_key');
        // removed delete_option('aimt_plugin_options');
    }

    public function get_onboarding_config() {
        $state = get_option('aimt_onboarding_state', array());
        
        if (empty($state)) {
            $state = array(
                'selectedLanguages' => array_flip(get_option('aimt_default_languages', array('en'))),
                'translationLanguages' => array_flip(get_option('aimt_translation_languages', array())),
                'postTypes' => array_flip(get_option('aimt_selected_post_types', array())),
                // 'urlFormat' => get_option('aimt_url_format', 'subdirectory'),
                'translationMode' => get_option('aimt_translation_mode', ''),
                'wpmlKey' => get_option('aimt_wpml_key', ''),
                'support' => get_option('aimt_support_options', array())
                // removed 'plugins' option retrieval
            );
        }
        
        return $state;
    }

    public function is_onboarding_complete() {
        $state = $this->get_onboarding_config();
        return !empty($state['selectedLanguages']) && 
               !empty($state['translationLanguages']) && 
               !empty($state['postTypes']) &&
               !empty($state['translationMode']);
    }

   public function register_menu(){
    add_menu_page(
        'AI Multi-Language',
        'AI Multi-Language',
        'manage_options',
        'aimt-settings',
        array($this, 'settings_page'),
        'dashicons-translation',
        80
    );
    add_submenu_page(
        'aimt-settings',
        'Onboarding',
        'Onboarding',
        'manage_options',
        'aimt-onboarding',
        array($this, 'onboarding_page')
    );
    add_submenu_page(
        null, // Hide from menu
        'Debug Onboarding',
        'Debug Onboarding',
        'manage_options',
        'aimt-debug-onboarding',
        array($this, 'debug_onboarding_state')
    );
}
    

    public function assets($hook) {
        $is_onboarding = (strpos($hook, 'aimt-onboarding') !== false);
        $is_settings   = (strpos($hook, 'aimt-settings') !== false);

        if ($is_onboarding) {
            wp_enqueue_style(
                'aimt-bootstrap',
                'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css'
            );

            wp_enqueue_script(
                'aimt-bootstrap',
                'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js',
                array('jquery'),
                null,
                true
            );

            wp_enqueue_script(
                'aimt-onboarding-js',
                plugin_dir_url(__FILE__) . '../assets/js/onboarding.js',
                array('jquery', 'aimt-bootstrap'),
                '1.0',
                true
            );
        }

        if ($is_onboarding || $is_settings) {
            wp_enqueue_style(
                'aimt-onboarding-css',
                plugin_dir_url(__FILE__) . '../assets/css/onboarding.css',
                array(),
                '1.0'
            );
        }

        if ($is_onboarding) {
            wp_localize_script('aimt-onboarding-js', 'aimtOnboardingData', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('aimt_onboarding_nonce'),
            ]);
        }

        if (in_array($hook, ['post.php', 'post-new.php'])) {
            wp_enqueue_script(
                'aimt-post-alert',
                plugin_dir_url(__FILE__) . '../assets/js/post-alert.js',
                ['jquery'],
                '1.0',
                true
            );

            global $post;
            if ($post) {
                wp_localize_script('aimt-post-alert', 'aimtData', [
                    'show_alert' => get_post_meta($post->ID, '_aimt_show_alert', true),
                    'post_id'    => $post->ID,
                    'ajax_url'   => admin_url('admin-ajax.php'),
                    'nonce'      => wp_create_nonce('aimt_alert_nonce')
                ]);
            }
        }
    }
   public function debug_onboarding_state() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    echo '<div class="wrap"><h2>Debug Onboarding State</h2>';
    
    $state = get_option('aimt_onboarding_state', array());
    echo '<h3>Complete State (aimt_onboarding_state):</h3>';
    echo '<pre>';
    print_r($state);
    echo '</pre>';
    
    echo '<h3>Individual Options:</h3>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr><th>Option Name</th><th>Value</th></tr></thead><tbody>';
    
    $options = array(
        'aimt_default_languages' => 'Default Languages',
        'aimt_translation_languages' => 'Translation Languages',
        'aimt_selected_post_type' => 'Selected Post Type',
        'aimt_url_format' => 'URL Format',
        'aimt_translation_mode' => 'Translation Mode',
        'aimt_wpml_key' => 'WPML Key',
        'aimt_support_options' => 'Support Options'
        // removed 'aimt_plugin_options' entry
    );
    
    foreach ($options as $option => $label) {
        $value = get_option($option, 'Not Set');
        echo '<tr>';
        echo '<td><strong>' . $label . '</strong><br><code>' . $option . '</code></td>';
        echo '<td><pre>' . print_r($value, true) . '</pre></td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    
    echo '<h3>Database Query:</h3>';
    global $wpdb;
    $result = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
            'aimt_onboarding_state'
        )
    );
    
    if ($result) {
        echo '<p>Raw database value:</p>';
        echo '<pre>' . esc_html($result->option_value) . '</pre>';
        echo '<p>Decoded:</p>';
        echo '<pre>';
        print_r(maybe_unserialize($result->option_value));
        echo '</pre>';
    } else {
        echo '<p>No record found in database.</p>';
    }
    
    echo '<p><a href="' . admin_url('admin.php?page=aimt-onboarding') . '" class="button">Go Back to Onboarding</a></p>';
    echo '</div>';
}
    public function onboarding_page() {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['aimt_onboarding_state']) &&
            isset($_POST['aimt_onboarding_nonce']) &&
            wp_verify_nonce($_POST['aimt_onboarding_nonce'], 'aimt_onboarding_save') &&
            current_user_can('manage_options')
        ) {
            $raw = wp_unslash($_POST['aimt_onboarding_state']);
            $decoded = json_decode($raw, true);
            if ( json_last_error() === JSON_ERROR_NONE && is_array($decoded) ) {
                $sanitized = $this->sanitize_onboarding_state($decoded);
                // save full state and individual options (keeps data separated)
                update_option('aimt_onboarding_state', $sanitized);
                $this->save_individual_options($sanitized);
                echo '<div class="updated"><p>Onboarding configuration saved.</p></div>';
            } else {
                echo '<div class="error"><p>Invalid onboarding data submitted.</p></div>';
            }
        }
        
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['aimt_posts']) &&
            isset($_POST['aimt_posts_nonce']) &&
            wp_verify_nonce($_POST['aimt_posts_nonce'], 'aimt_save_posts')
        ) {
            update_option(
                'aimt_translatable_post_types',
                array_map('sanitize_text_field', $_POST['aimt_post_types'])
            );

            echo '<div class="updated"><p>Selected posts saved!</p></div>';
        }
        
        $translatable_post_types = get_option(
            'aimt_translatable_post_types',
            array('post', 'page')
        );
        
    $post_types = get_post_types(
    array(
        'public'   => true
    ),
    'objects'
);
   

       $steps = array(
            'languages' => 'Languages',
            'register-multilang' => 'Register AI multi language translation',
            'translation-mode' => 'Translation Mode',
            'finished' => 'Finished'
        );

        $template_path = plugin_dir_path( __FILE__ ) . '../templates/html/onboarding-page.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<div class="wrap"><div class="notice notice-error"><p>Onboarding template not found: ' . esc_html( $template_path ) . '</p></div></div>';
        }

        /* 
       .
        
        ?>
        <div class="wrap aimt-onboarding">
          <h1 class="font-poppins text-center main-h">AI multi language translation</h1>

            <div class="container mt-4">
                <div class="progress mb-5">
                    <?php 
                    $step_count = count($steps);
                    $step_width = 100 / $step_count;
                    $i = 0;
                    foreach ($steps as $key => $label): 
                        $i++;
                    ?>
                        <div class="progress-step" style="width: <?php echo $step_width; ?>%">
                            <div class="step-number <?php echo ($i === 1) ? 'active' : ''; ?>"><?php echo $i; ?></div>
                            <div class="step-label"><?php echo $label; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="onboarding-steps">
                    <div class="step-content step-languages active ">
                        <h2 class="text-center font-poppins">Languages</h2>
                        <p class="text-center font-poppins">Select the languages you want to support on your website.</p>
                        
                            <div class="languages-select mt-4">
                    <label class="form-label">Default Languages</label>

                    <?php
                    $common_languages = array(
                        'English' => 'en',
                        'Spanish' => 'es',
                        'French' => 'fr',
                        'German' => 'de',
                        'Italian' => 'it',
                        'Portuguese' => 'pt',
                        'Chinese' => 'zh',
                        'Japanese' => 'ja',
                        'Russian' => 'ru',
                        'Arabic' => 'ar'
                    );
                    ?>

                    <div class="dropdown language-dropdown">
                        <button
                            class="btn btn-light border dropdown-toggle btn-first"
                            type="button"
                            id="languagesDropdownBtn"
                            data-toggle="dropdown"
                            aria-haspopup="true"
                            aria-expanded="false"
                        >
                            English
                        </button>

                        <div class="dropdown-menu p-2" aria-labelledby="languagesDropdownBtn">
                            <input
                                type="text"
                                class="form-control form-control-sm mb-2 language-search"
                                placeholder="Search language..."
                            >

                            <div class="language-options">
                                <?php foreach ($common_languages as $name => $code): ?>
                                    <div
                                        class="dropdown-item language-option"
                                        data-code="<?php echo esc_attr($code); ?>"
                                        data-name="<?php echo esc_attr($name); ?>"
                                    >
                                        <?php echo esc_html($name); ?> (<?php echo strtoupper($code); ?>)
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="selected-languages"></div>
                    <small class="form-text text-muted mt-2">
                        Search and select multiple languages
                    </small>
                    <br>
                <label class="form-label mt-4">Translation Languages</label>

                <div class="dropdown language-dropdown translation-language-dropdown">
                    <button
                        class="btn btn-light border dropdown-toggle btn-first"
                        type="button"
                        id="translationLanguagesDropdownBtn"
                        data-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        Select translation languages
                    </button>

                    <div class="dropdown-menu p-2" aria-labelledby="translationLanguagesDropdownBtn">
                        <input
                            type="text"
                            class="form-control form-control-sm mb-2 translation-language-search"
                            placeholder="Search language..."
                        >

                        <div class="translation-language-options">
                            <?php foreach ($common_languages as $name => $code): ?>
                                <div
                                    class="dropdown-item translation-language-option"
                                    data-code="<?php echo esc_attr($code); ?>"
                                    data-name="<?php echo esc_attr($name); ?>"
                                >
                                    <?php echo esc_html($name); ?> (<?php echo strtoupper($code); ?>)
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                            <?php
                            $total_posts = 0;
                            foreach ($post_types as $post_type) {
                                if ($post_type->name === 'attachment') continue;
                                $count = wp_count_posts($post_type->name)->publish;
                                $total_posts += $count;
                            }
                            ?>
                           <label class="form-label mt-2">Select Post Type</label>

                                <div class="dropdown language-dropdown post-type-dropdown mb-3">
                                    <button
                                        class="btn btn-light border dropdown-toggle btn-first"
                                        type="button"
                                        id="postTypeDropdownBtn"
                                        data-toggle="dropdown"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                    >
                                        Select Post Type
                                    </button>

                                    <div class="dropdown-menu p-2" aria-labelledby="postTypeDropdownBtn">
                                        <input
                                            type="text"
                                            class="form-control form-control-sm mb-2 posttype-search"
                                            placeholder="Search post types..."
                                        >

                                        <div class="post-type-options">
                                            <?php foreach ($post_types as $post_type): ?>
                                                <?php if ($post_type->name === 'attachment') continue; ?>
                                                <div
                                                    class="dropdown-item post-type-option"
                                                    data-post-type="<?php echo esc_attr($post_type->name); ?>"
                                                    data-name="<?php echo esc_attr($post_type->labels->singular_name); ?>"
                                                >
                                                    <?php echo esc_html($post_type->labels->singular_name); ?> (<?php echo esc_html($post_type->name); ?>)
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="selected-post-types"></div>
                                <div class="selected-translation-languages" data-required="true"></div>
                </div>   
                 
                                <div class="mt-4">
                                        <button class="button button-primary next-step btn-next" data-next="register-multilang">Next</button>
                                    </div>
                                </div>
                                <div class="step-content step-register-multilang">
                                    <h2>Register AI multi language translation</h2>
                                    <p>Connect your site to AI multi language translation for enhanced multilingual features.</p>
                                    <div class="mt-4">
                                        <div class="form-group">
                                            <label for="wpml_key" class="font-poppins">AI multi language translation Registration Key</label>
                                            <input type="text" class="form-control" id="wpml_key" name="wpml_key" placeholder="Enter your AI multi language translation key" required>
                                            <small class="form-text text-muted">Get your key from your AI multi language translation account.</small>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button class="button prev-step" data-prev="languages">Back</button>
                                        <button class="button button-primary next-step" data-next="translation-mode">Next</button>
                                    </div>
                                </div>
                                <div class="step-content step-translation-mode">
                                    <h2>How would you like to translate the content?</h2> 
                                     <div class="row mt-4">
                                         <div class="col-md-6 mb-4">
                                             <div class="card p-4 h-100">
                                                 <h4>Translate Everything Automatically</h4>
                                                 <p class="text-muted">Let AI Multilag do the translating for you</p>
                                                 <ul class="features-list">
                                                     <li>✓ Translates all your published content automatically</li>
                                                     <li>✓ Uses machine translation powered by Google, Microsoft, or DeepL</li>
                                                     <li>✓ Instantly translates new content and updates translations whenever you edit a page or post</li>
                                                     <li>✓ You can review translations before publishing or hire professional reviewers</li>
                                                     <li>✓ Affordable and fast</li>
                                                 </ul>
                                                 <button class="button button-primary btn-block choose-mode" data-mode="auto">Choose</button>
                                             </div>
                                         </div>
                                         
                                         <div class="col-md-6 mb-4">
                                             <div class="card p-4 h-100">
                                                 <h4>Translate What You Choose</h4>
                                                 <p class="text-muted">You decide what to translate and who'll translate it</p>
                                                 <ul class="features-list">
                                                     <li>✓ Translate yourself</li>
                                                     <li>✓ Use automatic translation on the content you choose</li>
                                                     <li>✓ Work with translators that are users of your site</li>
                                                     <li>✓ Send to professional translation services</li>
                                                 </ul>
                                                 <button class="button btn-block choose-mode" data-mode="manual">Choose</button>
                                             </div>
                                         </div>
                                     </div>
                                     
                                     <div class="mt-4">
                                        <button class="button prev-step" data-prev="register-multilang">Back</button>
                                        <button class="button button-primary next-step" data-next="finished">Next</button>
                                     </div>
                                 </div>

                                 <div class="step-content step-finished text-center">
                                     <h2>🎉 Setup Complete!</h2>
                                     <p class="lead">Your multilingual site is ready to go!</p>
                                    
                                    <div class="success-icon mb-4">
                                        <span class="dashicons dashicons-yes-alt" style="font-size: 80px; color: #46b450;"></span>
                                    </div>
                                    
                                    <div class="summary mt-4 p-4 bg-light rounded">
                                        <h5>Summary:</h5>
                                        <p>All settings have been configured successfully.</p>
                                        <p>You can always modify these settings from the main settings page.</p>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button class="button prev-step" data-prev="translation-mode">Back</button>
                                         <a href="<?php echo admin_url('admin.php?page=aimt-settings'); ?>" class="button button-primary">Go to Settings</a>
                                         <a href="<?php echo home_url(); ?>" class="button" target="_blank">Visit Site</a>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                    <?php
                    ?>

        <script type="text/javascript">
        (function($){
          // Validation is now handled in onboarding.js
          // Keeping this script block for any future inline scripts if needed
        })(jQuery);
        </script>

        <?php
        */
    }

    public function settings_page() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'aimt_string_translations';

        $common_languages = array(
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'ru' => 'Russian',
            // 'ar' => 'Arabic'
        );

        $available_source_codes = (array) get_option( 'aimt_default_languages', array() );
        $available_target_codes = (array) get_option( 'aimt_translation_languages', array() );

        if ( ! empty( $available_source_codes ) ) {
            $source_options = array_intersect_key( $common_languages, array_flip( $available_source_codes ) );
        } else {
            $source_options = $common_languages;
        }

        if ( ! empty( $available_target_codes ) ) {
            $target_options = array_intersect_key( $common_languages, array_flip( $available_target_codes ) );
        } else {
            $target_options = $common_languages;
        }

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can('manage_options') ) {

            if ( isset($_POST['action']) && $_POST['action'] === 'aimt_add_translation' ) {
                if ( ! wp_verify_nonce( $_POST['aimt_add_translation_nonce'] ?? '', 'aimt_add_translation' ) ) {
                    echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                } else {
                    $string = sanitize_textarea_field( wp_unslash( $_POST['string'] ?? '' ) );
                    $lang = sanitize_text_field( $_POST['lang'] ?? '' );
                    $translated_lang = sanitize_text_field( $_POST['translated_lang'] ?? '' );
                    $translated_string = sanitize_textarea_field( wp_unslash( $_POST['translated_string'] ?? '' ) );

                    if ( empty( $string ) || empty( $lang ) || empty( $translated_lang ) || empty( $translated_string ) ) {
                        echo '<div class="notice notice-error"><p>Please fill in all fields before adding a translation.</p></div>';
                    } else {
                        $wpdb->insert(
                            $table_name,
                            array(
                                'string' => $string,
                                'lang' => $lang,
                                'translated_lang' => $translated_lang,
                                'translated_string' => $translated_string,
                                'created_at' => current_time('mysql'),
                                'updated_at' => current_time('mysql')
                            ),
                            array( '%s', '%s', '%s', '%s', '%s', '%s' )
                        );

                        echo '<div class="notice notice-success"><p>Translation added.</p></div>';
                    }
                }
            }

            if ( isset($_POST['action']) && $_POST['action'] === 'aimt_edit_translation' ) {
                if ( ! wp_verify_nonce( $_POST['aimt_edit_translation_nonce'] ?? '', 'aimt_edit_translation' ) ) {
                    echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                } else {
                    $id = intval( $_POST['id'] ?? 0 );
                    if ( $id > 0 ) {
                        $string = sanitize_textarea_field( wp_unslash( $_POST['string'] ?? '' ) );
                        $lang = sanitize_text_field( $_POST['lang'] ?? '' );
                        $translated_lang = sanitize_text_field( $_POST['translated_lang'] ?? '' );
                        $translated_string = sanitize_textarea_field( wp_unslash( $_POST['translated_string'] ?? '' ) );

                        if ( empty( $string ) || empty( $lang ) || empty( $translated_lang ) || empty( $translated_string ) ) {
                            echo '<div class="notice notice-error"><p>Please fill in all fields before updating a translation.</p></div>';
                        } else {
                            $wpdb->update(
                                $table_name,
                                array(
                                    'string' => $string,
                                    'lang' => $lang,
                                    'translated_lang' => $translated_lang,
                                    'translated_string' => $translated_string,
                                    'updated_at' => current_time('mysql')
                                ),
                                array( 'id' => $id ),
                                array( '%s', '%s', '%s', '%s', '%s' ),
                                array( '%d' )
                            );

                            echo '<div class="notice notice-success"><p>Translation updated.</p></div>';
                        }
                    }
                }
            }

            if ( isset($_POST['action']) && $_POST['action'] === 'aimt_delete_translation' ) {
                if ( ! wp_verify_nonce( $_POST['aimt_delete_translation_nonce'] ?? '', 'aimt_delete_translation' ) ) {
                    echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                } else {
                    $id = intval( $_POST['id'] ?? 0 );
                    if ( $id > 0 ) {
                        $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
                        echo '<div class="notice notice-success"><p>Translation deleted.</p></div>';
                    }
                }
            }
        }

        $edit_row = null;
        if ( isset($_GET['edit_translation']) ) {
            $edit_id = intval( $_GET['edit_translation'] );
            if ( $edit_id > 0 ) {
                $edit_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d LIMIT 1", $edit_id ), ARRAY_A );
            }
        }

        $translations = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY id DESC", ARRAY_A );

        $template_path = plugin_dir_path( __FILE__ ) . '../templates/html/settings-page.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<div class="wrap"><div class="notice notice-error"><p>Settings template not found: ' . esc_html( $template_path ) . '</p></div></div>';
        }

    }
}