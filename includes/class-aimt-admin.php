<?php

class AIMT_Admin {
     public function __construct(){
          add_action('admin_menu' , array($this , 'register_menu'));
          add_action('admin_enqueue_scripts', array($this, 'assets'));

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
         add_submenu_page(
            'aimt-settings',
            'Onboarding',
            'Onboarding',
            'manage_options',
            'aimt-onboarding',
            array($this, 'onboarding_page')
        );
        
     }
         public function assets($hook) {
        if ($hook !== 'ai-multi-language_page_aimt-onboarding') {
            return;
        }
        wp_enqueue_style(
        'aimt-onboarding-css',
        plugin_dir_url(__FILE__) . '../assets/css/onboarding.css',
        array(),
        '1.0'
    );

    wp_enqueue_script(
        'aimt-onboarding-js',
        plugin_dir_url(__FILE__) . '../assets/js/onboarding.js',
        array('jquery'),
        '1.0',
        true
    );
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
    }

        public function onboarding_page() {
    $steps = array(
        'languages' => 'Languages',
        'url-format' => 'URL Format',
        'register-multilang' => 'Register MLI',
        'translation-mode' => 'Translation Mode',
        'support' => 'Support',
        'plugins' => 'Plugins',
        'finished' => 'Finished'
    );
    ?>
    <div class="wrap aimt-onboarding">
      <h1 class="font-poppins text-center main-h">Multilang-implementaion</h1>

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

            <div class="selected-translation-languages"></div>
                    
            </div>


                                
                                <div class="mt-4">
                                    <button class="button button-primary next-step btn-next" data-next="url-format">Next</button>
                                </div>
                            </div>

                            <div class="step-content step-url-format">
                                <h2 class="font-poppins">How would You like to format your site's URL?</h2>
                                <!-- <p>Choose how language codes appear in your URLs.</p> -->
                                
                                <div class="url-options mt-4">
                                    <div class="form-check mb-3">
                                        <input style="margin-top:8px"  class="form-check-input" type="radio" name="url_format" id="format-subdirectory" value="subdirectory" checked>
                                        <label  style="margin-left: 24px"  class="form-check-label" for="format-subdirectory">
                                            <strong class="font-poppins">Different Languages in directore</strong><br>
                                            <small>implementaion.com/fr/about-us/</small>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input style="margin-top:8px" class="form-check-input" type="radio" name="url_format" id="format-subdomain" value="subdomain">
                                        <label style="margin-left: 24px"  class="form-check-label" for="format-subdomain">
                                            <strong class="font-poppins" >A different domain per language</strong><br>
                                            <small>fr.implementaion.com/about-us/</small>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input style="margin-top:8px"  class="form-check-input" type="radio" name="url_format" id="format-parameter" value="parameter">
                                        <label style="margin-left: 24px"  class="form-check-label" for="format-parameter">
                                            <strong class="font-poppins">langauge name added as a parameter</strong><br>
                                            <small>spanish: implementaion.com/about-us/?lang=fr</small>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button class="button prev-step" data-prev="languages">Back</button>
                                    <button class="button button-primary next-step" data-next="register-multilang">Next</button>
                                </div>
                            </div>

                            <div class="step-content step-register-multilang">
                                <h2>Register MLI</h2>
                                <p>Connect your site to MLI for enhanced multilingual features.</p>
                                
                                <div class="mt-4">
                                    <div class="form-group">
                                        <label for="wpml_key" class="font-poppins">MLI Registration Key</label>
                                        <input type="text" class="form-control" id="wpml_key" placeholder="Enter your WPML key">
                                        <small class="form-text text-muted">Get your key from <a href="https://mli.org" target="_blank">MLI.org</a></small>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button class="button prev-step" data-prev="url-format">Back</button>
                                    <button class="button button-primary next-step" data-next="translation-mode">Next</button>
                                </div>
                            </div>

                            <div class="step-content step-translation-mode">
                                <h2>How would you like to translate the content?</h2>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6 mb-4">
                                        <div class="card p-4 h-100">
                                            <h4>Translate Everything Automatically</h4>
                                            <p class="text-muted">Let WPML do the translating for you</p>
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
                                    <button class="button button-primary next-step" data-next="support">Next</button>
                                </div>
                            </div>

                            <div class="step-content step-support">
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
                            </div>

                            <div class="step-content step-plugins">
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
                                    <button class="button prev-step" data-prev="plugins">Back</button>
                                    <a href="<?php echo admin_url('admin.php?page=aimt-settings'); ?>" class="button button-primary">Go to Settings</a>
                                    <a href="<?php echo home_url(); ?>" class="button" target="_blank">Visit Site</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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