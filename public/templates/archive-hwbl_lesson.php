<?php
/**
 * Archive template for Bible Lessons.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$page = isset( $_GET['hwbl_page'] ) ? max( 1, absint( $_GET['hwbl_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

echo '<div class="hwbl-archive-lessons wrap">';
echo '<header class="hwbl-archive-header">';
echo '<h1 class="hwbl-archive-title">' . esc_html__( 'Bible Lessons', 'hidden-word-bible-lessons' ) . '</h1>';
echo '<p class="hwbl-archive-description">' . esc_html__( 'Browse the full bundled curriculum by book.', 'hidden-word-bible-lessons' ) . '</p>';
echo '</header>';

$hwbl_list_html = HWBL_Lesson_List::render(
	array(
		'group'    => 'book',
		'per_page' => 50,
		'show'     => 'both',
		'page'     => absint( $page ),
	)
);
echo $hwbl_list_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside HWBL_Lesson_List::render().

echo '</div>';

get_footer();
