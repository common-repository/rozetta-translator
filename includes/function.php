<?php
add_filter( 'posts_join', 'rozetta_posts_join', 10, 2 );

function rozetta_posts_join( $join, $query ) {
	global $wpdb;
	if (!$meta_table = _get_meta_table('post')) {
		return $join;
	}

	if (RozettaMultilingual::get_is_multilingaul_enable()) {
		$join .= " LEFT JOIN $meta_table AS postmeta_rozetta ON ($wpdb->posts.ID = postmeta_rozetta.post_id AND postmeta_rozetta.meta_key = '_rozetta_locale')";
		$join .= " LEFT JOIN $meta_table AS postmeta_rozetta_origin ON ($wpdb->posts.ID = postmeta_rozetta_origin.post_id AND postmeta_rozetta_origin.meta_key = '_rozetta_original_post_id')";
	}
	return $join;
}

add_filter( 'posts_where', 'rozetta_posts_where', 10, 2 );
function rozetta_posts_where( $where, $query ) {
	global $wpdb;

    $post_id = get_the_id();
    $locale = get_locale();

	if (is_admin()) {
		//return $where;
	}

	if (!$meta_table = _get_meta_table('post')) {
		return $where;
	}

	if (isset($query->query['post_status']) && $query->query['post_status'] === 'trash') {
		return $where;
	}
    
	if (RozettaMultilingual::get_is_multilingaul_enable()) {
		if (is_admin()) {
			// $where .= ' OR postmeta_rozetta_origin.meta_value IS NULL';
		} else {
			$where .= ' AND (1=0';
			$where .= $wpdb->prepare(' OR postmeta_rozetta.meta_value = %s', $locale);
			$where .= ' OR postmeta_rozetta.meta_value IS NULL';
			$where .= ')';
		}
	}

	return $where;
}

add_filter('wp_nav_menu_objects', 'filter_wp_nav_menu_objects', 10, 5 ); 
function filter_wp_nav_menu_objects($sorted_menu_items, $args) {
	if (RozettaMultilingual::get_is_multilingaul_enable()) {
		$new_menu_items = array();
		$locale = get_locale();
		foreach ($sorted_menu_items as $post) {
			$rozetta_locale = get_post_meta($post->object_id, '_rozetta_locale', true);
	
			if ($rozetta_locale == $locale || !$rozetta_locale) {
				array_push($new_menu_items, $post);
			}
		}
		return $new_menu_items; 
	}
    return $sorted_menu_items; 
}; 

add_filter('get_previous_post_join', 'rozetta_get_adjacent_post_join');
add_filter('get_next_post_join', 'rozetta_get_adjacent_post_join');
function rozetta_get_adjacent_post_join($join) {
    global $wpdb;
	if (!$meta_table = _get_meta_table('post')) {
		return $join;
	}
	if (RozettaMultilingual::get_is_multilingaul_enable()) {
		$join .= " LEFT JOIN $wpdb->postmeta AS postmeta_rozetta ON (p.ID = postmeta_rozetta.post_id AND postmeta_rozetta.meta_key = '_rozetta_locale') ";
	}
    return $join;
}

add_filter('get_previous_post_where', 'rozetta_get_adjacent_post_where', 10, 5);
add_filter('get_next_post_where', 'rozetta_get_adjacent_post_where', 10, 5);
function rozetta_get_adjacent_post_where($where, $in_same_term, $excluded_terms, $taxonomy, $post) {
	global $wpdb;
    $locale = get_locale();
    
	if (RozettaMultilingual::get_is_multilingaul_enable()) {
		$where .= ' AND (1=0';
		$where .= $wpdb->prepare(' OR postmeta_rozetta.meta_value LIKE %s', $locale);
		$where .= ' OR postmeta_rozetta.meta_value IS NULL';
		$where .= ')';
	}

    return $where;
}

foreach( [ 'post', 'page', 'post_type' ] as $type )
{
    add_filter( $type . '_link', function ( $url, $post, $sample ) use ( $type )
    {
        return apply_filters( 'wpse_link', $url, $post, $sample, $type );
    }, 9999, 3 );
}
add_filter( 'wpse_link', function($url, $post, $sample, $type)
{
	if (RozettaMultilingual::get_is_multilingaul_enable()) {
		if (is_int($post)) {
			$post_id = $post;
		} else {
			$post_id = $post -> ID;
		}
		$locale = get_post_meta($post_id, '_rozetta_locale', true );
		$original_post = get_post_meta($post_id, '_rozetta_original_post_id', true );
		if (!$original_post) {
			return $url;
		}
		if ($locale) {
			return add_query_arg('lang', $locale, $url);
		}
	}
    return $url;
}, 10, 4 );

function rozetta_find_post_with_original_and_locale($original_post_id, $original_post_lang) {
    $return_target = get_post($original_post_id);
    $type = $return_target->post_type;

    $condition = array(
        'post_status' => array('publish', 'pending', 'draft', 'future', 'private'),
        'post_type' => $type,
        'nopaging' => true,
        'rozetta_locale' => 'all',
        'numberposts' => 0,
    );
    $contentArray = get_posts($condition);
    if ($type == 'page') {
        $contentArray = get_pages($condition);
    }
    
    foreach ( $contentArray as $target_post ) {
        $target_original_post_id = '';
        $target_original_post_lang = '';
        if (isset(get_post_meta($target_post->ID)['_rozetta_original_post_id'])) {
            $target_original_post_id = get_post_meta($target_post->ID, '_rozetta_original_post_id', true);
            $target_original_post_lang = get_post_meta($target_post->ID, '_rozetta_locale', true);
        }
        if ($target_original_post_id != '' && $target_original_post_id == $original_post_id && $target_original_post_lang != '' && $target_original_post_lang == $original_post_lang) {
            $return_target = $target_post;
        }
    }
    return $return_target;
}

?>