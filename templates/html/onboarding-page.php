<?php defined('ABSPATH') || exit; ?>
<div class="wrap aimt-configrations">
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
                            aria-expanded="false">
                            English
                        </button>

                        <div class="dropdown-menu p-2" aria-labelledby="languagesDropdownBtn">
                            <input
                                type="text"
                                class="form-control form-control-sm mb-2 language-search"
                                placeholder="Search language...">

                            <div class="language-options">
                                <?php foreach ($common_languages as $name => $code): ?>
                                    <div
                                        class="dropdown-item language-option"
                                        data-code="<?php echo esc_attr($code); ?>"
                                        data-name="<?php echo esc_attr($name); ?>">
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
                            aria-expanded="false">
                            Select translation languages
                        </button>

                        <div class="dropdown-menu p-2" aria-labelledby="translationLanguagesDropdownBtn">
                            <input
                                type="text"
                                class="form-control form-control-sm mb-2 translation-language-search"
                                placeholder="Search language...">

                            <div class="translation-language-options">
                                <?php foreach ($common_languages as $name => $code): ?>
                                    <div
                                        class="dropdown-item translation-language-option"
                                        data-code="<?php echo esc_attr($code); ?>"
                                        data-name="<?php echo esc_attr($name); ?>">
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
                            aria-expanded="false">
                            Select Post Type
                        </button>

                        <div class="dropdown-menu p-2" aria-labelledby="postTypeDropdownBtn">
                            <input
                                type="text"
                                class="form-control form-control-sm mb-2 posttype-search"
                                placeholder="Search post types...">

                            <div class="post-type-options">
                                <?php foreach ($post_types as $post_type): ?>
                                    <?php if ($post_type->name === 'attachment') continue; ?>
                                    <div
                                        class="dropdown-item post-type-option"
                                        data-post-type="<?php echo esc_attr($post_type->name); ?>"
                                        data-name="<?php echo esc_attr($post_type->labels->singular_name); ?>">
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