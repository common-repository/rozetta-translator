<?php
    function rozetta_set_env_info() {
        $lang = get_locale();
        wp_localize_script('rozetta-block-editor', 'envInfo ', 
            [
            'lang' => $lang,
            'isACFActive' => function_exists('get_field'),
            'isMultilingual' => RozettaMultilingual::get_is_multilingaul_enable(),
            ]
        );
        
        wp_localize_script('translation-script', 'envInfo ', 
            [
            'lang' => $lang,
            'isACFActive' => function_exists('get_field'),
            ]
        );
        
    }
    add_action('admin_init', 'rozetta_set_env_info', 20);

    function rozetta_load_admin_scripts($hook) {
        if (!get_current_screen()->is_block_editor()) {
            wp_enqueue_style('admin_style');
    
            if ($hook === 'rozetta-translator_page_rozetta-multilingual') {
                RozettaTranslator::set_contract_keys('multilingual-script');
                wp_enqueue_script('multilingual-script');
            }
    
            if ($hook === 'post.php' || $hook === 'post-new.php') {
                RozettaTranslator::set_contract_keys('translation-script');
                wp_enqueue_script('translation-script');
                
                print_r(get_the_id());
    
                wp_enqueue_script('post-script');
                wp_localize_script('post-script', 'postInfo ', 
                    [
                    'id' => get_the_id(),
                    'url' => get_site_url()
                    ]
                );
            }
            
            if (function_exists('get_field')) {
                ?><script>
                    //console.log(`<?php //print_r(get_post(get_the_id()));?>`);
                    //console.log(`<?php //print_r(get_post_meta(get_the_id()));?>`);
                    //console.log(`<?php //print_r(acf_get_field_groups(array('post_id' => get_the_id())));?>`);
                </script><?php
                wp_localize_script('translation-acf-script', 'acfInfo ', 
                    [
                    'acfGroups' => acf_get_field_groups(array('post_id' => get_the_id()))
                    ]
                );
                wp_enqueue_script('translation-acf-script');
            }
        }
    }
    add_action( 'admin_enqueue_scripts', 'rozetta_load_admin_scripts');

    
    function rozetta_enqueue_block_editor() {
        wp_set_script_translations('rozetta-block-editor', 'rozetta-translator', plugin_dir_path(__FILE__) . '../languages');
        
        RozettaTranslator::set_contract_keys('rozetta-block-editor');
        wp_enqueue_script('rozetta-block-editor');
    }
    add_action('enqueue_block_editor_assets','rozetta_enqueue_block_editor');


    function rozetta_save_locale($post_id, $post) {
        $postLang = '';
        if (isset($_POST['postLang'])) {
            $postLang =$_POST['postLang'];
        }
        if (RozettaMultilingual::get_is_multilingaul_enable() && $postLang != '') {
            $update_var = $_POST['postLang'];
            // $update_var = get_locale();
            // $update_var = get_post_meta($post_id, '_rozetta_locale', true );
        
            // add_post_meta($post_id, '_rozetta_locale', $update_var );
            update_post_meta($post_id, '_rozetta_locale', $update_var );
        }
    }
    add_action('save_post', 'rozetta_save_locale', 20, 2);
    
?>