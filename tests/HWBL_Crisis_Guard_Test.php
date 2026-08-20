<?php
/**
 * Crisis guard phrase detection tests.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function wp_strip_all_tags( $text ) {
		return strip_tags( (string) $text );
	}
}
if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_html_e' ) ) {
	/**
	 * @param string $text Text.
	 */
	function esc_html_e( $text ) {
		echo htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * @param string $tag   Hook.
	 * @param mixed  $value Value.
	 * @return mixed
	 */
	function apply_filters( $tag, $value ) {
		return $value;
	}
}

require_once dirname( __DIR__ ) . '/includes/class-crisis-guard.php';

$pass  = 0;
$total = 0;

/**
 * @param bool   $cond  Condition.
 * @param string $label Label.
 */
function hwbl_assert( $cond, $label ) {
	global $pass, $total;
	$total++;
	if ( $cond ) {
		$pass++;
		echo "OK  $label\n";
	} else {
		echo "FAIL $label\n";
	}
}

hwbl_assert( HWBL_Crisis_Guard::is_crisis( 'I want to kill myself tonight' ), 'detects kill myself' );
hwbl_assert( HWBL_Crisis_Guard::is_crisis( 'thinking about suicide' ), 'detects suicide' );
hwbl_assert( ! HWBL_Crisis_Guard::is_crisis( 'I feel anxious about work' ), 'ignores ordinary anxiety' );
hwbl_assert( false !== strpos( HWBL_Crisis_Guard::helpline_plain(), '988' ), 'helpline includes 988' );
hwbl_assert( false !== strpos( HWBL_Crisis_Guard::helpline_html(), 'iasp.info' ), 'html includes IASP' );

echo "Passed $pass / $total\n";
exit( $pass === $total ? 0 : 1 );
