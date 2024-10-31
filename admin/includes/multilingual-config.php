<?php
class RozettaMultilingual
{
	const VERSION           = '1.0.0';
	const PLUGIN_ID         = 'rozetta-translator';
	const CONFIG_MENU_SLUG  = self::PLUGIN_ID . '-config';
	const CREDENTIAL_ACTION = self::PLUGIN_ID . '-nonce-action';
	const CREDENTIAL_NAME   = self::PLUGIN_ID . '-nonce-key';
	const PLUGIN_DB_PREFIX  = self::PLUGIN_ID . '_';

	static function init()
	{
		return new self();
	}

	function __construct()
	{
		if (is_admin() && is_user_logged_in()) {
			add_action('admin_menu', [$this, 'set_plugin_menu']);
			add_action('admin_init', [$this, 'save_config']);
			add_action('admin_init', [$this, 'update_customized_field']);
		}
	}

	function set_plugin_menu()
	{
		add_submenu_page(
		  'rozetta-translator',
		  __( 'Multilingual', 'rozetta-translator' ),
		  __( 'Multilingual', 'rozetta-translator' ),
		  'manage_options',
		  'rozetta-multilingual',
		  [$this, 'show_multilingual_form']
		);
		
		add_submenu_page(
			'rozetta-translator',
			__( 'Custom Menu CSS', 'rozetta-translator' ),
			__( 'Custom Menu CSS', 'rozetta-translator' ),
			'manage_options',
			'rozetta-translator-custom-menu-css',
			[$this, 'show_custom_menu_css']
		);
	}
	
	static function show_multilingual_form() {
    $enableMultilingual = get_option(self::PLUGIN_DB_PREFIX . 'enableMultilingual');
	$activeLangs = get_option(self::PLUGIN_DB_PREFIX . 'activeLangs');
?>
	  <div class='wrap'>
		<h1><?php _e( 'Multilingual', 'rozetta-translator' );?></h1>
		<form action='' method='post' id='multilingual-form-ability'>
			<?php wp_nonce_field('set_multilingual_ability', 'save_config') ?>
			<p class='rozetta-setting-item'>
		        <input id="rozetta-enable-multilingual" type="checkbox" name="enable-multilingual" <?php echo $enableMultilingual == '' ? '' : 'checked' ?>><label class="rozetta-setting-label" for="rozetta-enable-multilingual"><?php _e( 'Enable Multilingual', 'rozetta-translator' );?></label>
			<input type='submit' value='<?php _e( 'Save', 'rozetta-translator' );?>' class='button button-primary'>
            </p>
		</form>
        <hr>
    <?php
        if ($enableMultilingual != '') {
    ?>
		<h4><?php _e( 'Active Languages', 'rozetta-translator' );?></h4>
		<form action='' method='post' id='multilingual-form-actived'>
			<?php wp_nonce_field('set_multilingual_languages_delete', 'save_config') ?>
			<?php
			foreach ( $activeLangs as $activeLang ) {
				print '
				<div class="rozetta-lang-item">
					<input id="rozetta-active-lang-' . $activeLang . '" type="checkbox" value="' . $activeLang . '" name="deleteLangs[]" ' . ($activeLang == get_locale() ? 'disabled' : '') .'>
					<label class="rozetta-setting-label" for="rozetta-active-lang-' . $activeLang . '">' . __(rozetta_get_languages_array()[$activeLang], 'rozetta-translator') . '
					</label>
				</div>';
			}
			?>
			<input type='submit' value='<?php _e( 'Delete', 'rozetta-translator' );?>' class='button button-large'>
		</form>
        <hr>
		<h4><?php _e( 'Add Language', 'rozetta-translator' );?></h4>
		<form action='' method='post' id='multilingual-form'>
			<?php wp_nonce_field('set_multilingual_languages', 'save_config') ?>
            <select id='rozettaSettingLang' class='rozetta-metabox-select-lang' placeholder='<?php _e('Setting Language', 'rozetta-translator')?>' name='settingLang'>
			<?php
			foreach ( rozetta_get_languages_array() as $key => $name ) {
				if (!in_array($key, $activeLangs)) {
					print '<option value="' . $key . '">' . __($name, 'rozetta-translator') . '</option>';
				}
			}
			?>
			</select>
			<input type='submit' value='<?php _e( 'Add Language', 'rozetta-translator' );?>' class='button button-primary button-large'>
		</form>
	  </div>
<?php
        }
	}
	  
