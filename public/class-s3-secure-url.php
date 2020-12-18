<?php
/**
 * S3 Secure URL
 *
 * @package   S3_Secure_URL
 * @author    Max Kostinevich <hello@maxkostinevich.com>
 * @license   GPL-2.0+
 * @link      http://maxkostinevich.com
 * @copyright 2015 Max Kostinevich
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `s3-secure-url-admin.php`
 *
 * @package S3_Secure_URL
 * @author  Max Kostinevich <hello@maxkostinevich.com>
 */
class S3_Secure_URL {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.1.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 's3-secure-url';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	* Plugin options, used on plugin settings page
	*
	* @since    1.0.0
	*
	* @var      array
	*/
	public static $pluginOptions=array(
		array(
			'name'=>'s3_secure_url_plugin_version',
			'hidden'=>1
		),


		array(
			'name'=>'s3_secure_url_aws_access_key',
			'title'=>'Access Key ID',
			'section'=>'main-section',
			'field'=>array(
				'type'=>'text',
				'description'=>'Your Amazon access key ID',
				'class'=>'regular-text'
			)
		),

		array(
			'name'=>'s3_secure_url_aws_secret_key',
			'title'=>'Secret Access Key',
			'section'=>'main-section',
			'field'=>array(
				'type'=>'password',
				'description'=>'Your Amazon secret access key',
				'class'=>'regular-text'
			)
		),
	);

	/**
	 * Default plugin options values
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	public static $pluginDefaultOptions=array(
		'plugin_version'=>array(
			'name'=>'s3_secure_url_plugin_version',
			'value'=>'1.0.0'
		)
	);

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action('wp_ajax_s3_secure_url_load_popup', array( $this, 'ajax_s3_secure_url_load_popup'));
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		self::register_options();
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {

	}

	/**
	 * Register plugin settings
	 *
	 * @since    1.0.0
	 */
	private static function register_options() {
		//Fill in plugin options with default values
		foreach(self::$pluginDefaultOptions as $k=>$v){
			add_option( self::$pluginDefaultOptions[$k]['name'], self::$pluginDefaultOptions[$k]['value'] );
		}

		//Always update plugin version
		update_option( self::$pluginDefaultOptions['plugin_version']['name'], self::$pluginDefaultOptions['plugin_version']['value'] );


	}


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}



	/**
	 * Handle TinyMCE popup via AJAX
	 *
	 * @since    1.0.0
	 */
	public function ajax_s3_secure_url_load_popup(){
		// Security check
		check_ajax_referer( 's3_secure_url_ajax_request', 'nonce' );
		include_once( dirname(dirname(__FILE__)).'/admin/assets/js/tinymce-plugin/tinymce-plugin-popup.php' );
		wp_die(); // this is required to terminate immediately and return a proper response
	}


	/**
	 * Create temporary secure URLs to protected Amazon S3 files.
	 *
	 * @author Max Kostinevich
	 * https://maxkostinevich.com
	 *
	 * @param $bucketName Amazon S3 Bucket Name
	 * @param $objectPath The target file path starting with slash (e.g. '/folder-name/file-name.png')
	 * @param int $expires Time to expire in minutes
	 *
	 * @return string Temporary Amazon S3 URL
	 *
	 * @license MIT License http://opensource.org/licenses/MIT
	 *
	 * @version: 1.0.0
	 *
	 * Got inspired from:
	 * @see https://css-tricks.com/snippets/php/generate-expiring-amazon-s3-link/
	 * @see https://tournasdimitrios1.wordpress.com/2012/12/04/how-to-create-expiring-links-for-amazons-s3-with-php/
	 */

	private static function awsS3SecureURL($bucketName , $objectPath , $expires = 5, $urlformat = 'default') {

		$awsAccessKey=get_option( 's3_secure_url_aws_access_key' );
		$awsSecretKey=get_option( 's3_secure_url_aws_secret_key' );

		if(!$awsAccessKey || !$awsSecretKey){
			return '';
		}

		// Calculating expiry time
		$expires = time() + ($expires * 60) ;

		$objectPath =  ltrim($objectPath, '/') ;
		$signature = "GET\n\n\n$expires\n".'/'.$bucketName.'/'.$objectPath ;
		// Calculating  HMAC-sha1
		$hashedSignature = base64_encode(hash_hmac('sha1' ,$signature , $awsSecretKey , true )) ;
		// Constructing the URL
		switch($urlformat) {
			case "domain": $urlsyntax = 'https://%s/%s'; break;
			case "folder": $urlsyntax = 'https://s3.amazonaws.com/%s/%s'; break;
			case "default":
			default:
				$domainpattern = '/^((?!-)[A-Za-z0-9-]{1,63}(?<!-)\\.)+[A-Za-z]{2,6}$/';
				if(preg_match($domainpattern, $bucketName)) {
					$urlsyntax = 'https://%s/%s';
				} else {
					$urlsyntax = 'https://s3.amazonaws.com/%s/%s';
				}
				break;
		}
		$url = sprintf($urlsyntax, $bucketName , $objectPath);
		// Constructing the query String
		$queryString = http_build_query( array(
			'AWSAccessKeyId' => $awsAccessKey ,
			'Expires' => $expires ,
			'Signature' => $hashedSignature
		)) ;
		// Apending query string to URL
		return $url.'?'.$queryString ;
	}

	/**
	 * Handle [s3secureurl shortcode
	 *
	 * @param $atts
	 *          bucket Amazon S3 Bucket Name
	 *          target The target file path starting with slash (e.g. '/folder-name/file-name.png')
	 *          expires Time to expire in minutes
	 *			urlformat How to handle the bucket, as a "domain" or "folder" or by "default" to guess if it's a domain or folder.
	 *
	 * @param string $content
	 *
	 * @return string
	 * @since    1.0.0
	 */
	public static function sc_s3secureurl( $atts, $content = "" ) {
		extract(shortcode_atts(array(
			'file' => '',
			'bucket' => '',
			'target' => '',
			'expires' => 5,
			'urlformat' => 'default'
		), $atts));

		$expires=(int)$expires;

		$awsAccessKey=get_option( 's3_secure_url_aws_access_key' );
		$awsSecretKey=get_option( 's3_secure_url_aws_secret_key' );

		if(!$awsAccessKey || !$awsSecretKey){
			return '';
		}

		if(!$target) { $target = $file; }
		
		if(!$bucket || !$target || !$expires || $expires<=0){
			return '';
		}

		$secureURL=self::awsS3SecureURL($bucket,$target,$expires,$urlformat);

		if($content!=''){
			return '<a href="'.$secureURL.'" rel="nofollow" target="_blank">'.$content.'</a>';

		}else{
			return $secureURL;
		}

	}

}

/**
 * Register [s3secureurl] shortcode
 *
 * usage: [s3secureurl bucket='bucket-name' target='/path/to/file.ext' expires='5']Download Now[/s3secureurl]
 * or: [s3secureurl bucket='bucket-name' target='/path/to/file.ext' expires='5' /]
 *
 * @since    1.0.0
 */
add_shortcode( 's3secureurl', array( 'S3_Secure_URL', 'sc_s3secureurl' ) );




