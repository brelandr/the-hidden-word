<?php
/**
 * PHPUnit bootstrap.
 *
 * @package The_Hidden_Word
 */

define( 'THW_TESTS', true );
define( 'THW_VERSION', '1.0.0' );
define( 'THW_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'THW_PLUGIN_URL', 'http://example.org/wp-content/plugins/the-hidden-word/' );
define( 'THW_PLUGIN_BASENAME', 'the-hidden-word/the-hidden-word.php' );
define( 'THW_MAX_BUNDLED_VERSES', 500 );

require_once THW_PLUGIN_DIR . 'includes/class-books.php';
require_once THW_PLUGIN_DIR . 'includes/interface-translation-provider.php';
require_once THW_PLUGIN_DIR . 'includes/class-bundled-provider.php';
require_once THW_PLUGIN_DIR . 'includes/class-translation-service.php';
require_once THW_PLUGIN_DIR . 'includes/class-scheduler.php';
