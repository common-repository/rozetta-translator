<?php

function rozetta_add_post($data) {
    $post = get_post($data['id']);
  
    if (empty( $post )) {
      return null;
    }

    $target_parent = $post->post_parent;
    
    if (!empty($post->post_parent)) {
        $target_post = $post;
        if (get_post_meta($data['id'], '_rozetta_original_post_id', true)) {
            $target_post = get_post(get_post_meta($data['id'], '_rozetta_original_post_id', true));
        }
        $target_parent = rozetta_find_post_with_original_and_locale($target_post->post_parent, $data['lang'])->ID;
    }

    $new_post = array(
        'post_title' => $post->post_title,
        'post_content' => $post->post_content,
        'post_author' => $post->post_author,
        'post_type' => $post->post_type,
        'post_excerpt' => $post->post_excerpt,
        'post_password' => $post->post_password,
        'post_parent' => $target_parent,
        'post_status' => 'draft'
    );

    $post_id = wp_insert_post($new_post);
    
    $post_meta = get_post_custom($data['id']);
    foreach ($post_meta as $key => $values) {
        foreach ($values as $value) {
            add_post_meta($post_id, $key, maybe_unserialize($value));
        }
    }

    $original_post_id = $data['id'];
    if (get_post_meta($data['id'], '_rozetta_original_post_id', true)) {
        $original_post_id = get_post_meta($data['id'], '_rozetta_original_post_id', true);
    }
    update_post_meta( $post_id, '_rozetta_locale', $data['lang'] );
    update_post_meta( $post_id, '_rozetta_original_post_id', $original_post_id );
    
    $taxonomies = get_post_taxonomies($data['id']);
    foreach ($taxonomies as $taxonomy) {
        $term_ids = wp_get_object_terms($data['id'], $taxonomy, ['fields' => 'ids']);
        wp_set_object_terms($post_id , $term_ids, $taxonomy);
    }

    if (function_exists('get_fields')) {
        $fields = get_fields($data['id']);
    }
    if ($fields) {
        foreach( $fields as $name => $value ):
            update_field( $name, $value, $post_id );
        endforeach;
    }

    return get_post($post_id);
}
add_action('rest_api_init', function () {
    register_rest_route( 'rozetta-wp-api/v1', '/post/(?P<id>[a-zA-Z0-9-]+)/lang/(?P<lang>[a-zA-Z-]+)', array(
      'methods' => 'POST',
      'callback' => 'rozetta_add_post',
      'args' => array(),
      'permission_callback' => '__return_true',
    ));
});

function rozetta_return_actived_langs() {
    $rtn_array = array();
    foreach ( RozettaMultilingual::get_actived_langs() as $lang ) {
        array_push($rtn_array, array(
            'lang' => $lang,
            'langName' => rozetta_get_languages_array()[$lang]
        ));
    }
    return $rtn_array;
}
add_action('rest_api_init', function () {
    register_rest_route( 'rozetta-wp-api/v1', '/activedLangs', array(
      'methods' => 'GET',
      'callback' => 'rozetta_return_actived_langs',
      'args' => array(),
      'permission_callback' => '__return_true',
    ));
});


function rozetta_return_other_posts($data) {
    $post_id = $data['id'];

    $current_original_post_id =  get_post_meta($post_id, '_rozetta_original_post_id', true);

    $rtn_array = array();

    $contnetArray = get_posts(array('post_status' => array('publish', 'pending', 'draft', 'future', 'private'), 'post_type' => get_post($post_id)->post_type, 'nopaging' => true, 'numberposts' => 0));
    if (get_post($post_id)->post_type == 'page') {
        $contnetArray = get_pages(array('post_status' => array('publish', 'pending', 'draft', 'future', 'private'), 'post_type' => get_post($post_id)->post_type, 'nopaging' => true, 'numberposts' => 0));
    }
    wp_set_current_user(1);
    foreach ($contnetArray as $post) {
        $original_post_id = get_post_meta($post->ID)['_rozetta_original_post_id'][0];
        if (($post_id == $post->ID || $original_post_id != '') && $original_post_id == $current_original_post_id || $post_id == $original_post_id || $post->ID == $current_original_post_id) {
            if ($post_id != $post->ID) {
                array_push($rtn_array, array(
                    'lang' => get_post_meta($post->ID, '_rozetta_locale', true),
                    'langName' => __(rozetta_get_languages_array()[get_post_meta($post->ID, '_rozetta_locale', true)], 'rozetta-translator'),
                    'post_id_link' => htmlspecialchars_decode(get_edit_post_link($post->ID))
                ));
            }
        }
    }
    wp_set_current_user(0);
    return $rtn_array;
}
add_action('rest_api_init', function () {
    register_rest_route( 'rozetta-wp-api/v1', '/otherPost/(?P<id>[a-zA-Z0-9-]+)', array(
      'methods' => 'GET',
      'callback' => 'rozetta_return_other_posts',
      'args' => array(),
      'permission_callback' => '__return_true',
    ));
});

function rozetta_copy_cutomized_fields($data) {
    
    $taxonomies = get_post_taxonomies($data['original_id']);
    foreach ($taxonomies as $taxonomy) {
        $term_ids = wp_get_object_terms($data['original_id'], $taxonomy, ['fields' => 'ids']);
        wp_set_object_terms($data['id'] , $term_ids, $taxonomy);
    }

    $fields = get_fields($data['original_id']);
    if ($fields) {
        foreach( $fields as $name => $value ):
            update_field( $name, $value, $data['id'] );
        endforeach;
    }

    $post_lang = get_post_meta($data['id'], '_rozetta_locale', true);
    if (!empty(get_post($data['original_id'])->post_parent)) {
        $target_parent = rozetta_find_post_with_original_and_locale(get_post($data['original_id'])->post_parent, $post_lang);
        if (!empty($target_parent)) {
            wp_update_post(
                array(
                    'ID' => $data['id'], 
                    'post_parent' => $target_parent->ID
                )
            );
            if (!$fields) {
                return array(
                    'ID' => $data['id'], 
                    'post_parent' => $target_parent->ID
                );
            }        
        }
    }
    
    return $fields;
}
add_action('rest_api_init', function () {
    register_rest_route( 'rozetta-wp-api/v1', '/copy/(?P<original_id>[a-zA-Z0-9-]+)/(?P<id>[a-zA-Z0-9-]+)', array(
      'methods' => 'POST',
      'callback' => 'rozetta_copy_cutomized_fields',
      'args' => array(),
      'permission_callback' => '__return_true',
    ));
});
?>