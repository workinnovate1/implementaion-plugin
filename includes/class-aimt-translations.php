<?php
//string translation
//meta data
//create transaltion of meta data

class AIMT_Translations
{

    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_translation_meta_box')); // Add meta box to post and page edior
        add_action('save_post', array($this, 'save_translation_meta'), 10, 2);  //Hook to page/past save to store translation
    }

    public function add_translation_meta_box()
    {
        add_meta_box(
            'aimt_translation_meta_box',      //id
            'Translation Options',              //title
            array($this, 'render_meta_box'),    //callback
            ['post', 'page'],                  //post type
            'normal',                          //context
            'high'                             //priority
        );
    }


    public function render_meta_box($post)
    {
        $languages = get_option('aimt_default_languages', array('en', 'fr', 'de'));
        global $wpdb;
        $table_name = $wpdb->prefix . 'aimt_translations';
?>
        <div id="aimt-translation-modal" style="border:1px solid #ccc; padding:15px; background:#f9f9f9;">
            <h4>Translation Option</h4>
            <ul>
                <?php foreach ($languages as $lang) :
                    $existing = $wpdb->get_var($wpdb->prepare(     //ya translation fetch kr raha hy agr koi b hy
                        "SELECT translation_text FROM $table_name WHERE post_id=%d and language_code=%s",
                        $post->ID,
                        $lang
                    )); ?>
                    <li><?php echo esc_html($lang); ?></li>
                    <textarea name="aimt_translation[<?php echo esc_attr($lang); ?>]" style="width:100%;" rows="2"><?php echo ($existing); ?></textarea>
                <?php endforeach; ?>
            </ul>
        </div>
<?php
        //security nonce
        wp_nonce_field('aimt_save_translation', 'aimt_translation_nonce');
    }
    //ya function transaltion save kr raha hy jab post save hogi
    public function save_translation_meta($post_id, $post)
    {
        //verify nonce
        if (!isset($_POST['aimt_translation_nonce']) || !wp_verify_nonce($_POST['aimt_translation_nonce'], 'aimt_save_translation')) return;
        //ya function autosave ko rokny ky liya hy 
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        //ya function permission chekc kr raha hy
        if (!current_user_can('edit_post', $post_id)) return;

        //ya function check kr raha hy if translation exist
        if (!isset($_POST['aimt_translation']) || !is_array($_POST['aimt_translation']))  return;

        global $wpdb;
        $table_name = $wpdb->prefix . 'aimt_translations';

        //Har lanugage ko loop krein or transltion add or update kry 
        foreach ($_POST['aimt_translation'] as $lang => $text) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE post_id=%d AND  language_code=%s",
                $post_id,
                $lang
            ));
            if ($exists) {
                //ya function update kry ga existing translation
                $wpdb->update($table_name, ['translation_text' => $text], ['id' => $exists], ['%s'], ['%d']);
            } else {
                $wpdb->insert(
                    $table_name,
                    [
                        'post_id' => $post_id,
                        'language_code' => $lang,
                        'translation_text' => $text
                    ],
                    ['%d ', '%s', '%s']
                );
            };
        }
    }
}
