<?php
add_action( 'widgets_init', 'rozetta_register_widgets', 10, 0 );

// Disables the block editor from managing widgets in the Gutenberg plugin.
add_filter( 'gutenberg_use_widgets_block_editor', '__return_false', 100 );

// Disables the block editor from managing widgets. renamed from wp_use_widgets_block_editor
add_filter( 'use_widgets_block_editor', '__return_false' );

function rozetta_register_widgets() {
	if (RozettaMultilingual::get_is_multilingaul_enable()) {
		register_widget( 'Rozetta_Language_Switcher' );
	}
}

class Rozetta_Language_Switcher extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'Language Switcher', 'rozetta-translator' ),
		);

		$control_ops = array();

		WP_Widget::__construct( 'rozetta_language_switcher',
			__( 'Language Switcher', 'rozetta-translator' ),
			$widget_ops, $control_ops
		);
	}

	function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title',
			empty( $instance['title'] )
				? __( 'Language Switcher', 'rozetta-translator' )
				: $instance['title'],
			$instance, $this->id_base
		);

		// print_r($args);
		// print_r($instance);
		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		
        $contentArray = get_posts(array('post_status' => array('publish'), 'nopaging' => true, 'numberposts' => 0, 'post_type'=> 'any'));
		
		array_push($contentArray, get_pages(array('post_status' => array('publish'), 'nopaging' => true, 'numberposts' => 0)));
		
		$locale_existed_array = array();
		$current_original_post_id =  get_post_meta(get_the_ID(), '_rozetta_original_post_id', true);
		if ($current_original_post_id) {
			$current_original_post_locale =  get_post_meta($current_original_post_id, '_rozetta_locale', true);
		} else {
			$current_original_post_locale =  get_post_meta(get_the_ID(), '_rozetta_locale', true);
		}

		print '<div class="rozetta-language-link-list ' . (RozettaMultilingual:: get_is_collapse_menu() ? 'rozetta-hide-list' : '') . '">';

        foreach ($contentArray as $post_c) {
            $original_post_id = get_post_meta($post_c->ID)['_rozetta_original_post_id'][0];
            $rozetta_locale = get_post_meta($post_c->ID)['_rozetta_locale'][0];
			foreach (RozettaMultilingual::get_actived_langs() as $activeLang) {
				if (is_front_page()) {
					if (!in_array($activeLang, $locale_existed_array) && $activeLang == get_post_meta(get_option('page_on_front'))['_rozetta_locale'][0]) {
						print '<a href="' . get_home_url() . '"><div class="rozetta-language-link rozetta-home">' . rozetta_get_languages_array()[$activeLang] . '</div></a>';
						array_push($locale_existed_array, $activeLang);
					}
				} else if (is_home() || is_category() || is_post_type_archive() ) {
					if (!in_array($activeLang, $locale_existed_array)) {
						print '<a href="' . add_query_arg('lang', $activeLang, get_post_type_archive_link($_GET['post_type'])) . '"><div class="rozetta-language-link rozetta-home">' . rozetta_get_languages_array()[$activeLang] . '</div></a>';
						array_push($locale_existed_array, $activeLang);
					}
				} else if (((get_the_ID() == $post_c->ID || $original_post_id != '') && $original_post_id == $current_original_post_id || get_the_ID() == $original_post_id || $post_c->ID == $current_original_post_id) && $rozetta_locale == $activeLang) {
					if ($current_original_post_locale == $activeLang) {
						print '<a href="' . get_permalink($post_c->ID) . '"><div class="rozetta-language-link">' . rozetta_get_languages_array()[$activeLang] . '</div></a>';
					} else {
						print '<a href="' . add_query_arg('lang', $activeLang, get_permalink($post_c->ID)) . '"><div class="rozetta-language-link">' . rozetta_get_languages_array()[$activeLang] . '</div></a>';
					}
					array_push($locale_existed_array, $activeLang);
				}
			}
        }
		$locale_unexisted_array = array_diff(RozettaMultilingual::get_actived_langs(), $locale_existed_array);
		$current_post_locale =  get_post_meta(get_the_ID(), '_rozetta_locale', true);
		foreach ($locale_unexisted_array as $activeLang) {
			if ($current_post_locale == '') {
				print '<a href="' . add_query_arg('lang', $activeLang, get_post(get_the_ID())->guid) . '"><div class="rozetta-language-link">' . rozetta_get_languages_array()[$activeLang] . '</div></a>';
			} else {
				print '<a href="' . add_query_arg('lang', $activeLang, get_home_url()) . '"><div class="rozetta-language-link rozetta-home">' . rozetta_get_languages_array()[$activeLang] . '</div></a>';
			}
		}

		//$current_locale =  get_post_meta(get_the_ID(), '_rozetta_locale', true);

		echo $args['after_widget'];

		if (RozettaMultilingual:: get_is_collapse_menu()) {
			print '
			<script>
			const widgetTitle = document.getElementById("' . $args['widget_id'] . '").querySelectorAll(".widget-title, .widgettitle")[0];
			widgetTitle.onclick = (e) => {
				const widgetMenu = document.getElementById("' . $args['widget_id'] . '").getElementsByClassName("rozetta-language-link-list")[0];
				widgetMenu.classList.toggle("rozetta-hide-list");
			};</script>
			<style>.rozetta-language-link-list{ max-height:150px; overflow: hidden; transition: max-height 600ms ease-out; } .rozetta-hide-list{ max-height: 0px; }</style>
			';
		}
		print '<style>/* sample css */
		  #' . $args['widget_id'] . '.widget_rozetta_language_switcher {
			cursor: pointer;
		  }
		  
		  #' . $args['widget_id'] . '.widget_rozetta_language_switcher  
		  .widget-title {
			margin: 5px auto;
		  }
		  
		  #' . $args['widget_id'] . '.widget_rozetta_language_switcher 
		  .rozetta-language-link-list{
			max-height:150px;
			overflow: hidden;
			transition: max-height 600ms ease-out;
		  }
		  
		  #' . $args['widget_id'] . '.widget_rozetta_language_switcher 
		  .rozetta-hide-list {
			max-height:0px!important;
		  }
		  
		  #' . $args['widget_id'] . '.widget_rozetta_language_switcher 
		  .rozetta-language-link-list 
		  .rozetta-language-link:hover {
		   background-color: rgba(0,0,0,0.2);
		  }</style>';
		print '<style>' . RozettaMultilingual::get_custom_menu_css() . '</style>';
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = strip_tags( $instance['title'] );

		echo '<p><label for="' . $this->get_field_id( 'title' ) . '">' . esc_html( __( 'Title:', 'rozetta-translator' ) ) . '</label> <input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ) . '" /></p>';
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$new_instance = wp_parse_args(
			(array) $new_instance,
			array( 'title' => '' )
		);

		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

}

/* Locale Option 

add_filter( 'widget_display_callback', 'bogo_widget_display_callback', 10, 3 );

function bogo_widget_display_callback( $instance, $widget, $args ) {
	if ( isset( $instance['bogo_locales'] ) ) {
		$locale = get_locale();

		if ( ! in_array( $locale, (array) $instance['bogo_locales'] ) ) {
			$instance = false;
		}
	}

	return $instance;
}*/