	static function show_custom_menu_css() {
		$customMenuCss = get_option(self::PLUGIN_DB_PREFIX . 'customMenuCss');
		$activeCollapse = get_option(self::PLUGIN_DB_PREFIX . 'activeCollapse');
		$defaultCss = '/* sample css */
.widget_rozetta_language_switcher {
  cursor: pointer;
}

.widget_rozetta_language_switcher 
.widget-title {
  margin: 5px auto;
}

.widget_rozetta_language_switcher 
.rozetta-language-link-list{
  max-height:150px;
  overflow: hidden;
  transition: max-height 600ms ease-out;
}

.widget_rozetta_language_switcher 
.rozetta-hide-list {
  max-height:0px!important;
}

.widget_rozetta_language_switcher 
.rozetta-language-link-list 
.rozetta-language-link:hover {
 background-color: rgba(0,0,0,0.2);
}';
		?>
		<div class='wrap'>
			<h1><?php _e( 'Custom Menu CSS', 'rozetta-translator' );?></h1>
			<form action='' method='post' id='custom_menu_css-form'>
				<?php wp_nonce_field('set_custom_menu_css', 'save_config') ?>
				<p class='rozetta-setting-item'>
					<label class='rozetta-setting-label' for='customMenuCss'><?php _e( 'Custom Menu CSS', 'rozetta-translator' );?></label>
					<textarea class='rozetta-setting-input' name='customMenuCss' rows='30' placeholder='<?php echo esc_attr($defaultCss); ?>'><?php echo esc_attr($customMenuCss) ?></textarea>
				</p>
				<div><input id="rozetta-active-collapse" type="checkbox" name="active-collapse" <?php echo $activeCollapse == '' ? '' : 'checked' ?>><label class="rozetta-setting-label" for="rozetta-active-collapse"><?php _e( 'Enable click to collapse menu', 'rozetta-translator' );?></label></div>
	
				<input type='submit' value='<?php _e( 'Save', 'rozetta-translator' );?>' class='button button-primary button-large'>
			</form>
		</div>
		<?php
  	}

	static function save_config()
	{
		if (isset($_POST['save_config']) && $_POST['save_config']) {
			if (wp_verify_nonce( $_POST['save_config'], 'set_multilingual_ability')) {
				$enableMultilingual = esc_html($_POST['enable-multilingual']);
				
				update_option(self::PLUGIN_DB_PREFIX . 'enableMultilingual', $enableMultilingual);

				$activeLangs = get_option(self::PLUGIN_DB_PREFIX . 'activeLangs');
				if (!$activeLangs) {
					$activeLangs = array(get_locale());
				}
				if (!in_array(get_locale(), $activeLangs)) {
					array_push($activeLangs, get_locale());
				}
				update_option(self::PLUGIN_DB_PREFIX . 'activeLangs', $activeLangs);
			}

			if (wp_verify_nonce( $_POST['save_config'], 'set_multilingual_languages')) {
				$settingLang = sanitize_text_field($_POST['settingLang']);
				if ($settingLang !== $_POST['settingLang']) {
					?>
					<script>alert('<?php _e( 'Saving Failed', 'rozetta-translator' );?>')</script>
					<?php
				} else {
					$activeLangs = get_option(self::PLUGIN_DB_PREFIX . 'activeLangs');
					if (in_array($settingLang, $activeLangs)) {
						?>
						<script>alert('<?php _e( 'Language is actived', 'rozetta-translator' );?>')</script>
						<?php
					} else {
						if (!$activeLangs) {
							$activeLangs = array();
						}
						array_push($activeLangs, $settingLang);
						update_option(self::PLUGIN_DB_PREFIX . 'activeLangs', $activeLangs);
					}
				}
			}
			
			if (wp_verify_nonce( $_POST['save_config'], 'set_multilingual_languages_delete')) {
				$settingLangs = $_POST['deleteLangs'];
				$activeLangs = get_option(self::PLUGIN_DB_PREFIX . 'activeLangs');

				if (is_array($settingLangs)) {
					update_option(self::PLUGIN_DB_PREFIX . 'activeLangs', array_values(array_diff($activeLangs, $settingLangs)));
				}
				
				if (count($settingLangs) == 0) {
					?>
					<script>alert('<?php _e( 'Language is not selected', 'rozetta-translator' );?>')</script>
					<?php
				}
			}

			if (wp_verify_nonce( $_POST['save_config'], 'set_custom_menu_css')) {
				$customMenuCss = esc_html($_POST['customMenuCss']);
				$activeCollapse = esc_html($_POST['active-collapse']);				
				
				update_option(self::PLUGIN_DB_PREFIX . 'customMenuCss', $customMenuCss);
				update_option(self::PLUGIN_DB_PREFIX . 'activeCollapse', $activeCollapse);
			}
		}
	}
	
	static function update_customized_field()
	{
		if (isset($_POST['update_customized_field']) && $_POST['update_customized_field']) {
			if(array_key_exists('accept',$_POST)){
				$post_id = $_POST['post_ID'];
				$current_original_post_id =  get_post_meta($post_id, '_rozetta_original_post_id', true);
				$fields = get_fields($current_original_post_id);
				if ($fields) {
					foreach( $fields as $name => $value ):
						update_field( $name, $value, $post_id );
					endforeach;
				}
			}       
		}
	}   
	
    static function get_is_multilingaul_enable()
    {
		return esc_attr(get_option(self::PLUGIN_DB_PREFIX . 'enableMultilingual')) != '';
    }
	static function get_actived_langs()
    {
		return get_option(self::PLUGIN_DB_PREFIX . 'activeLangs');
	}
    static function get_custom_menu_css()
    {
		return esc_attr(get_option(self::PLUGIN_DB_PREFIX . 'customMenuCss'));
    }
    static function get_is_collapse_menu()
    {
		return esc_attr(get_option(self::PLUGIN_DB_PREFIX . 'activeCollapse')) != '';
    }
}
?>