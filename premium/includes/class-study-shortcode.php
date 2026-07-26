<?php
/**
 * Study finder shortcode markup (extracted from AI study finder).
 *
 * @package The_Hidden_Word_Premium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy thw_/THW_ identifiers retained for shortcode/option backward compatibility.


/**
 * Class THW_Premium_Study_Shortcode
 */
class THW_Premium_Study_Shortcode {

	/**
	 * Render study finder shortcode.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts ) {
		$features_enabled = function_exists( 'hwbl_premium_features_enabled' )
			? hwbl_premium_features_enabled()
			: ( class_exists( 'THW_Premium_License' ) && THW_Premium_License::is_licensed() );

		if ( ! $features_enabled ) {
			return '<p class="thw-notice thw-notice-info">' . esc_html__( 'Bible study search requires an active The Hidden Word Premium license.', 'hidden-word-bible-lessons' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'title'        => __( 'Find a Bible Study by Topic', 'hidden-word-bible-lessons' ),
				'placeholder'  => __( 'Enter a topic (hope, divorce, peace…)', 'hidden-word-bible-lessons' ),
				'button_label' => __( 'Search Scripture', 'hidden-word-bible-lessons' ),
			),
			$atts,
			'thw_study_finder'
		);

		ob_start();
		?>
		<div class="thw-study-finder" data-thw-study-finder>
			<?php if ( $atts['title'] ) : ?>
				<h2 class="thw-study-finder__title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>
			<form class="thw-study-finder__form" action="#" method="post">
				<?php
				thw_premium_the_tradition_select(
					array(
						'id'    => 'thw-ai-tradition-study',
						'class' => 'thw-ai-tradition-select thw-study-finder__tradition',
					)
				);
				?>
				<label class="screen-reader-text" for="thw-study-keywords"><?php esc_html_e( 'Topic', 'hidden-word-bible-lessons' ); ?></label>
				<input
					type="search"
					id="thw-study-keywords"
					class="thw-study-finder__input"
					name="keywords"
					placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
					required
				/>
				<button type="submit" class="thw-btn thw-study-finder__submit"><?php echo esc_html( $atts['button_label'] ); ?></button>
			</form>
			<div class="thw-study-finder__status" role="status" aria-live="polite"></div>
			<div class="thw-study-finder__results"></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
