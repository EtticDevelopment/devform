<?php
/**
 * Plugin Name:       DevForm
 * Plugin URI:        https://github.com/EtticDevelopment/devform
 * Description:       Forms and submission endpoints for developers. Define a form in your theme, own the markup, own the data, and run an ordered stack of post-submit actions.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Author:            Ettic
 * Author URI:        https://ettic.nl
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       devform
 *
 * @package DevForm
 */

declare( strict_types = 1 );

namespace DevForm;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.1.0';

define( 'DEVFORM_VERSION', VERSION );
define( 'DEVFORM_FILE', __FILE__ );
define( 'DEVFORM_PATH', plugin_dir_path( __FILE__ ) );
define( 'DEVFORM_URL', plugin_dir_url( __FILE__ ) );

require_once DEVFORM_PATH . 'includes/autoload.php';

add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );

/**
 * Boot the plugin once WordPress has loaded.
 */
function bootstrap(): void {
	/**
	 * Fires once DevForm has loaded and forms may be registered.
	 *
	 * @since 0.1.0
	 */
	do_action( 'devform_init' );
}
