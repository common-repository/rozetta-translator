<?php
add_action( 'add_meta_boxes', 'add_rozetta_translator_meta_boxes', 10, 3 );

function add_rozetta_translator_meta_boxes( $post_type, $post ) {
    $customPostType = RozettaTranslator::get_custom_post_type();
    $customPostTypeArr = preg_split('/[,]/', $customPostType, -1, PREG_SPLIT_NO_EMPTY);
    
	add_meta_box( 'rozetta_translator_meta_boxes', __( 'Rozetta Translator', 'rozetta-translator' ),
		'rozetta_translator_meta_boxes',
        array_merge(array('post', 'page'), $customPostTypeArr),
        'side', 'high',
		array(
			'__back_compat_meta_box' => true,
        )
	);
}
function rozetta_translator_meta_boxes() {
    $current_original_post_id =  get_post_meta(get_the_ID(), '_rozetta_original_post_id', true);
    $current_locale =  get_post_meta(get_the_ID(), '_rozetta_locale', true);

    if (!empty(get_post(get_the_ID())->post_parent)) {
        $type = get_post(get_the_ID())->post_type;
    }
    ?>
    <div class='misc-pub-section rozetta-metabox'>
    <?php
        if (RozettaMultilingual::get_is_multilingaul_enable()) {
    ?>
        <div class='rozetta-post-lang'>
            <?php _e('Post Language:', 'rozetta-translator')?>
            <?php
                if (get_post_meta(get_the_ID(), '_rozetta_locale', true)) {
                    print __(rozetta_get_languages_array()[get_post_meta(get_the_ID(), '_rozetta_locale', true)], 'rozetta-translator');
                ?>
                    <input type='hidden' value='<?php print get_post_meta(get_the_ID(), '_rozetta_locale', true);?>' name='postLang'>
                <?php 
                } else {
                    print __(rozetta_get_languages_array()[get_locale()], 'rozetta-translator');
                    ?>
                        <input type='hidden' value='<?php print get_locale();?>' name='postLang'>
                    <?php 
                /*?>
                    <select id='rozettaPostLang' class='rozetta-metabox-select-lang' placeholder='<?php _e('Original Language', 'rozetta-translator')?>' onChange='selectPostLang()' name='postLang'>
                        <?php
                            foreach ( RozettaTranslator::get_actived_langs() as $activeLang ) {
                                print '<option value="' . $activeLang . '" ' . (get_post_meta(get_the_ID(), '_rozetta_locale', true) == $activeLang ? ' selected ' : '') . '>' . $activeLang . '</option>';
                            }
                        ?>
                    </select>
                <?php */
                }  
                
            ?>
        </div>
        <hr>
        
    <?php
        }
    ?>
        <div id='rozetta-translator-setting-before'>
			<select id='rozettaFieldId' class='rozetta-metabox-select' placeholder='<?php _e('Specialized Field', 'rozetta-translator')?>' name='fieldId'></select>
            <div class='rozetta-lang-set'>
                <select id='rozettaSourceLang' class='rozetta-metabox-select-lang' placeholder='<?php _e('Original Language', 'rozetta-translator')?>' name='sourceLang'></select>
                >
                <select id='rozettaTargetLang' class='rozetta-metabox-select-lang' placeholder='<?php _e('Translated Language', 'rozetta-translator')?>' name='targetLang'></select>
            </div>
            <button type='button' class='button' onclick='handleRozettaTranslate()'><?php _e('Translate', 'rozetta-translator')?></button>
        </div>
        <div id='rozetta-translator-setting-after' class='rozetta-translator-after' style='display: none;'>
            <?php _e('Translation Success!', 'rozetta-translator')?>
            <button type='button' class='button' onclick='handleBack()'><?php _e('Undo', 'rozetta-translator')?></button>
        </div>
        <p id='rozetta-translator-status-translating' style='display: none;'><?php _e('Translating...', 'rozetta-translator')?></p>
        <p id='rozetta-translator-error-message' style='color: red;'></p>
    <?php
        if (RozettaMultilingual::get_is_multilingaul_enable()) {
    ?>    
        <hr>
        <div class='rozetta-add-post'>
            <div>
                <?php
                    _e('Posts in other languages:', 'rozetta-translator');
                    $locale_existed_array = array();
                    $contnetArray = get_posts(array('post_status' => array('publish', 'pending', 'draft', 'future', 'private'), 'post_type' => get_post()->post_type, 'nopaging' => true));
                    if (get_post()->post_type == 'page') {
                        $contnetArray = get_pages(array('post_status' => array('publish', 'pending', 'draft', 'future', 'private'), 'post_type' => get_post()->post_type, 'nopaging' => true));
                    }
                    foreach ($contnetArray as $post) {
                        //if (isset(get_post_meta($post->ID)['_rozetta_original_post_id'])) {
                            $original_post_id = get_post_meta($post->ID)['_rozetta_original_post_id'][0];
                            if ((get_the_ID() == $post->ID || $original_post_id != '') && $original_post_id == $current_original_post_id || get_the_ID() == $original_post_id || $post->ID == $current_original_post_id) {
                                if (get_the_ID() != $post->ID) {
                                    ?>
                                        <div><a href="<?php print get_edit_post_link($post->ID); ?>"><?php print __(rozetta_get_languages_array()[get_post_meta($post->ID, '_rozetta_locale', true)], 'rozetta-translator'); ?></a></div>
                                    <?php
                                }
                                array_push($locale_existed_array, get_post_meta($post->ID, '_rozetta_locale', true));
                            }
                        //}
                    }
                ?>
            </div>
        <hr>
            <?php
                $locale_unexisted_array = array_diff(RozettaMultilingual::get_actived_langs(), $locale_existed_array);
                if (count($locale_unexisted_array) > 0) {
                    _e('Add Post in Language:', 'rozetta-translator');
                ?>
                    <select id='rozettaNewPostLang' class='rozetta-metabox-select-lang' placeholder='<?php _e('Original Language', 'rozetta-translator')?>' onChange='selectNewPostLang()' name='newPostLang'>
                        <?php
                            foreach ( $locale_unexisted_array as $activeLang ) {
                                if (!in_array($activeLang, $locale_existed_array)) {
                                    print '<option value="' . $activeLang . '">' . __(rozetta_get_languages_array()[$activeLang], 'rozetta-translator') . '</option>';
                                }
                            }
                        ?>
                    </select>
                    <button id='rozettaNewPostButton' type='button' class='button' onclick='handleAddPost()'><?php _e('Add Post', 'rozetta-translator')?></button>
                <?php
                }
            ?>
        </div>
        <?php
            if (RozettaMultilingual::get_is_multilingaul_enable() && $current_original_post_id && $current_original_post_id !== get_the_ID()) {
        ?>    
            <hr>
            <div class='rozetta-add-post'>
                <button id='rozettaCopyPostButton' type='button' class='button' onclick='copyFromACFFields("<?=$current_original_post_id?>", "<?=get_the_ID()?>", "<?php print __("Are you sure to copy the Customized Field to current post from Original Post?", "rozetta-translator"); ?>")'><?php _e('Copy Customized Field', 'rozetta-translator')?></button>
            </div>
        <?php
            }
        ?>
    <?php
        }
    ?>
    </div>
    <?php
}

?>