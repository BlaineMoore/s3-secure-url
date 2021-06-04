<?php
/**
 * S3 Secure URL WordPress plugin
 *
 * @package   S3_Secure_URL
 * @author    Max Kostinevich <hello@maxkostinevich.com>
 * @license   GPL-2.0+
 * @link      http://maxkostinevich.com
 * @copyright 2015 Max Kostinevich
 *
 * @wordpress-plugin	
 * Plugin Name:       S3 Secure URL
 * Plugin URI:        https://github.com/BlaineMoore/s3-secure-url
 * Description:       Create temporary secure URLs to protected Amazon S3 files.
 * Version:           1.2.0
 * Author:            Max Kostinevich (updates by Blaine Moore)
 * Author URI:        https://github.com/BlaineMoore/s3-secure-url
 * Text Domain:       s3-secure-url-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: BlaineMoore/s3-secure-url
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-s3-secure-url.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'S3_Secure_URL', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'S3_Secure_URL', 'deactivate' ) );


add_action( 'plugins_loaded', array( 'S3_Secure_URL', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-s3-secure-url-admin.php' );
	add_action( 'plugins_loaded', array( 'S3_Secure_URL_Admin', 'get_instance' ) );

}
