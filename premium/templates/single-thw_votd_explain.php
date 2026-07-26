<?php
/**
 * Fallback single template for VOTD explanations (themes without single.php).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


get_header();
?>
<main id="primary" class="thw-main thw-main--page">
	<div class="thw-container thw-content">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'thw-article thw-article--votd-explain' ); ?>>
				<header class="thw-article__header">
					<h1 class="thw-article__title"><?php the_title(); ?></h1>
				</header>
				<div class="thw-article__body entry-content thw-votd-explain">
					<?php
					// Goes through THW_Premium_Votd_Explain_Store::filter_singular_content()
					// so markdown fences / plain-text headings are repaired for display.
					the_content();
					?>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
