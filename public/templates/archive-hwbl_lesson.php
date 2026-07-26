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

echo '<div class="hwbl-archive-lessons wrap">';
echo '<header class="hwbl-archive-header">';
echo '<h1 class="hwbl-archive-title">' . esc_html__( 'Bible Lessons', 'hidden-word-bible-lessons' ) . '</h1>';
echo '<p class="hwbl-archive-description">' . esc_html__( 'Browse the full bundled curriculum by book.', 'hidden-word-bible-lessons' ) . '</p>';
echo '</header>';

// Pagination is read and sanitized inside HWBL_Lesson_List::render().
$hwbl_list_html = HWBL_Lesson_List::render(
	array(
		'group'    => 'book',
		'per_page' => 50,
		'show'     => 'both',
	)
);
echo wp_kses_post( $hwbl_list_html );

echo '</div>';

get_footer();
