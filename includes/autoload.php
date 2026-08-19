<?php
/**
 * PSR-4 autoloader for the DevForm namespace.
 *
 * The plugin ships no vendor directory, so this is the only autoloader.
 *
 * @package DevForm
 */

declare( strict_types = 1 );

namespace DevForm;

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = __NAMESPACE__ . '\\';

		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$path     = DEVFORM_PATH . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);
