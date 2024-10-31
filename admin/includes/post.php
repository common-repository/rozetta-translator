<?php
add_action('restrict_manage_posts', 'rozetta_restrict_manage_posts');
function rozetta_restrict_manage_posts(){
	if (RozettaMultilingual::get_is_multilingaul_enable()) {
        $type = 'post';
        if (isset($_GET['post_type'])) {
            $type = $_GET['post_type'];
        }
    
        $values = RozettaMultilingual::get_actived_langs();
        ?>
        <select name="rozetta_locale">
        <?php
            $current_v = isset($_GET['rozetta_locale']) ? $_GET['rozetta_locale'] : get_locale();
            foreach ($values as $lang) {
                printf('<option value="%s"%s>%s</option>', $lang, $lang == $current_v? ' selected="selected"':'', __(rozetta_get_languages_array()[$lang], 'rozetta-translator'));
            }
            ?>
        </select>
        <?php
    }
}

add_filter('parse_query', 'rozetta_posts_filter');
function rozetta_posts_filter($query){
	if (RozettaMultilingual::get_is_multilingaul_enable()) {
        global $pagenow;
        $type = 'post';
        if (isset($_GET['post_type'])) {
            $type = $_GET['post_type'];
        }

        $status = '';
        if (isset($_GET['post_status'])) {
            $status = $_GET['post_status'];
        }
        $rozetta_locale = 'all';
        if (isset($query->query['rozetta_locale'])) {
            $rozetta_locale = $query->query['rozetta_locale'];
        } else if (isset($_GET['rozetta_locale'])) {
            $rozetta_locale = $_GET['rozetta_locale'];
        }

        if ($status != 'trash') {
            if (is_admin() && $pagenow=='edit.php' && $rozetta_locale != 'all') {
                $meta_query = array(
                    'relation' => 'OR',
                    array(
                        'key' => '_rozetta_locale',
                        'compare' => '=',
                        'value'    => $rozetta_locale
                    ),
                    array(
                        'key' => '_rozetta_locale',
                        'compare' => 'NOT EXISTS',
                        'value'    => ''
                    ),
                    array(
                        'key' => '_rozetta_locale',
                        'compare' => '=',
                        'value' => ''
                    )
                );
                $query->set('meta_query', $meta_query);
            }
        
            if (is_admin() && $pagenow=='edit.php' && !isset($query->query['rozetta_locale']) && (!isset($_GET['rozetta_locale']) || !in_array($rozetta_locale, RozettaMultilingual::get_actived_langs()))) {
                $meta_query = array(
                    'relation' => 'OR',
                    array(
                        'key' => '_rozetta_locale',
                        'compare' => '=',
                        'value'    => get_locale()
                    ),
                    array(
                        'key' => '_rozetta_locale',
                        'compare' => 'NOT EXISTS',
                        'value'    => ''
                    ),
                    array(
                        'key' => '_rozetta_locale',
                        'compare' => '=',
                        'value' => ''
                    )
                );
                $query->set('meta_query', $meta_query);
            }
        }
    }
}

add_filter('manage_pages_columns', 'rozetta_manage_pages_column', 10, 1);
add_filter('manage_posts_columns', 'rozetta_manage_posts_column', 10, 2);
function rozetta_manage_posts_column($posts_columns, $post_type) {
	if (RozettaMultilingual::get_is_multilingaul_enable()) {
        $posts_columns = array_merge(
            array_slice($posts_columns, 0, 3),
            array('rozettaLocale' => __( 'Locale', 'rozetta-translator' )),
            array_slice($posts_columns, 3)
        );
    
        $status = '';
        if (isset($_GET['post_status'])) {
            $status = $_GET['post_status'];
        }

        if ($status != 'trash') {
            $posts_columns = array_merge(
                array_slice($posts_columns, 0, 4),
                array('rozettaLocalePost' => __( 'Posts in other language', 'rozetta-translator' )),
                array_slice($posts_columns, 4)
            );
        }
    }

	return $posts_columns;
}

function rozetta_manage_pages_column($posts_columns) {
	return rozetta_manage_posts_column( $posts_columns, 'page' );
}

add_action('manage_pages_custom_column', 'rozetta_manage_posts_custom_column', 10, 2);
add_action('manage_posts_custom_column', 'rozetta_manage_posts_custom_column', 10, 2);
function rozetta_manage_posts_custom_column($column_name, $post_id) {
	if (RozettaMultilingual::get_is_multilingaul_enable()) {
        if ('rozettaLocale' == $column_name) {
            $post_type = get_post_type($post_id);
            $locale = get_post_meta( $post_id, '_rozetta_locale', true );
            if ($locale !== '') {
                print_r(__(rozetta_get_languages_array()[$locale], 'rozetta-translator'));
            }
        } else if ('rozettaLocalePost' == $column_name) {
            $condition = array(
                'post_status' => isset($_GET['post_status']) && $_GET['post_status'] != 'all' ? array($_GET['post_status']) : array('publish', 'pending', 'draft', 'future', 'private'),
                'post_type' => get_post($post_id)->post_type,
                'nopaging' => true,
                'rozetta_locale' => 'all',
                'numberposts' => 0,
            );
            $contentArray = get_posts($condition);
            if ($type == 'page') {
                $contentArray = get_pages($condition);
            }
            $current_original_post_id =  get_post_meta($post_id, '_rozetta_original_post_id', true);
            foreach ( $contentArray as $post ) {
                $original_post_id = '';
                if (isset(get_post_meta($post->ID)['_rozetta_original_post_id'])) {
                    $original_post_id = get_post_meta($post->ID)['_rozetta_original_post_id'][0];
                }
                if (($post_id != $post->ID) && ($original_post_id != '' && $original_post_id == $current_original_post_id || $post_id == $original_post_id || $post->ID == $current_original_post_id)) {
                    print '<div><a href="' . get_edit_post_link($post->ID) . '">' .  __(rozetta_get_languages_array()[get_post_meta($post->ID, '_rozetta_locale', true)], 'rozetta-translator') . '</a></div>';
                }
            }
        } else {
            return;
        }
    }
}

?>