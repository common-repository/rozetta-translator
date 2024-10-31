<?php
/**
 * Plugin Name:       Rozetta Translator for WordPress
 * Description:       Translator Plugin provided by Rozetta.
 * Requires at least: 5.7
 * Requires PHP:      7.0
 * Version:           1.1.8
 * Author:            Rozetta Co.
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rozetta-translator
 * Domain Path:		  /languages/
 *
 * @package           rozetta-translator
 */

require dirname( __FILE__ ) . '/admin/admin.php';
require dirname( __FILE__ ) . '/admin/includes/api.php';
require dirname( __FILE__ ) . '/admin/includes/config.php';
require dirname( __FILE__ ) . '/admin/includes/multilingual-config.php';
require dirname( __FILE__ ) . '/admin/includes/editor.php';
require dirname( __FILE__ ) . '/admin/includes/post.php';
require dirname( __FILE__ ) . '/includes/function.php';
require dirname( __FILE__ ) . '/includes/widgets.php';
require dirname( __FILE__ ) . '/includes/utils.php';

function rozetta_load_textdomain() {
    load_plugin_textdomain( 'rozetta-translator', false, dirname(plugin_basename(__FILE__ )) . '/languages' );
}
add_action('plugins_loaded', 'rozetta_load_textdomain');

function rozetta_register_script() {
    wp_register_style('admin_style', plugins_url('admin/includes/css/admin.css', __FILE__));

	wp_register_script(
        'rozetta-block-editor',
        plugins_url('admin/includes/block-editor/index.js', __FILE__),
        array(
            'wp-components',
            'wp-data',
            'wp-edit-post',
            'wp-element',
            'wp-plugins',
            'wp-i18n'
        )
    );
    wp_register_script(
        'translation-script',
        plugins_url('admin/includes/js/translate.js', __FILE__),
        array( 'jquery' )
    );
    wp_register_script(
        'multilingual-script',
        plugins_url('admin/includes/js/multilingual.js', __FILE__),
        array( 'jquery' )
    );
    wp_register_script(
        'post-script',
        plugins_url('admin/includes/js/post.js', __FILE__),
        array( 'jquery' )
    );
    if (function_exists('get_field')) {
        wp_register_script(
            'translation-acf-script',
            plugins_url('admin/includes/js/translate-acf.js', __FILE__),
            array( 'jquery' )
        );
    }
}
add_action('init', 'rozetta_register_script' );


function rozetta_register_meta() {
	if (RozettaMultilingual::get_is_multilingaul_enable()) {
        $customPostType = RozettaTranslator::get_custom_post_type();
        $customPostTypeArr = preg_split('/[,]/', $customPostType, -1, PREG_SPLIT_NO_EMPTY);
        
        $auth_callback = function ( $allowed, $meta_key, $object_id, $user_id ) {
            return user_can( $user_id, 'edit_post', $object_id );
        };
        foreach (array_merge(array('post', 'page'), $customPostTypeArr) as $postType) {
            register_post_meta(
                $postType,
                '_rozetta_locale',
                [
                    'show_in_rest' => true,
                    'single'       => true,
                    'type'         => 'string',
                    'auth_callback' => $auth_callback,
                ]
            );
            register_post_meta(
                $postType,
                '_rozetta_original_post_id',
                [
                    'show_in_rest' => true,
                    'single'       => true,
                    'type'         => 'string',
                    'auth_callback' => $auth_callback,
                ]
            );
        }
    }
}
add_action('init', 'rozetta_register_meta' );

add_action('init', ['RozettaTranslator', 'init']);
add_action('init', ['RozettaMultilingual', 'init']);



add_filter( 'locale', 'rozetta_set_locale', 10, 1 );

function rozetta_set_locale( $locale ) {
	if ( isset( $_GET['lang'] ) ) {
		$locale = $_GET['lang'];
	}

	return $locale;
}

add_filter('page_link', 'append_query_string', 10, 2);

function append_query_string($url, $post) {
    $current_original_post_id =  get_post_meta($post, '_rozetta_original_post_id', true);
    $current_locale =  get_post_meta($post, '_rozetta_locale', true);
    $frontpage_id = get_option('page_on_front');
    if ($frontpage_id === $current_original_post_id) {
        return add_query_arg('lang', $current_locale, get_home_url());
    }
    return $url;
}

function front_page_request( $query ) {
    if ($query->get('page_id') === get_option('page_on_front') && $_GET['lang']) {
        $meta_query = array(
          array(
            'key'     => '_rozetta_original_post_id',
            'value'   => $query->get('page_id'),
          ),
        );
        $query->set('meta_query', $meta_query);
        $query->set('page_id', 0);
    }
}
add_action( 'pre_get_posts', 'front_page_request' );

?>
