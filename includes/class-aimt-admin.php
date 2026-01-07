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
        
        $sanitized['postType'] = sanitize_text_field($state['postType'] ?? '');
        // $sanitized['urlFormat'] = sanitize_text_field($state['urlFormat'] ?? 'subdirectory');
        $sanitized['wpmlKey'] = sanitize_text_field($state['wpmlKey'] ?? '');
        $sanitized['translationMode'] = sanitize_text_field($state['translationMode'] ?? '');
        
        // $sanitized['support'] = array(
        //     'support_docs' => !empty($state['support']['support_docs']),
        //     'support_forum' => !empty($state['support']['support_forum']),
        //     'support_email' => !empty($state['support']['support_email'])
        // );
        
        $sanitized['plugins'] = array(
            'plugin_woocommerce' => !empty($state['plugins']['plugin_woocommerce']),
            'plugin_seo' => !empty($state['plugins']['plugin_seo']),
            'plugin_slug' => !empty($state['plugins']['plugin_slug']),
            'plugin_media' => !empty($state['plugins']['plugin_media'])
        );
        
        return $sanitized;
    }

    private function save_individual_options($state) {
        if (!empty($state['selectedLanguages'])) {
            update_option('aimt_default_languages', array_keys($state['selectedLanguages']));
        }
        
        if (!empty($state['translationLanguages'])) {
            update_option('aimt_translation_languages', array_keys($state['translationLanguages']));
        }
        
        if (!empty($state['postType'])) {
            update_option('aimt_selected_post_type', $state['postType']);
        }
        
        // update_option('aimt_url_format', $state['urlFormat']);
        update_option('aimt_translation_mode', $state['translationMode']);
        
        if (!empty($state['wpmlKey'])) {
            update_option('aimt_wpml_key', $state['wpmlKey']);
        }
        
        // update_option('aimt_support_options', $state['support']);
        update_option('aimt_plugin_options', $state['plugins']);
    }

    private function clear_individual_options() {
        delete_option('aimt_default_languages');
        delete_option('aimt_translation_languages');
        delete_option('aimt_selected_post_type');
        // delete_option('aimt_url_format');
        delete_option('aimt_translation_mode');
        delete_option('aimt_wpml_key');
        // delete_option('aimt_support_options');
        delete_option('aimt_plugin_options');
    }

    public function get_onboarding_config() {
        $state = get_option('aimt_onboarding_state', array());
        
        if (empty($state)) {
            $state = array(
                'selectedLanguages' => array_flip(get_option('aimt_default_languages', array('en'))),
                'translationLanguages' => array_flip(get_option('aimt_translation_languages', array())),
                'postType' => get_option('aimt_selected_post_type', ''),
                // 'urlFormat' => get_option('aimt_url_format', 'subdirectory'),
                'translationMode' => get_option('aimt_translation_mode', ''),
                'wpmlKey' => get_option('aimt_wpml_key', ''),
                'support' => get_option('aimt_support_options', array()),
                'plugins' => get_option('aimt_plugin_options', array())
            );
        }
        
        return $state;
    }

    public function is_onboarding_complete() {
        $state = $this->get_onboarding_config();
        return !empty($state['selectedLanguages']) && 
               !empty($state['translationLanguages']) && 
               !empty($state['postType']) &&
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
        if (strpos($hook, 'aimt-onboarding') !== false) {
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

            wp_enqueue_style(
                'aimt-onboarding-css',
                plugin_dir_url(__FILE__) . '../assets/css/onboarding.css',
                array(),
                '1.0'
            );
            
            wp_enqueue_script(
                'aimt-onboarding-js',
                plugin_dir_url(__FILE__) . '../assets/js/onboarding.js',
                array('jquery', 'aimt-bootstrap'),
                '1.0',
                true
            );

            wp_localize_script('aimt-onboarding-js', 'aimtOnboardingData', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aimt_onboarding_nonce')
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
        'aimt_support_options' => 'Support Options',
        'aimt_plugin_options' => 'Plugin Options'
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

                            <div class="dropdown post-type-dropdown mb-3">
                                <button class="btn btn-light border dropdown-toggle w-100 text-start"
                                        type="button"
                                        id="postTypeDropdownBtn"
                                        data-toggle="dropdown"
                                        aria-haspopup="true"
                                        aria-expanded="false">
                                    Select Post Type
                                </button>

                                <div class="dropdown-menu w-100 p-2" aria-labelledby="postTypeDropdownBtn">
                                    <?php foreach ($post_types as $post_type): ?>
                                        <?php if ($post_type->name === 'attachment') continue; ?>
                                        <div class="dropdown-item post-type-option"
                                             data-post-type="<?php echo esc_attr($post_type->name); ?>">
                                            <?php echo esc_html($post_type->labels->singular_name); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <input type="hidden" name="aimt_post_type" id="aimt_post_type" data-required="true">
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

                             <!-- <div class="step-content step-support">
                                <h2>Support</h2>
                                <p>Select your support preferences.</p>
                                
                                <div class="support-options mt-4">
                                    <div class="form-check mb-3">
                                        <input style="margin-top:8px"   class="form-check-input" type="checkbox" id="support_docs" checked>
                                        <label style="margin-left:24px"  class="form-check-label" for="support_docs">
                                            <strong>Documentation Access</strong><br>
                                            <small>Get access to comprehensive documentation</small>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input style="margin-top:8px"  class="form-check-input" type="checkbox" id="support_forum">
                                        <label style="margin-left: 24px" class="form-check-label" for="support_forum">
                                            <strong>Forum Support</strong><br>
                                            <small>Access to community support forums</small>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input style="margin-top:8px"   class="form-check-input" type="checkbox" id="support_email">
                                        <label style="margin-left: 24px"  class="form-check-label" for="support_email">
                                            <strong>Email Support</strong><br>
                                            <small>Direct email support with our team</small>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button class="button prev-step" data-prev="translation-mode">Back</button>
                                    <button class="button button-primary next-step" data-next="plugins">Next</button>
                                </div>
                            </div> -->
                            <!-- <div class="step-content step-plugins">
                                <h2>Recommended Plugins</h2>
                                <p>Enhance your multilingual site with these recommended plugins.</p>
                                
                                <div class="plugins-list mt-4">
                                    <div class="form-check mb-3">
                                        <input style="margin-top:8px"   class="form-check-input" type="checkbox" id="plugin_woocommerce" checked>
                                        <labe style="margin-left:24px"   class="form-check-label" for="plugin_woocommerce">
                                            <strong>WooCommerce Multilingual</strong><br>
                                            <small>Translate your WooCommerce products and store</small>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input style="margin-top:8px"   class="form-check-input" type="checkbox" id="plugin_seo" checked>
                                        <label style="margin-left: 24px"  class="form-check-label" for="plugin_seo">
                                            <strong>SEO Pack</strong><br>
                                            <small>Optimize your multilingual site for search engines</small>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input style="margin-top:8px"   class="form-check-input" type="checkbox" id="plugin_slug">
                                        <label style="margin-left: 24px"  class="form-check-label" for="plugin_slug">
                                            <strong>Slug Translation</strong><br>
                                            <small>Translate URL slugs for better SEO</small>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input style="margin-top:8px"   class="form-check-input" type="checkbox" id="plugin_media">
                                        <label style="margin-left: 24px"  class="form-check-label" for="plugin_media">
                                            <strong>Media Translation</strong><br>
                                            <small>Translate media captions and alt texts</small>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button class="button prev-step" data-prev="support">Back</button>
                                    <button class="button button-primary next-step" data-next="finished">Next</button>
                                </div>
                            </div> -->

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
  $(document).on('click', '.next-step', function(e){
    var next = $(this).data('next');

    if(next === 'register-multilang'){
      var selLangsCount = $('.selected-languages').children().length;
      if (selLangsCount === 0) {
        selLangsCount = $('.selected-languages .selected-language, .selected-languages .tag, .selected-languages input[type="hidden"]').length;
      }
      if(selLangsCount === 0){
        alert('Please select at least one default language.');
        e.preventDefault(); return false;
      }

      var transCount = $('.selected-translation-languages').children().length;
      if (transCount === 0) {
        transCount = $('.selected-translation-languages .selected-language, .selected-translation-languages .tag, .selected-translation-languages input[type="hidden"]').length;
      }
      if(transCount === 0){
        alert('Please select at least one translation language.');
        e.preventDefault(); return false;
      }

      if(!$('#aimt_post_type').val()){
        alert('Please select a post type.');
        e.preventDefault(); return false;
      }
    }

    if(next === 'translation-mode'){
      if($('#wpml_key').length && !$('#wpml_key').val().trim()){
        alert('Please enter your AI multi language translation registration key.');
        e.preventDefault(); return false;
      }
    }

    if(next === 'finished'){
      if(!$('.choose-mode.active').length){
        alert('Please choose a translation mode.');
        e.preventDefault(); return false;
      }
    }
  });

  $(document).on('click', '.choose-mode', function(e){
    $('.choose-mode').removeClass('active');
    $(this).addClass('active');
  });

})(jQuery);
</script>

                <?php
            }
                public function settings_page() {

                    if (
                        $_SERVER['REQUEST_METHOD'] === 'POST' &&
                        isset($_POST['aimt_languages_nonce']) &&
                        wp_verify_nonce($_POST['aimt_languages_nonce'], 'aimt_save_languages')
                    ) {
                        $langs = array_map(
                            'sanitize_text_field',
                            array_filter(explode(',', $_POST['aimt_languages']))
                        );
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
                            <input type="text"
                                name="aimt_languages"
                                value="<?php echo esc_attr(implode(',', $languages)); ?>"
                                style="width:100%;">
                            <p>
                                <input type="submit" class="button button-primary" value="Save Languages">
                            </p>
                        </form>

                        <h2>Current Languages:</h2>
                        <ul>
                            <?php foreach ($languages as $lang) : ?>
                                <li><?php echo esc_html($lang); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php
                }
            }