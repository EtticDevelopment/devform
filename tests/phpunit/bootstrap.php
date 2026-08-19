<?php
/**
 * Test bootstrap.
 *
 * Plain unit tests, WordPress is not loaded. The plugin's own entry file is
 * loaded against the few core functions it touches, so the real constant
 * definitions and the real autoloader are what the tests exercise.
 *
 * @package DevForm
 */

declare( strict_types = 1 );

define( 'DEVFORM_TESTS_ROOT', dirname( __DIR__, 2 ) );

require_once DEVFORM_TESTS_ROOT . '/vendor/autoload.php';

define( 'ABSPATH', DEVFORM_TESTS_ROOT . '/' );

/**
 * Hooks recorded by the shims, so a test can assert what the plugin registered.
 *
 * @var array<string, array<int, callable>>
 */
$GLOBALS['devform_test_hooks'] = array();

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( string $file ): string {
		return rtrim( dirname( $file ), '/\\' ) . '/';
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( string $file ): string {
		return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, callable $callback, int $priority = 10, int $args = 1 ): bool {
		$GLOBALS['devform_test_hooks'][ $hook ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( string $hook, ...$args ): void {
		foreach ( $GLOBALS['devform_test_hooks'][ $hook ] ?? array() as $callback ) {
			$callback( ...$args );
		}
	}
}

require_once DEVFORM_TESTS_ROOT . '/devform.php';
