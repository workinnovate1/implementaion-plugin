<?php

class AIMT_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('save_post', array($this, 'aimt_show_alert_on_post_save'), 10, 3);
        add_action('save_post', array($this, 'aimt_process_translation_options'), 20, 3);
        add_action('wp_ajax_aimt_clear_alert_flag', array($this, 'aimt_clear_alert_flag'));
        add_action('wp_ajax_aimt_save_onboarding', array($this, 'save_onboarding_state'));
        add_action('wp_ajax_aimt_load_onboarding', array($this, 'load_onboarding_state'));
        add_action('wp_ajax_aimt_clear_configration', array($this, 'clear_onboarding_state'));

        add_action('add_meta_boxes', array($this, 'register_translation_metabox'));
    }

    // public function aimt_show_alert_on_post_save($post_id, $post, $update)
    // {
    //     if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    //     if (wp_is_post_revision($post_id)) return;
    //     if (!is_admin()) return;

    //     if (!in_array($post->post_type, ['post', 'page'])) return;
    //     if (isset($post->post_status) && $post->post_status !== 'publish') {
    //         return;
    //     }

    //     update_post_meta($post_id, '_aimt_show_alert', 1);
    // }

    public function register_translation_metabox()
    {
        $post_types = $this->get_configured_post_type_slugs();
        foreach ($post_types as $pt) {
            add_meta_box(
                'aimt-translation-box',
                __('AI Translation', 'aimt'),
                array($this, 'render_translation_metabox'),
                $pt,
                'side',
                'high'
            );
        }
    }

    public function aimt_show_alert_on_post_save($post_id, $post, $update)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (wp_is_post_revision($post_id))
            return;
        if (!is_admin())
            return;
        $selected_raw = (array) get_option('aimt_selected_post_types', array());
        if (empty($selected_raw)) {
            $selected_raw = (array) get_option('aimt_translatable_post_types', array());
        }

        $registered = get_post_types(array(), 'objects');
        $allowed = array();

        foreach ($selected_raw as $v) {
            $v = sanitize_text_field($v);
            if (isset($registered[$v])) {
                $allowed[] = $v;
                continue;
            }
            foreach ($registered as $slug => $obj) {
                $labels = [
                    strtolower($slug),
                    strtolower($obj->label ?? ''),
                    strtolower($obj->labels->name ?? ''),
                    strtolower($obj->labels->singular_name ?? ''),
                ];
                if (in_array(strtolower($v), $labels, true)) {
                    $allowed[] = $slug;
                    break;
                }
            }
        }

        $allowed = array_values(array_unique(array_filter($allowed)));
        if (empty($allowed)) {
            $allowed = array('post', 'page');
        }
        
        if (!in_array('page', $allowed, true)) {
            $allowed[] = 'page';
        }

        if (!in_array($post->post_type, $allowed, true))
            return;
        if (!isset($post->post_status) || !in_array($post->post_status, array('draft', 'publish'), true))
            return;

        update_post_meta($post_id, '_aimt_show_alert', 1);
    }

  
    public function aimt_process_translation_options($post_id, $post, $update)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (wp_is_post_revision($post_id))
            return;
        if (!is_admin())
            return;

        if (!current_user_can('edit_post', $post_id))
            return;

        if (!isset($_POST['aimt_translation_options']) || empty($_POST['aimt_translation_options'])) {
            return;
        }

        $languages_json = sanitize_text_field($_POST['aimt_translation_options']);
        $languages = json_decode($languages_json, true);

        if (!is_array($languages) || empty($languages)) {
            return;
        }

        update_post_meta($post_id, '_aimt_pending_translations', $languages);
        update_post_meta($post_id, '_aimt_translation_requested', true);
        update_post_meta($post_id, '_aimt_translation_timestamp', time());

        $post_content = $post->post_content;
        $post_title = $post->post_title;
        $post_excerpt = $post->post_excerpt;

        $source_language = get_option('aimt_default_languages', array('en'));
        $source_language = is_array($source_language) && !empty($source_language) ? $source_language[0] : 'en';

        global $wpdb;
        $table_name = $wpdb->prefix . 'aimt_translations';

        foreach ($languages as $target_lang) {
            $target_lang = sanitize_text_field($target_lang);
            if (empty($target_lang))
                continue;

            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE post_id=%d AND language_code=%s",
                $post_id,
                $target_lang
            ));

            $translation_text = $this->aimt_translate_content($post_content, $source_language, $target_lang);
            $translation_title = $this->aimt_translate_content($post_title, $source_language, $target_lang);
            $translation_excerpt = !empty($post_excerpt) ? $this->aimt_translate_content($post_excerpt, $source_language, $target_lang) : '';

            $translation_data = array(
                'title' => $translation_title,
                'content' => $translation_text,
                'excerpt' => $translation_excerpt,
                'source_lang' => $source_language,
                'target_lang' => $target_lang,
                'translated_at' => current_time('mysql')
            );

            if ($existing) {
                $wpdb->update(
                    $table_name,
                    array('translation_text' => json_encode($translation_data)),
                    array('id' => $existing),
                    array('%s'),
                    array('%d')
                );
            } else {
                $wpdb->insert(
                    $table_name,
                    array(
                        'post_id' => $post_id,
                        'language_code' => $target_lang,
                        'translation_text' => json_encode($translation_data)
                    ),
                    array('%d', '%s', '%s')
                );
            }
        }

        delete_post_meta($post_id, '_aimt_pending_translations');
    }

  
    private function aimt_translate_content($content, $source_lang, $target_lang)
    {
        if (empty($content)) {
            return '';
        }

        // If source and target are the same, return original
        if ($source_lang === $target_lang) {
            return $content;
        }

        // Get API configuration
        $api_key = get_option('aimt_api_key', '');
        $translation_mode = get_option('aimt_translation_mode', '');

        // TODO: Implement actual translation API call here
        // Example implementations:
        //
        // 1. Google Translate API:
        //    $response = wp_remote_post('https://translation.googleapis.com/language/translate/v2', array(
        //        'body' => array(
        //            'key' => $api_key,
        //            'q' => $content,
        //            'source' => $source_lang,
        //            'target' => $target_lang
        //        )
        //    ));
        //
        // 2. DeepL API:
        //    $response = wp_remote_post('https://api-free.deepl.com/v2/translate', array(
        //        'headers' => array('Authorization' => 'DeepL-Auth-Key ' . $api_key),
        //        'body' => array(
        //            'text' => $content,
        //            'source_lang' => strtoupper($source_lang),
        //            'target_lang' => strtoupper($target_lang)
        //        )
        //    ));
        //
        // 3. Microsoft Translator API:
        //    Similar implementation using Microsoft's API

        // For now, return original content as placeholder
        // TODO: Replace this with actual API call when translation service is configured
        // The structure below shows how to implement the API call

        if (!empty($api_key) && !empty($translation_mode)) {
            // API is configured - implement translation call here
            // Example structure (uncomment and configure for your API):

            /*
             * $response = wp_remote_post('YOUR_TRANSLATION_API_URL', array(
             *     'headers' => array(
             *         'Authorization' => 'Bearer ' . $api_key,
             *         'Content-Type' => 'application/json'
             *     ),
             *     'body' => json_encode(array(
             *         'text' => $content,
             *         'source' => $source_lang,
             *         'target' => $target_lang
             *     )),
             *     'timeout' => 30
             * ));
             *
             * if (!is_wp_error($response)) {
             *     $body = json_decode(wp_remote_retrieve_body($response), true);
             *     if (isset($body['translated_text'])) {
             *         return $body['translated_text'];
             *     }
             * }
             */
        }

        
        return $content;
    }

    public function render_translation_metabox($post)
    {
        if (!current_user_can('edit_post', $post->ID)) {
            return;
        }

        $status = $post->post_status ?? '';
        if (!in_array($status, array('draft', 'publish'), true)) {
            echo '<p>' . esc_html__('Translation becomes available after saving a Draft or Publishing.', 'aimt') . '</p>';
            return;
        }

        wp_nonce_field('aimt_translate_post', 'aimt_translate_nonce');

        $enabled = get_post_meta($post->ID, '_aimt_show_alert', true) ? true : false;

        if ($enabled) {
            echo '<p><strong>' . esc_html__('Translation available.', 'aimt') . '</strong></p>';
            echo '<p><a class="button button-primary" href="#" id="aimt-translate-btn">' . esc_html__('Translate Now', 'aimt') . '</a></p>';
            echo '<p class="description" style="margin-top:8px;">' . esc_html__('Translation will use your configured languages.', 'aimt') . '</p>';
        } else {
            echo '<p>' . esc_html__('Translation option is not active for this post. Save as Draft or Publish to enable.', 'aimt') . '</p>';
        }
    }

    private function get_configured_post_type_slugs()
    {
        $selected_raw = (array) get_option('aimt_selected_post_types', array());
        if (empty($selected_raw)) {
            $selected_raw = (array) get_option('aimt_translatable_post_types', array());
        }

        $registered = get_post_types(array(), 'objects');
        $allowed = array();

        foreach ($selected_raw as $v) {
            $v = sanitize_text_field($v);
            // direct slug match
            if (isset($registered[$v])) {
                $allowed[] = $v;
                continue;
            }
            // match against labels
            foreach ($registered as $slug => $obj) {
                $labels = array_map('strtolower', [
                    $slug,
                    $obj->label ?? '',
                    $obj->labels->name ?? '',
                    $obj->labels->singular_name ?? '',
                ]);
                if (in_array(strtolower($v), $labels, true)) {
                    $allowed[] = $slug;
                    break;
                }
            }
        }

        $allowed = array_values(array_unique(array_filter($allowed)));
        // Always include 'page' as it's enabled by default (not shown in config)
        if (!in_array('page', $allowed, true)) {
            $allowed[] = 'page';
        }
        return !empty($allowed) ? $allowed : array('post', 'page');
    }

    public function aimt_clear_alert_flag()
    {
        check_ajax_referer('aimt_alert_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        delete_post_meta($post_id, '_aimt_show_alert');

        wp_send_json_success();
    }

    public function save_onboarding_state()
    {
        check_ajax_referer('aimt_configration_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $state = isset($_POST['state']) ? json_decode(stripslashes($_POST['state']), true) : null;

        if ($state) {
            $sanitized_state = $this->sanitize_onboarding_state($state);

            update_option('aimt_configration_state', $sanitized_state, false);

            $this->save_individual_options($sanitized_state);

            wp_send_json_success(array(
                'message' => 'State saved successfully',
                'state' => $sanitized_state
            ));
        } else {
            wp_send_json_error('Invalid state data');
        }
    }

    public function load_onboarding_state()
    {
        check_ajax_referer('aimt_configration_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $state = get_option('aimt_configration_state', array());

        wp_send_json_success(array(
            'state' => $state,
            'exists' => !empty($state)
        ));
    }

    public function clear_onboarding_state()
    {
        check_ajax_referer('aimt_configration_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        delete_option('aimt_configration_state');

        $this->clear_individual_options();

        wp_send_json_success('State cleared successfully');
    }

    private function sanitize_onboarding_state($state)
    {
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
                if ($clean)
                    $sanitized['postTypes'][] = $clean;
            }
        } else {
            if (!empty($state['postType'])) {
                $clean = sanitize_text_field($state['postType']);
                if ($clean)
                    $sanitized['postTypes'][] = $clean;
            }
        }

        $sanitized['wpmlKey'] = sanitize_text_field($state['wpmlKey'] ?? '');
        $sanitized['translationMode'] = sanitize_text_field($state['translationMode'] ?? '');

        return $sanitized;
    }

    private function save_individual_options($state)
    {
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

    private function clear_individual_options()
    {
        delete_option('aimt_default_languages');
        delete_option('aimt_translation_languages');
        delete_option('aimt_selected_post_types');
        // delete_option('aimt_url_format');
        delete_option('aimt_translation_mode');
        delete_option('aimt_wpml_key');
        // removed delete_option('aimt_plugin_options');
    }

    public function get_onboarding_config()
    {
        $state = get_option('aimt_configration_state', array());

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

    public function is_onboarding_complete()
    {
        $state = $this->get_onboarding_config();
        return !empty($state['selectedLanguages']) &&
            !empty($state['translationLanguages']) &&
            !empty($state['postTypes']) &&
            !empty($state['translationMode']);
    }

    public function register_menu()
    {
        // add_menu_page(
        //     'String-translations ',
        //     'String-translation',
        //     'manage_options',
        //     'aimt-settings',
        //     array($this, 'settings_page'),
        //     'dashicons-translation',
        //     80
        // );
        // add_submenu_page(
        //     'aimt-settings',
        //     'Configrations',
        //     'Configrations',
        //     'manage_options',
        //     'aimt-configrations',
        //     array($this, 'configration_page')
        // );
        add_menu_page(
            'Configrations',
            'AI Multi-Lang Config',
            'manage_options',
            'aimt-configrations',
            array($this, 'configration_page'),
            'dashicons-admin-generic',
            80
        );
        add_submenu_page(
            'aimt-configrations',
            'String Translations',
            'String Translations',
            'manage_options',
            'aimt-settings',
            array($this, 'settings_page')
        );
    }

    public function assets($hook)
    {
        $is_onboarding = (strpos($hook, 'aimt-configrations') !== false);
        $is_settings = (strpos($hook, 'aimt-settings') !== false);

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
                'aimt-configrations-js',
                plugin_dir_url(__FILE__) . '../assets/js/configrations.js',
                array('jquery', 'aimt-bootstrap'),
                '1.0',
                true
            );
        }

        if ($is_onboarding || $is_settings) {
            wp_enqueue_style(
                'aimt-configrations-css',
                plugin_dir_url(__FILE__) . '../assets/css/onboarding.css',
                array(),
                '1.0'
            );
        }

        if ($is_onboarding) {
            wp_localize_script('aimt-configrations-js', 'aimtOnboardingData', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aimt_configration_nonce'),
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
                $available_target_codes = (array) get_option('aimt_translation_languages', array());
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
                    'ar' => 'Arabic'
                );

                $translation_langs = array();
                foreach ($available_target_codes as $code) {
                    $code = sanitize_text_field($code);
                    if (empty($code))
                        continue;
                    $translation_langs[$code] = isset($common_languages[$code]) ? $common_languages[$code] : strtoupper($code);
                }

                wp_localize_script('aimt-post-alert', 'aimtData', [
                    'show_alert' => get_post_meta($post->ID, '_aimt_show_alert', true),
                    'post_id' => $post->ID,
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('aimt_alert_nonce'),
                    'translation_languages' => $translation_langs,
                    'config_page_url' => admin_url('admin.php?page=aimt-configrations')
                ]);
            }
        }
    }

    public function configration_page()
    {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['aimt_configration_state']) &&
            isset($_POST['aimt_configration_nonce']) &&
            wp_verify_nonce($_POST['aimt_configration_nonce'], 'aimt_onboarding_save') &&
            current_user_can('manage_options')
        ) {
            $raw = wp_unslash($_POST['aimt_configration_state']);
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $sanitized = $this->sanitize_onboarding_state($decoded);
                // save full state and individual options (keeps data separated)
                update_option('aimt_configration_state', $sanitized);
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
        $all_post_types = get_post_types(array(), 'objects');
        // echo '<pre>';
        // var_dump($selected_post_types);
        // echo '</pre>';
        // $selected_post_types = get_option('aimt_selected_post_types', array());
        // echo '<pre>';
        // var_dump($selected_post_types, $translatable_post_types);
        // echo '</pre>';
        $blacklist = array('elementor_library', 'e-floating-buttons', 'page');
        foreach ($all_post_types as $pt) {
            if ($pt->name === 'attachment')
                continue;
            if (empty($pt->public) || empty($pt->publicly_queryable))
                continue;
            if (isset($pt->show_ui) && !$pt->show_ui)
                continue;
            if (in_array($pt->name, $blacklist, true))
                continue;
            $post_types[] = $pt;
        }

        $steps = array(
            'languages' => 'Languages',
            'register-multilang' => 'Register AI multi language translation',
            'translation-mode' => 'Translation Mode',
            'finished' => 'Finished'
        );

        $template_path = plugin_dir_path(__FILE__) . '../templates/html/onboarding-page.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><div class="notice notice-error"><p>Onboarding template not found: ' . esc_html($template_path) . '</p></div></div>';
        }

        /*
         * .
         *
         *  ?>
         *  <div class="wrap aimt-configrations">
         *    <h1 class="font-poppins text-center main-h">AI multi language translation</h1>
         *
         *      <div class="container mt-4">
         *          <div class="progress mb-5">
         *              <?php
         *              $step_count = count($steps);
         *              $step_width = 100 / $step_count;
         *              $i = 0;
         *              foreach ($steps as $key => $label):
         *                  $i++;
         *              ?>
         *                  <div class="progress-step" style="width: <?php echo $step_width; ?>%">
         *                      <div class="step-number <?php echo ($i === 1) ? 'active' : ''; ?>"><?php echo $i; ?></div>
         *                      <div class="step-label"><?php echo $label; ?></div>
         *                  </div>
         *              <?php endforeach; ?>
         *          </div>
         *          <div class="onboarding-steps">
         *              <div class="step-content step-languages active ">
         *                  <h2 class="text-center font-poppins">Languages</h2>
         *                  <p class="text-center font-poppins">Select the languages you want to support on your website.</p>
         *
         *                      <div class="languages-select mt-4">
         *              <label class="form-label">Default Languages</label>
         *
         *              <?php
         *              $common_languages = array(
         *                  'English' => 'en',
         *                  'Spanish' => 'es',
         *                  'French' => 'fr',
         *                  'German' => 'de',
         *                  'Italian' => 'it',
         *                  'Portuguese' => 'pt',
         *                  'Chinese' => 'zh',
         *                  'Japanese' => 'ja',
         *                  'Russian' => 'ru',
         *                  'Arabic' => 'ar'
         *              );
         *              ?>
         *
         *              <div class="dropdown language-dropdown">
         *                  <button
         *                      class="btn btn-light border dropdown-toggle btn-first"
         *                      type="button"
         *                      id="languagesDropdownBtn"
         *                      data-toggle="dropdown"
         *                      aria-haspopup="true"
         *                      aria-expanded="false"
         *                  >
         *                      English
         *                  </button>
         *
         *                  <div class="dropdown-menu p-2" aria-labelledby="languagesDropdownBtn">
         *                      <input
         *                          type="text"
         *                          class="form-control form-control-sm mb-2 language-search"
         *                          placeholder="Search language..."
         *                      >
         *
         *                      <div class="language-options">
         *                          <?php foreach ($common_languages as $name => $code): ?>
         *                              <div
         *                                  class="dropdown-item language-option"
         *                                  data-code="<?php echo esc_attr($code); ?>"
         *                                  data-name="<?php echo esc_attr($name); ?>"
         *                              >
         *                                  <?php echo esc_html($name); ?> (<?php echo strtoupper($code); ?>)
         *                              </div>
         *                          <?php endforeach; ?>
         *                      </div>
         *                  </div>
         *              </div>
         *
         *              <div class="selected-languages"></div>
         *              <small class="form-text text-muted mt-2">
         *                  Search and select multiple languages
         *              </small>
         *              <br>
         *          <label class="form-label mt-4">Translation Languages</label>
         *
         *          <div class="dropdown language-dropdown translation-language-dropdown">
         *              <button
         *                  class="btn btn-light border dropdown-toggle btn-first"
         *                  type="button"
         *                  id="translationLanguagesDropdownBtn"
         *                  data-toggle="dropdown"
         *                  aria-haspopup="true"
         *                  aria-expanded="false"
         *              >
         *                  Select translation languages
         *              </button>
         *
         *              <div class="dropdown-menu p-2" aria-labelledby="translationLanguagesDropdownBtn">
         *                  <input
         *                      type="text"
         *                      class="form-control form-control-sm mb-2 translation-language-search"
         *                      placeholder="Search language..."
         *                  >
         *
         *                  <div class="translation-language-options">
         *                      <?php foreach ($common_languages as $name => $code): ?>
         *                          <div
         *                              class="dropdown-item translation-language-option"
         *                              data-code="<?php echo esc_attr($code); ?>"
         *                              data-name="<?php echo esc_attr($name); ?>"
         *                          >
         *                              <?php echo esc_html($name); ?> (<?php echo strtoupper($code); ?>)
         *                          </div>
         *                      <?php endforeach; ?>
         *                  </div>
         *              </div>
         *          </div>
         *
         *                      <?php
         *                      $total_posts = 0;
         *                      foreach ($post_types as $post_type) {
         *                          if ($post_type->name === 'attachment') continue;
         *                          $count = wp_count_posts($post_type->name)->publish;
         *                          $total_posts += $count;
         *                      }
         *                      ?>
         *                     <label class="form-label mt-2">Select Post Type</label>
         *
         *                          <div class="dropdown language-dropdown post-type-dropdown mb-3">
         *                              <button
         *                                  class="btn btn-light border dropdown-toggle btn-first"
         *                                  type="button"
         *                                  id="postTypeDropdownBtn"
         *                                  data-toggle="dropdown"
         *                                  aria-haspopup="true"
         *                                  aria-expanded="false"
         *                              >
         *                                  Select Post Type
         *                              </button>
         *
         *                              <div class="dropdown-menu p-2" aria-labelledby="postTypeDropdownBtn">
         *                                  <input
         *                                      type="text"
         *                                      class="form-control form-control-sm mb-2 posttype-search"
         *                                      placeholder="Search post types..."
         *                                  >
         *
         *                                  <div class="post-type-options">
         *                                      <?php foreach ($post_types as $post_type): ?>
         *                                          <?php if ($post_type->name === 'attachment') continue; ?>
         *                                          <div
         *                                              class="dropdown-item post-type-option"
         *                                              data-post-type="<?php echo esc_attr($post_type->name); ?>"
         *                                              data-name="<?php echo esc_attr($post_type->labels->singular_name); ?>"
         *                                          >
         *                                              <?php echo esc_html($post_type->labels->singular_name); ?> (<?php echo esc_html($post_type->name); ?>)
         *                                          </div>
         *                                      <?php endforeach; ?>
         *                                  </div>
         *                              </div>
         *                          </div>
         *
         *                          <div class="selected-post-types"></div>
         *                          <div class="selected-translation-languages" data-required="true"></div>
         *          </div>
         *
         *                          <div class="mt-4">
         *                                  <button class="button button-primary next-step btn-next" data-next="register-multilang">Next</button>
         *                              </div>
         *                          </div>
         *                          <div class="step-content step-register-multilang">
         *                              <h2>Register AI multi language translation</h2>
         *                              <p>Connect your site to AI multi language translation for enhanced multilingual features.</p>
         *                              <div class="mt-4">
         *                                  <div class="form-group">
         *                                      <label for="wpml_key" class="font-poppins">AI multi language translation Registration Key</label>
         *                                      <input type="text" class="form-control" id="wpml_key" name="wpml_key" placeholder="Enter your AI multi language translation key" required>
         *                                      <small class="form-text text-muted">Get your key from your AI multi language translation account.</small>
         *                                  </div>
         *                              </div>
         *                              <div class="mt-4">
         *                                  <button class="button prev-step" data-prev="languages">Back</button>
         *                                  <button class="button button-primary next-step" data-next="translation-mode">Next</button>
         *                              </div>
         *                          </div>
         *                          <div class="step-content step-translation-mode">
         *                              <h2>How would you like to translate the content?</h2>
         *                               <div class="row mt-4">
         *                                   <div class="col-md-6 mb-4">
         *                                       <div class="card p-4 h-100">
         *                                           <h4>Translate Everything Automatically</h4>
         *                                           <p class="text-muted">Let AI Multilag do the translating for you</p>
         *                                           <ul class="features-list">
         *                                               <li>✓ Translates all your published content automatically</li>
         *                                               <li>✓ Uses machine translation powered by Google, Microsoft, or DeepL</li>
         *                                               <li>✓ Instantly translates new content and updates translations whenever you edit a page or post</li>
         *                                               <li>✓ You can review translations before publishing or hire professional reviewers</li>
         *                                               <li>✓ Affordable and fast</li>
         *                                           </ul>
         *                                           <button class="button button-primary btn-block choose-mode" data-mode="auto">Choose</button>
         *                                       </div>
         *                                   </div>
         *
         *                                   <div class="col-md-6 mb-4">
         *                                       <div class="card p-4 h-100">
         *                                           <h4>Translate What You Choose</h4>
         *                                           <p class="text-muted">You decide what to translate and who'll translate it</p>
         *                                           <ul class="features-list">
         *                                               <li>✓ Translate yourself</li>
         *                                               <li>✓ Use automatic translation on the content you choose</li>
         *                                               <li>✓ Work with translators that are users of your site</li>
         *                                               <li>✓ Send to professional translation services</li>
         *                                           </ul>
         *                                           <button class="button btn-block choose-mode" data-mode="manual">Choose</button>
         *                                       </div>
         *                                   </div>
         *                               </div>
         *
         *                               <div class="mt-4">
         *                                  <button class="button prev-step" data-prev="register-multilang">Back</button>
         *                                  <button class="button button-primary next-step" data-next="finished">Next</button>
         *                               </div>
         *                           </div>
         *
         *                           <div class="step-content step-finished text-center">
         *                               <h2>🎉 Setup Complete!</h2>
         *                               <p class="lead">Your multilingual site is ready to go!</p>
         *
         *                              <div class="success-icon mb-4">
         *                                  <span class="dashicons dashicons-yes-alt" style="font-size: 80px; color: #46b450;"></span>
         *                              </div>
         *
         *                              <div class="summary mt-4 p-4 bg-light rounded">
         *                                  <h5>Summary:</h5>
         *                                  <p>All settings have been configured successfully.</p>
         *                                  <p>You can always modify these settings from the main settings page.</p>
         *                              </div>
         *
         *                              <div class="mt-4">
         *                                  <button class="button prev-step" data-prev="translation-mode">Back</button>
         *                                   <a href="<?php echo admin_url('admin.php?page=aimt-settings'); ?>" class="button button-primary">Go to Settings</a>
         *                                   <a href="<?php echo home_url(); ?>" class="button" target="_blank">Visit Site</a>
         *                               </div>
         *                           </div>
         *                       </div>
         *                   </div>
         *               </div>
         *              <?php
         *              ?>
         *
         *  <script type="text/javascript">
         *  (function($){
         *    // Validation is now handled in configrations.js
         *    // Keeping this script block for any future inline scripts if needed
         *  })(jQuery);
         *  </script>
         *
         *  <?php
         */
    }

    public function settings_page()
    {
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

        $available_source_codes = (array) get_option('aimt_default_languages', array());
        $available_target_codes = (array) get_option('aimt_translation_languages', array());

        if (!empty($available_source_codes)) {
            $source_options = array_intersect_key($common_languages, array_flip($available_source_codes));
        } else {
            $source_options = $common_languages;
        }

        if (!empty($available_target_codes)) {
            $target_options = array_intersect_key($common_languages, array_flip($available_target_codes));
        } else {
            $target_options = $common_languages;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can('manage_options')) {
            if (isset($_POST['action']) && $_POST['action'] === 'aimt_add_translation') {
                if (!wp_verify_nonce($_POST['aimt_add_translation_nonce'] ?? '', 'aimt_add_translation')) {
                    echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                } else {
                    $string = sanitize_textarea_field(wp_unslash($_POST['string'] ?? ''));
                    $lang = sanitize_text_field($_POST['lang'] ?? '');
                    $translated_lang = sanitize_text_field($_POST['translated_lang'] ?? '');
                    $translated_string = sanitize_textarea_field(wp_unslash($_POST['translated_string'] ?? ''));

                    if (empty($string) || empty($lang) || empty($translated_lang) || empty($translated_string)) {
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
                            array('%s', '%s', '%s', '%s', '%s', '%s')
                        );

                        echo '<div class="notice notice-success"><p>Translation added.</p></div>';
                    }
                }
            }

            if (isset($_POST['action']) && $_POST['action'] === 'aimt_edit_translation') {
                if (!wp_verify_nonce($_POST['aimt_edit_translation_nonce'] ?? '', 'aimt_edit_translation')) {
                    echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                } else {
                    $id = intval($_POST['id'] ?? 0);
                    if ($id > 0) {
                        $string = sanitize_textarea_field(wp_unslash($_POST['string'] ?? ''));
                        $lang = sanitize_text_field($_POST['lang'] ?? '');
                        $translated_lang = sanitize_text_field($_POST['translated_lang'] ?? '');
                        $translated_string = sanitize_textarea_field(wp_unslash($_POST['translated_string'] ?? ''));

                        if (empty($string) || empty($lang) || empty($translated_lang) || empty($translated_string)) {
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
                                array('id' => $id),
                                array('%s', '%s', '%s', '%s', '%s'),
                                array('%d')
                            );

                            echo '<div class="notice notice-success"><p>Translation updated.</p></div>';
                        }
                    }
                }
            }

            if (isset($_POST['action']) && $_POST['action'] === 'aimt_delete_translation') {
                if (!wp_verify_nonce($_POST['aimt_delete_translation_nonce'] ?? '', 'aimt_delete_translation')) {
                    echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                } else {
                    $id = intval($_POST['id'] ?? 0);
                    if ($id > 0) {
                        $wpdb->delete($table_name, array('id' => $id), array('%d'));
                        echo '<div class="notice notice-success"><p>Translation deleted.</p></div>';
                    }
                }
            }
        }

        $edit_row = null;
        if (isset($_GET['edit_translation'])) {
            $edit_id = intval($_GET['edit_translation']);
            if ($edit_id > 0) {
                $edit_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d LIMIT 1", $edit_id), ARRAY_A);
            }
        }

        $translations = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY id DESC", ARRAY_A);

        $template_path = plugin_dir_path(__FILE__) . '../templates/html/settings-page.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><div class="notice notice-error"><p>Settings template not found: ' . esc_html($template_path) . '</p></div></div>';
        }
    }
}
