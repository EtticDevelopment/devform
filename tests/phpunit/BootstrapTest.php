<?php
/**
 * The entry file must define its constants and register its init hook.
 *
 * @package DevForm
 */

declare( strict_types = 1 );

namespace DevForm\Tests;

use PHPUnit\Framework\TestCase;

final class BootstrapTest extends TestCase {

	public function test_entry_file_defines_its_constants(): void {
		$this->assertTrue( defined( 'DEVFORM_VERSION' ) );
		$this->assertTrue( defined( 'DEVFORM_FILE' ) );
		$this->assertTrue( defined( 'DEVFORM_PATH' ) );
		$this->assertTrue( defined( 'DEVFORM_URL' ) );
	}

	public function test_path_constant_points_at_the_plugin_root(): void {
		$this->assertFileExists( DEVFORM_PATH . 'devform.php' );
		$this->assertStringEndsWith( '/', DEVFORM_PATH );
	}

	public function test_it_registers_a_bootstrap_callback_on_plugins_loaded(): void {
		$this->assertArrayHasKey( 'plugins_loaded', $GLOBALS['devform_test_hooks'] );
		$this->assertNotEmpty( $GLOBALS['devform_test_hooks']['plugins_loaded'] );
	}

	public function test_bootstrap_fires_the_public_init_hook(): void {
		$fired = false;

		add_action(
			'devform_init',
			static function () use ( &$fired ): void {
				$fired = true;
			}
		);

		do_action( 'plugins_loaded' );

		$this->assertTrue( $fired, 'devform_init should fire once the plugin has booted.' );
	}

	public function test_autoloader_ignores_classes_outside_the_namespace(): void {
		$this->assertFalse( class_exists( 'SomeOtherPlugin\\Thing', true ) );
	}
}
