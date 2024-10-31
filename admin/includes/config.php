<?php
class RozettaTranslator
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
		}
	}

	function set_plugin_menu()
	{
		add_menu_page(
			'Rozetta Translator',
			'Rozetta Translator',
			'manage_options',
			'rozetta-translator',
			[$this, 'show_config_form'],
			'dashicons-admin-generic',
			99
		);
		
		/* add_submenu_page(
		  'rozetta-translator',
		  __( 'Overview', 'rozetta-translator' ),
		  __( 'Overview', 'rozetta-translator' ),
		  'manage_options',
		  'rozetta-translator',
		  [$this, 'show_about_plugin']
		); */

		add_submenu_page(
		  'rozetta-translator',
		  __( 'Setting', 'rozetta-translator' ),
		  __( 'Setting', 'rozetta-translator' ),
		  'manage_options',
		  'rozetta-translator',
		  [$this, 'show_config_form']
		);

		add_submenu_page(
			'rozetta-translator',
			__( 'Custom Post Type', 'rozetta-translator' ),
			__( 'Custom Post Type', 'rozetta-translator' ),
			'manage_options',
			'rozetta-translator-custom-post-type-setting',
			[$this, 'show_custom_post_type_setting']
		);
	}

	static function show_about_plugin() {
		$accessKey = get_option(self::PLUGIN_DB_PREFIX . 'accessKey');
		$secretKey = get_option(self::PLUGIN_DB_PREFIX . 'secretKey');
		$contractId = get_option(self::PLUGIN_DB_PREFIX . 'contractId');
		?>
		  <div class='wrap'>
			<h1><?php _e( 'Overview', 'rozetta-translator' );?></h1>
			<p><?php printf(__( 'Access Key: %s', 'rozetta-translator' ), $accessKey);?></p>
			<p><?php printf(__( 'Secret Key: %s', 'rozetta-translator' ), $secretKey);?></p>
			<p><?php printf(__( 'Contract ID: %s', 'rozetta-translator' ), $contractId);?></p>
		  </div>
	  <?php
	}

	static function show_config_form() {
	  $accessKey = get_option(self::PLUGIN_DB_PREFIX . 'accessKey');
	  $secretKey = get_option(self::PLUGIN_DB_PREFIX . 'secretKey');
	  $contractId = get_option(self::PLUGIN_DB_PREFIX . 'contractId');
?>
	  <div class='wrap'>
		<h1><?php _e( 'Setting', 'rozetta-translator' );?></h1>
		<form action='' method='post' id='translator-form'>
			<?php wp_nonce_field('set_keys_contract', 'save_config') ?>
			<?php //wp_nonce_field(self::CREDENTIAL_ACTION, self::CREDENTIAL_NAME) ?>
			<p class='rozetta-setting-item'>
			  <label class='rozetta-setting-label' for='accessKey'><?php _e( 'Access Key', 'rozetta-translator' );?></label>
			  <input class='rozetta-setting-input' type='text' name='accessKey' value='<?php echo esc_attr($accessKey) ?>'/>
			</p>
			<p class='rozetta-setting-item'>
			  <label class='rozetta-setting-label' for='secretKey'><?php _e( 'Secret Key', 'rozetta-translator' );?></label>
			  <input class='rozetta-setting-input' type='text' name='secretKey' value='<?php echo esc_attr($secretKey) ?>'/>
			</p>
			<p class='rozetta-setting-item'>
			  <label class='rozetta-setting-label' for='contractId'><?php _e( 'Contract ID', 'rozetta-translator' );?></label>
			  <input class='rozetta-setting-input' type='text' name='contractId' value='<?php echo esc_attr($contractId) ?>'/>
			</p>
			<input type='submit' value='<?php _e( 'Save', 'rozetta-translator' );?>' class='button button-primary button-large'>
		</form>
	  </div>
<?php
	}

	static function show_custom_post_type_setting() {
		$customPostType = get_option(self::PLUGIN_DB_PREFIX . 'customPostType');
		?>
		<div class='wrap'>
			<h1><?php _e( 'Custom Post Type', 'rozetta-translator' );?></h1>
			<form action='' method='post' id='custom-post-type-form'>
				<?php wp_nonce_field('set_custom_post_type', 'save_config') ?>
				<p class='rozetta-setting-item'>
					<label class='rozetta-setting-label' for='customPostType'><?php _e( 'Custom Post Type', 'rozetta-translator' );?></label>
					<textarea class='rozetta-setting-input' name='customPostType' style='resize: none;'><?php echo esc_attr($customPostType) ?></textarea>
				</p>
				<p>
					<?php _e( 'Use comma to concat your custom post type slug. For example: news,info,collection', 'rozetta-translator' );?>
				</p>
				<input type='submit' value='<?php _e( 'Save', 'rozetta-translator' );?>' class='button button-primary button-large'>
			</form>
		</div>
		<?php
  	}
	  
	static function save_config()
	{
		if (isset($_POST['save_config']) && $_POST['save_config']) {
			// if (check_admin_referer($_POST['set_keys_contract'], $_POST['save_config'])) {
			if (wp_verify_nonce( $_POST['save_config'], 'set_keys_contract')) {

				$accessKey = sanitize_key($_POST['accessKey']);
				$secretKey = sanitize_key($_POST['secretKey']);
				$contractId = sanitize_key($_POST['contractId']);
				if ($accessKey !== $_POST['accessKey'] || $secretKey !== $_POST['secretKey'] || $contractId !== $_POST['contractId']) {
					?>
					<script>alert('<?php _e( 'Saving Failed', 'rozetta-translator' );?>')</script>
					<?php
				} else {
					update_option(self::PLUGIN_DB_PREFIX . 'accessKey', $accessKey);
					update_option(self::PLUGIN_DB_PREFIX . 'secretKey', $secretKey);
					update_option(self::PLUGIN_DB_PREFIX . 'contractId', $contractId);
					?>
					<script>alert('<?php _e( 'Saving Success', 'rozetta-translator' );?>')</script>
					<?php
					wp_safe_redirect(menu_page_url(self::CONFIG_MENU_SLUG), 301);
				}
			}

			if (wp_verify_nonce( $_POST['save_config'], 'set_custom_post_type')) {
				$customPostType = trim(str_replace(' ', '', sanitize_text_field($_POST['customPostType'])), ',');
				
				update_option(self::PLUGIN_DB_PREFIX . 'customPostType', $customPostType);
				?>
				<script>alert('<?php _e( 'Saving Success', 'rozetta-translator' );?>')</script>
				<?php
			}
			
		}
	}
    
    static function set_contract_keys($targetScript)
    {
        wp_localize_script($targetScript, 'settingInfo ', 
            [
            'accessKey' => get_option(self::PLUGIN_DB_PREFIX . 'accessKey'),
            'secretKey' => get_option(self::PLUGIN_DB_PREFIX . 'secretKey'),
            'contractId' => get_option(self::PLUGIN_DB_PREFIX . 'contractId')
            ]
        );
    }
	
	static function get_actived_langs()
    {
		return get_option(self::PLUGIN_DB_PREFIX . 'activeLangs');
	}
    static function get_custom_post_type()
    {
		return esc_attr(get_option(self::PLUGIN_DB_PREFIX . 'customPostType'));
    }
}
?>