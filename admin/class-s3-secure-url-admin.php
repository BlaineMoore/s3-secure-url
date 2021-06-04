<?php
/**
 * S3 Secure URL
 *
 * @package   S3_Secure_URL_Admin
 * @author    Max Kostinevich <hello@maxkostinevich.com>
 * @license   GPL-2.0+
 * @link      http://maxkostinevich.com
 * @copyright 2015 Max Kostinevich
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-s3-secure-url.php`
 *
 * @package S3_Secure_URL_Admin
 * @author    Max Kostinevich <hello@maxkostinevich.com>
 */
class S3_Secure_URL_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$plugin = S3_Secure_URL::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		add_action( 'admin_init', array( $this, 'admin_options_init' ) );

		add_action( 'admin_init', array( $this, 'register_tinymce_plugin' ) );

		/*
		foreach ( array('post.php','post-new.php') as $hook ) {
			add_action( "admin_head-$hook", array($this,'js_ajax_nonce') );
		}
		*/

		add_action( "admin_head", array($this,'js_ajax_nonce') );


	}



	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), S3_Secure_URL::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), S3_Secure_URL::VERSION );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 *   For reference: http://codex.wordpress.org/Roles_and_Capabilities
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Amazon S3 Secure URL', $this->plugin_slug ),
			__( 'S3 Secure URL', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}


	/**
	 * Register new TinyMCE plugin to handle shortcode
	 *
	 * @since    1.0.0
	 */
	public function register_tinymce_plugin() {
		if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
			add_filter( 'mce_external_plugins', array( $this, 'tinymce_add_plugin' ) );
			add_filter( 'mce_buttons', array( $this, 'tinymce_add_buttons' ) );
		}
	}
	public function tinymce_add_plugin($plugin_array) {
		$plugin_array['s3_secure_url'] = plugins_url( 'assets/js/tinymce-plugin/tinymce-plugin.js', __FILE__ );
		return $plugin_array;
	}
	function tinymce_add_buttons($buttons) {
		array_push( $buttons, 's3_secure_url' );
		return $buttons;
	}

	/**
	 * Add Ajax Nonce to TinyMCE javascript file
	 *
	 * @since     1.0.0
	 */
	public function js_ajax_nonce() {
		$ajax_nonce = wp_create_nonce( 's3_secure_url_ajax_request' );
		?>
		<!-- TinyMCE Shortcode Plugin -->
		<script type='text/javascript'>
			var s3_secure_url = {
				'ajax_nonce': '<?php echo $ajax_nonce; ?>',
			};
		</script>
		<!-- TinyMCE Shortcode Plugin -->
	<?php
	}

	/**
	 * Init plugin options
	 *
	 * @since    1.0.0
	 */
	public function admin_options_init() {


		// Sections
		add_settings_section( 'main-section', 'Plugin Options', array( $this, 'plugin_options_section_callback' ), 's3-secure-url');

		// Handle plugin options
		foreach(S3_Secure_URL::$pluginOptions as $setting){
			if((isset($setting['hidden']))&&($setting['hidden']==1)){
				// Hidden option
			}else{
				// Register Settings
				register_setting( 's3_secure_url_settings', $setting['name'] );
				// Fields
				add_settings_field( $setting['name'], $setting['title'], array( $this, 'setting_field_callback' ), 's3-secure-url' , $setting['section'], array('name'=>$setting['name'],'field'=>$setting['field']) );
			}
		}


	}

	/**
	 * Main section callback
	 *
	 * @since    1.0.0
	 */
	public function plugin_options_section_callback($args) {
		$section_id=$args['id'];
		switch($section_id){
			case 'main-section':?>
				<p>Add your Amazon Access and Secret Keys</p>
				<p>Shortcode usage:</p>
				<p>Wrap text ('Download Now') in the link (<b>a-tag</b>):<br>
				<b>[s3secureurl bucket='bucket-name' target='/path/to/file.ext' expires='5']Download Now[/s3secureurl]</b><br>
				Output: <b><a href="http://example.com/secure-url" target="_blank">Download Now</a></b></p>
				<p>Display the raw link as text:<br>
				<b>[s3secureurl bucket='bucket-name' target='/path/to/file.ext' expires='5' /]</b><br>
				Output: <b>http://example.com/secure-url</b></p>
				<?php
				break;
		}
	}


	/**
	 * Generate setting field
	 *
	 * @since    1.0.0
	 */
	public function setting_field_callback($args) {
		$setting_value = esc_attr( get_option( $args['name'] ) );
		$field=$args['field'];
		if(isset($field['class'])){
			$field_class=$field['class']?$field['class']:'';
		}else{
			$field_class='';
		}
		if(isset($field['description'])){
			$field_descr=$field['description']?('<span class="description">'.$field['description'].'</span>'):"";
		}else{
			$field_descr='';
		}
		if(isset($field['id'])){
			$field_id=$field['id']?$field['id']:'';
		}else{
			$field_id='';
		}
		switch($field['type']){
			case 'checkbox':
				?>
				<input id="<?php echo $field_id;?>" class="<?php echo $field_class;?>" type="checkbox" name="<?php echo $args['name'];?>" value="1" <?php checked( $setting_value, '1', true);?> />
				<?php echo $field_descr;?>
				<?php
				break;
			case 'radio':
				if(is_array($field['options'])){
					?>
					<?php echo $field_descr;?>
					<?php
					foreach($field['options'] as $k=>$v){
						?>
						<label><input id="<?php echo $field_id;?>" class="<?php echo $field_class;?>" type="radio" name="<?php echo $args['name'];?>" value="<?php echo $k;?>" <?php checked( $setting_value, $k, true);?> /> <span><?php echo $v;?></span></label><br />
					<?php
					}
				}
				break;
			case 'combobox':
				if(is_array($field['options'])){
					?>
					<input type="text" list="<?php echo $field_id;?>list" id="<?php echo $field_id;?>" class="<?php echo $field_class;?>" name="<?php echo $args['name'];?>" value="<?php echo $setting_value; ?>" />
					<datalist id="<?php echo $field_id;?>list">
					<?php
						foreach($field['options'] as $k=>$v){
					?>
						<option value="<?php echo $k;?>" <?php selected( $setting_value, $k, true);?>><?php echo $v;?></option>
					<?php
						}
					?>
					</datalist>
					<?php echo $field_descr;?>
				<?php
				}
				break;
			case 'dropdown':
				if(is_array($field['options'])){
					?>
					<select id="<?php echo $field_id;?>" class="<?php echo $field_class;?>" name="<?php echo $args['name'];?>">
						<?php
						foreach($field['options'] as $k=>$v){
							?>
							<option value="<?php echo $k;?>" <?php selected( $setting_value, $k, true);?>><?php echo $v;?></option>
						<?php
						}
						?>
					</select>
					<?php echo $field_descr;?>
				<?php
				}
				break;
			case 'text':
				?>
				<input id="<?php echo $field_id;?>" class="<?php echo $field_class;?>" type="text" name="<?php echo $args['name'];?>" value="<?php echo $setting_value;?>" />
				<?php echo $field_descr;?>
				<?php
				break;
			case 'password':
				?>
				<input id="<?php echo $field_id;?>" class="<?php echo $field_class;?>" type="password" name="<?php echo $args['name'];?>" value="<?php echo $setting_value;?>" />
				<?php echo $field_descr;?>
				<?php
				break;
			default:
				?>
				<input id="<?php echo $field_id;?>" class="regular-text" type="text" name="<?php echo $args['name'];?>" value="<?php echo $setting_value;?>" />
				<?php echo $field_descr;?>
				<?php
				break;
		}
	}







}
