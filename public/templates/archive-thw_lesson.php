<?php
/**
 * Archive template for Bible Lessons.
 *
 * @package The_Hidden_Word
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$page = isset( $_GET['thw_page'] ) ? max( 1, absint( $_GET['thw_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

echo '<div class="thw-archive-lessons wrap">';
echo '<header class="thw-archive-header">';
echo '<h1 class="thw-archive-title">' . esc_html__( 'Bible Lessons', 'the-hidden-word' ) . '</h1>';
echo '<p class="thw-archive-description">' . esc_html__( 'Browse the full bundled curriculum by book.', 'the-hidden-word' ) . '</p>';
echo '</header>';

echo THW_Lesson_List::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'group'    => 'book',
		'per_page' => 50,
		'show'     => 'both',
		'page'     => $page,
	)
);

echo '</div>';

get_footer();
