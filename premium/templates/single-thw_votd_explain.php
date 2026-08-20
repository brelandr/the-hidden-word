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
				<?php
				if (
					class_exists( 'THW_Premium_Votd_Explain_Store' )
					&& THW_Premium_Votd_Explain_Store::is_featured_image_enabled()
					&& has_post_thumbnail()
				) {
					// Tell the_content filter not to prepend a second copy.
					$GLOBALS['thw_votd_explain_featured_rendered'] = true; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
					echo THW_Premium_Votd_Explain_Store::render_featured_image_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper returns escaped HTML.
				}
				?>
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
