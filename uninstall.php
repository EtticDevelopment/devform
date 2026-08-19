<?php
/**
 * Uninstall handler.
 *
 * DevForm keeps submission data on uninstall by default. Entries are the site
 * owner's records, and a plugin removal is not consent to destroy them. Removal
 * is an explicit, separate action in the settings screen.
 *
 * @package DevForm
 */

declare( strict_types = 1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
