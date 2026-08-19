<?php
/**
 * The version string lives in three places and a release is wrong if they drift.
 *
 * @package DevForm
 */

declare( strict_types = 1 );

namespace DevForm\Tests;

use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase {

	private function plugin_header( string $field ): string {
		$source = (string) file_get_contents( DEVFORM_TESTS_ROOT . '/devform.php' );

		$this->assertMatchesRegularExpression(
			'/^\s*\*\s*' . preg_quote( $field, '/' ) . ':\s*(.+)$/m',
			$source,
			"Plugin header is missing the {$field} field."
		);

		preg_match( '/^\s*\*\s*' . preg_quote( $field, '/' ) . ':\s*(.+)$/m', $source, $m );

		return trim( $m[1] );
	}

	private function readme_header( string $field ): string {
		$source = (string) file_get_contents( DEVFORM_TESTS_ROOT . '/readme.txt' );

		preg_match( '/^' . preg_quote( $field, '/' ) . ':\s*(.+)$/m', $source, $m );

		$this->assertNotEmpty( $m, "readme.txt is missing the {$field} header." );

		return trim( $m[1] );
	}

	public function test_header_version_matches_the_constant(): void {
		$this->assertSame( $this->plugin_header( 'Version' ), \DevForm\VERSION );
	}

	public function test_readme_stable_tag_matches_the_constant(): void {
		$this->assertSame( $this->readme_header( 'Stable tag' ), \DevForm\VERSION );
	}

	public function test_php_floor_agrees_between_header_readme_and_composer(): void {
		$composer = json_decode( (string) file_get_contents( DEVFORM_TESTS_ROOT . '/composer.json' ), true );

		$this->assertSame( $this->plugin_header( 'Requires PHP' ), $this->readme_header( 'Requires PHP' ) );
		$this->assertSame( '>=' . $this->plugin_header( 'Requires PHP' ), $composer['require']['php'] );
	}

	public function test_wordpress_floor_agrees_between_header_and_readme(): void {
		$this->assertSame( $this->plugin_header( 'Requires at least' ), $this->readme_header( 'Requires at least' ) );
	}

	public function test_tested_up_to_is_not_behind_the_floor(): void {
		$this->assertGreaterThanOrEqual(
			(float) $this->readme_header( 'Requires at least' ),
			(float) $this->readme_header( 'Tested up to' ),
			'Tested up to must not be lower than Requires at least.'
		);
	}
}
