<?php defined('ABSPATH') || exit; ?>

<div class="wrap aimt-settings-page">
    <div class="aimt-settings-header">
        <div class="aimt-settings-header-content">
            <h1>AI Multi-Language Translation</h1>
            <p class="aimt-subtitle">Manage string-level translations stored by the plugin.</p>
        </div>
    </div>

    <div class="aimt-settings-container">
        <div class="aimt-form-panel">
            <div class="aimt-form-card">
                <div class="aimt-form-header">
                    <?php if ($edit_row): ?>
                        <h2>Edit Translation</h2>
                        <span class="aimt-form-id">#<?php echo esc_html($edit_row['id']); ?></span>
                    <?php else: ?>
                        <h2>Add New Translation</h2>
                    <?php endif; ?>
                </div>
                <p class="aimt-form-subtitle">
                    Create or update a translation entry. These values are used when rendering multilingual strings on
                    your site.
                </p>

                <div class="aimt-form">
                    <?php if ($edit_row): ?>
                        <form method="post">
                            <?php wp_nonce_field('aimt_edit_translation', 'aimt_edit_translation_nonce'); ?>
                            <input type="hidden" name="action" value="aimt_edit_translation">
                            <input type="hidden" name="id" value="<?php echo esc_attr($edit_row['id']); ?>">

                            <div class="aimt-form-group">
                                <label class="aimt-form-label">Original string (source)</label>
                                <textarea name="string" rows="3"
                                    class="aimt-form-control"><?php echo esc_textarea($edit_row['string']); ?></textarea>
                            </div>

                            <div class="aimt-form-row">
                                <div class="aimt-form-group">
                                    <label class="aimt-form-label">Source language</label>
                                    <select name="lang" class="aimt-form-control">
                                        <?php foreach ($source_options as $code => $label): ?>
                                            <option value="<?php echo esc_attr($code); ?>" <?php selected($edit_row['lang'] ?? '', $code); ?>>
                                                <?php echo esc_html("{$label} ({$code})"); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="aimt-form-group">
                                    <label class="aimt-form-label">Translated language</label>
                                    <select name="translated_lang" class="aimt-form-control">
                                        <?php foreach ($target_options as $code => $label): ?>
                                            <option value="<?php echo esc_attr($code); ?>" <?php selected($edit_row['translated_lang'] ?? '', $code); ?>>
                                                <?php echo esc_html("{$label} ({$code})"); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="aimt-form-group">
                                <label class="aimt-form-label">Translated string</label>
                                <textarea name="translated_string" rows="3"
                                    class="aimt-form-control"><?php echo esc_textarea($edit_row['translated_string']); ?></textarea>
                            </div>

                            <div class="aimt-form-actions">
                                <button type="submit" class="button button-primary aimt-btn-primary">Save
                                    Translation</button>
                                <a class="button aimt-btn-secondary"
                                    href="<?php echo admin_url('admin.php?page=aimt-settings'); ?>">Cancel</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <form method="post">
                            <?php wp_nonce_field('aimt_add_translation', 'aimt_add_translation_nonce'); ?>
                            <input type="hidden" name="action" value="aimt_add_translation">

                            <div class="aimt-form-group">
                                <label class="aimt-form-label">Original string (source)</label>
                                <textarea name="string" rows="3"
                                    class="aimt-form-control"><?php echo isset($_POST['string']) ? esc_textarea($_POST['string']) : ''; ?></textarea>
                            </div>

                            <div class="aimt-form-row">
                                <div class="aimt-form-group">
                                    <label class="aimt-form-label">Source language</label>
                                    <select name="lang" class="aimt-form-control">
                                        <?php foreach ($source_options as $code => $label): ?>
                                            <option value="<?php echo esc_attr($code); ?>">
                                                <?php echo esc_html("{$label} ({$code})"); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="aimt-form-group">
                                    <label class="aimt-form-label">Translated language</label>
                                    <select name="translated_lang" class="aimt-form-control">
                                        <?php foreach ($target_options as $code => $label): ?>
                                            <option value="<?php echo esc_attr($code); ?>">
                                                <?php echo esc_html("{$label} ({$code})"); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="aimt-form-group">
                                <label class="aimt-form-label">Translated string</label>
                                <textarea name="translated_string" rows="3"
                                    class="aimt-form-control"><?php echo isset($_POST['translated_string']) ? esc_textarea($_POST['translated_string']) : ''; ?></textarea>
                            </div>

                            <div class="aimt-form-actions">
                                <button type="submit" class="button button-primary aimt-btn-primary">Add
                                    Translation</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="aimt-info-panel">
            <div class="aimt-info-card">
                <div class="aimt-info-header">
                    <h3>Notes</h3>
                </div>
                <div class="aimt-info-content">
                    <div class="aimt-info-section">
                        <p class="aimt-info-text">
                            Translations are stored in the
                            <span class="aimt-code"><?php echo esc_html($table_name); ?></span>
                            table.
                        </p>
                    </div>
                    <div class="aimt-info-section">
                        <p class="aimt-info-text">
                            You can edit or delete existing entries from the table below.
                        </p>
                    </div>
                    <div class="aimt-info-section">
                        <p class="aimt-info-text">
                            Changes here take effect immediately wherever these strings are rendered.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="aimt-table-section">
        <div class="aimt-table-header">
            <h2>All Translations</h2>
            <span class="aimt-table-count">
                <?php echo intval(count($translations)); ?> entries
            </span>
        </div>
        <div class="aimt-table-wrapper">
            <table class="widefat fixed striped aimt-table">
                <thead>
                    <tr>
                        <th class="aimt-col-id">ID</th>
                        <th>Original String</th>
                        <th class="aimt-col-source">Source (code)</th>
                        <th class="aimt-col-target">Target (code)</th>
                        <th>Translated String</th>
                        <th class="aimt-col-created">Created</th>
                        <th class="aimt-col-updated">Updated</th>
                        <th class="aimt-col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($translations)): ?>
                        <?php foreach ($translations as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row['id']); ?></td>
                                <td><?php echo esc_html($row['string']); ?></td>
                                <td>
                                    <span class="aimt-badge aimt-badge-code">
                                        <?php echo esc_html($row['lang']); ?>
                                    </span>
                                    <span class="aimt-badge-label">
                                        <?php echo esc_html($common_languages[$row['lang']] ?? ''); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="aimt-badge aimt-badge-code">
                                        <?php echo esc_html($row['translated_lang']); ?>
                                    </span>
                                    <span class="aimt-badge-label">
                                        <?php echo esc_html($common_languages[$row['translated_lang']] ?? ''); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($row['translated_string']); ?></td>
                                <td><?php echo esc_html($row['created_at']); ?></td>
                                <td><?php echo esc_html($row['updated_at']); ?></td>
                                <td>
                                    <a class="button aimt-btn-edit"
                                        href="<?php echo esc_url(add_query_arg('edit_translation', intval($row['id']), admin_url('admin.php?page=aimt-settings'))); ?>">Edit</a>
                                    <form method="post" style="display:inline-block;margin:0 0 0 6px;"
                                        onsubmit="return confirm('Delete translation #<?php echo esc_attr($row['id']); ?>?');">
                                        <?php wp_nonce_field('aimt_delete_translation', 'aimt_delete_translation_nonce'); ?>
                                        <input type="hidden" name="action" value="aimt_delete_translation">
                                        <input type="hidden" name="id" value="<?php echo esc_attr($row['id']); ?>">
                                        <input type="submit" class="button button-secondary aimt-btn-delete" value="Delete">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">No translations found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>