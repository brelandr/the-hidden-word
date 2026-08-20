<?php
/**
 * Sample reading-plan seed (Anxiety / Worry + gospel shareable skeleton).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HWBL_Plan_Seed
 */
class HWBL_Plan_Seed {

	const OPT_SEEDED = 'hwbl_plan_sample_seeded';

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_seed' ) );
	}

	/**
	 * Seed sample plans; bump version to add Phase 6 / Phase 8 content without wiping earlier seeds.
	 */
	public static function maybe_seed() {
		$can = current_user_can( 'manage_options' ) || ( defined( 'WP_CLI' ) && WP_CLI );
		if ( ! $can ) {
			return;
		}
		$raw = get_option( self::OPT_SEEDED, '' );
		$ver = is_numeric( $raw ) ? (int) $raw : ( '1' === (string) $raw ? 1 : 0 );
		if ( $ver < 1 ) {
			self::seed_sample_plans();
			$ver = 1;
		}
		if ( $ver < 2 ) {
			self::seed_phase6_plans();
			$ver = 2;
		}
		if ( $ver < 3 ) {
			self::seed_phase8_rich_plans();
			$ver = 3;
		}
		if ( $ver < 4 ) {
			self::seed_phase9_life_plans();
			$ver = 4;
		}
		if ( $ver < 5 ) {
			self::seed_phase10_plans();
			$ver = 5;
		}
		if ( $ver < 6 ) {
			self::seed_phase11_plans();
			$ver = 6;
		}
		update_option( self::OPT_SEEDED, (string) $ver, false );
	}

	/**
	 * Phase 6 content: grief, new-believer, marriage, advent, lent, kids.
	 */
	public static function seed_phase6_plans() {
		self::ensure_plan(
			'Walking Through Grief (7 days)',
			'grief',
			false,
			array(
				array(
					'day'       => 1,
					'lesson_id' => null,
					'title'     => 'God is near',
					'body'      => '<p>Grief is heavy. Begin by telling God honestly how you feel—He draws near to the brokenhearted.</p>',
					'verse_ref' => 'Psalm 34:18',
				),
				array(
					'day'       => 2,
					'lesson_id' => null,
					'title'     => 'Jesus wept',
					'body'      => '<p>Jesus entered the sorrow of friends. Your tears are not a lack of faith.</p>',
					'verse_ref' => 'John 11:35',
				),
				array(
					'day'       => 3,
					'lesson_id' => null,
					'title'     => 'Comfort received and shared',
					'body'      => '<p>Ask God for comfort today, and notice one person who might need a gentle word.</p>',
					'verse_ref' => '2 Corinthians 1:3-4',
				),
				array(
					'day'       => 4,
					'lesson_id' => null,
					'title'     => 'Hope that holds',
					'body'      => '<p>Hope in Christ does not erase pain, but it keeps you from despair.</p>',
					'verse_ref' => '1 Thessalonians 4:13-14',
				),
				array(
					'day'       => 5,
					'lesson_id' => null,
					'title'     => 'Cast your cares',
					'body'      => '<p>Name one burden you are carrying and place it in God’s hands in prayer.</p>',
					'verse_ref' => '1 Peter 5:7',
				),
				array(
					'day'       => 6,
					'lesson_id' => null,
					'title'     => 'Strength for today',
					'body'      => '<p>You do not need strength for every tomorrow—ask for enough grace for this day.</p>',
					'verse_ref' => 'Lamentations 3:22-23',
				),
				array(
					'day'       => 7,
					'lesson_id' => null,
					'title'     => 'Future glory',
					'body'      => '<p>Hold the promise that present suffering is not the end of the story.</p>',
					'verse_ref' => 'Romans 8:18',
				),
			),
			'A gentle seven-day walk for those who are grieving.'
		);

		self::ensure_plan(
			'New Believer Foundations (7 days)',
			'new-believer',
			false,
			array(
				array(
					'day'       => 1,
					'lesson_id' => null,
					'title'     => 'You are a new creation',
					'body'      => '<p>In Christ, the old has gone and the new has come. Thank God for new life today.</p>',
					'verse_ref' => '2 Corinthians 5:17',
				),
				array(
					'day'       => 2,
					'lesson_id' => null,
					'title'     => 'Saved by grace',
					'body'      => '<p>You did not earn salvation. Rest in the gift of grace through faith.</p>',
					'verse_ref' => 'Ephesians 2:8-9',
				),
				array(
					'day'       => 3,
					'lesson_id' => null,
					'title'     => 'Abide in Jesus',
					'body'      => '<p>Stay close to Jesus through prayer and His Word—He is the vine; we are the branches.</p>',
					'verse_ref' => 'John 15:5',
				),
				array(
					'day'       => 4,
					'lesson_id' => null,
					'title'     => 'The Spirit helps',
					'body'      => '<p>You are not alone. The Holy Spirit helps you pray and grow.</p>',
					'verse_ref' => 'Romans 8:26',
				),
				array(
					'day'       => 5,
					'lesson_id' => null,
					'title'     => 'Belong to the church',
					'body'      => '<p>Find a local church family where you can worship, learn, and serve.</p>',
					'verse_ref' => 'Hebrews 10:24-25',
				),
				array(
					'day'       => 6,
					'lesson_id' => null,
					'title'     => 'Baptism and obedience',
					'body'      => '<p>Following Jesus means learning to obey Him in love—ask what next step He is inviting.</p>',
					'verse_ref' => 'Matthew 28:19-20',
				),
				array(
					'day'       => 7,
					'lesson_id' => null,
					'title'     => 'Share the hope',
					'body'      => '<p>Be ready to gently share why you hope in Christ with someone who asks.</p>',
					'verse_ref' => '1 Peter 3:15',
				),
			),
			'Seven days of first steps for new followers of Jesus.'
		);

		self::ensure_plan(
			'Marriage in Christ (7 days)',
			'marriage',
			false,
			array(
				array(
					'day'       => 1,
					'lesson_id' => null,
					'title'     => 'Love that lasts',
					'body'      => '<p>Read 1 Corinthians 13 slowly. Which quality of love do you most need to practice today?</p>',
					'verse_ref' => '1 Corinthians 13:4-7',
				),
				array(
					'day'       => 2,
					'lesson_id' => null,
					'title'     => 'Leave and cleave',
					'body'      => '<p>Marriage forms a new household. Talk together about loyalty and unity under God.</p>',
					'verse_ref' => 'Genesis 2:24',
				),
				array(
					'day'       => 3,
					'lesson_id' => null,
					'title'     => 'Mutual honor',
					'body'      => '<p>Submit to one another out of reverence for Christ—honor your spouse in word and deed.</p>',
					'verse_ref' => 'Ephesians 5:21',
				),
				array(
					'day'       => 4,
					'lesson_id' => null,
					'title'     => 'Quick to listen',
					'body'      => '<p>Practice listening before speaking in one conversation today.</p>',
					'verse_ref' => 'James 1:19',
				),
				array(
					'day'       => 5,
					'lesson_id' => null,
					'title'     => 'Forgive as forgiven',
					'body'      => '<p>Name one offense to release, and ask God for grace to forgive as He forgave you.</p>',
					'verse_ref' => 'Colossians 3:13',
				),
				array(
					'day'       => 6,
					'lesson_id' => null,
					'title'     => 'Pray together',
					'body'      => '<p>Pray briefly with your spouse (or for them if apart)—invite God into your home.</p>',
					'verse_ref' => 'Matthew 18:19-20',
				),
				array(
					'day'       => 7,
					'lesson_id' => null,
					'title'     => 'Build your house',
					'body'      => '<p>A wise home is built on the Lord. Choose one habit that strengthens your marriage this week.</p>',
					'verse_ref' => 'Psalm 127:1',
				),
			),
			'Seven days of Scripture for couples growing in Christ.'
		);

		self::ensure_plan(
			'Advent Walk (5 days)',
			'advent',
			false,
			array(
				array(
					'day'       => 1,
					'lesson_id' => null,
					'title'     => 'Hope',
					'body'      => '<p>Advent begins in hope. Watch for Christ’s coming with expectant hearts.</p>',
					'verse_ref' => 'Isaiah 9:2',
				),
				array(
					'day'       => 2,
					'lesson_id' => null,
					'title'     => 'Peace',
					'body'      => '<p>The Prince of Peace draws near. Ask Him to calm one restless place in your life.</p>',
					'verse_ref' => 'Isaiah 9:6',
				),
				array(
					'day'       => 3,
					'lesson_id' => null,
					'title'     => 'Joy',
					'body'      => '<p>Good news of great joy is for all people. Share a word of joy with someone today.</p>',
					'verse_ref' => 'Luke 2:10-11',
				),
				array(
					'day'       => 4,
					'lesson_id' => null,
					'title'     => 'Love',
					'body'      => '<p>God so loved the world that He gave His Son. Rest in that love.</p>',
					'verse_ref' => 'John 3:16',
				),
				array(
					'day'       => 5,
					'lesson_id' => null,
					'title'     => 'The Word made flesh',
					'body'      => '<p>Celebrate that God came near in Jesus—Emmanuel, God with us.</p>',
					'verse_ref' => 'John 1:14',
				),
			),
			'A short Advent walk: hope, peace, joy, love, and the Incarnation.'
		);

		self::ensure_plan(
			'Lent Walk (5 days)',
			'lent',
			false,
			array(
				array(
					'day'       => 1,
					'lesson_id' => null,
					'title'     => 'Return to the Lord',
					'body'      => '<p>Lent invites repentance. Turn again to God with your whole heart.</p>',
					'verse_ref' => 'Joel 2:12-13',
				),
				array(
					'day'       => 2,
					'lesson_id' => null,
					'title'     => 'Deny yourself',
					'body'      => '<p>Following Jesus costs something. What will you set aside to seek Him?</p>',
					'verse_ref' => 'Luke 9:23',
				),
				array(
					'day'       => 3,
					'lesson_id' => null,
					'title'     => 'Hunger for the Word',
					'body'      => '<p>Fast from distraction; feast on Scripture today.</p>',
					'verse_ref' => 'Matthew 4:4',
				),
				array(
					'day'       => 4,
					'lesson_id' => null,
					'title'     => 'The cross ahead',
					'body'      => '<p>Jesus set His face toward the cross for us. Thank Him for His steadfast love.</p>',
					'verse_ref' => 'Luke 9:51',
				),
				array(
					'day'       => 5,
					'lesson_id' => null,
					'title'     => 'New mercies',
					'body'      => '<p>Even in Lent, God’s mercies are new every morning. Receive grace for today.</p>',
					'verse_ref' => 'Lamentations 3:22-23',
				),
			),
			'A five-day Lent walk of repentance, discipline, and hope.'
		);

		self::ensure_plan(
			'Kids Bible Stories (7 days)',
			'kids',
			false,
			array(
				array(
					'day'       => 1,
					'lesson_id' => null,
					'title'     => 'God made everything',
					'body'      => '<p>God made the sky, the animals, and you! Thank Him for one thing He made.</p>',
					'verse_ref' => 'Genesis 1:1',
				),
				array(
					'day'       => 2,
					'lesson_id' => null,
					'title'     => 'Noah and the boat',
					'body'      => '<p>Noah trusted God and built a big boat. God keeps His promises.</p>',
					'verse_ref' => 'Genesis 9:13',
				),
				array(
					'day'       => 3,
					'lesson_id' => null,
					'title'     => 'David was brave',
					'body'      => '<p>David was small, but God helped him be brave. God helps you too.</p>',
					'verse_ref' => '1 Samuel 17:45',
				),
				array(
					'day'       => 4,
					'lesson_id' => null,
					'title'     => 'Jesus loves children',
					'body'      => '<p>Jesus welcomed kids. You are important to Him.</p>',
					'verse_ref' => 'Mark 10:14',
				),
				array(
					'day'       => 5,
					'lesson_id' => null,
					'title'     => 'The lost sheep',
					'body'      => '<p>Jesus looks for people who feel lost—like a shepherd finds a sheep.</p>',
					'verse_ref' => 'Luke 15:4-6',
				),
				array(
					'day'       => 6,
					'lesson_id' => null,
					'title'     => 'Jesus is alive',
					'body'      => '<p>Jesus died and rose again. That is the best news!</p>',
					'verse_ref' => 'Matthew 28:6',
				),
				array(
					'day'       => 7,
					'lesson_id' => null,
					'title'     => 'Go and tell',
					'body'      => '<p>Tell a friend one thing you learned about Jesus this week.</p>',
					'verse_ref' => 'Matthew 28:19',
				),
			),
			'Seven short Bible stories for kids (about ages 6–10).'
		);
	}

	/**
	 * Create sample plans if missing.
	 */
	public static function seed_sample_plans() {
		self::ensure_plan(
			'Anxiety / Worry (7 days)',
			'anxiety',
			false,
			array(
				array(
					'day'       => 1,
					'lesson_id' => null,
					'title'     => 'Cast your cares',
					'body'      => '<p>Begin by naming one worry and handing it to God in prayer.</p>',
					'verse_ref' => '1 Peter 5:7',
				),
				array(
					'day'       => 2,
					'lesson_id' => null,
					'title'     => 'Do not be anxious',
					'body'      => '<p>Read Philippians 4:6–7 slowly. What request will you bring today?</p>',
					'verse_ref' => 'Philippians 4:6-7',
				),
				array(
					'day'       => 3,
					'lesson_id' => null,
					'title'     => 'Peace that guards',
					'body'      => '<p>Notice how God’s peace guards the heart and mind in Christ Jesus.</p>',
					'verse_ref' => 'Philippians 4:7',
				),
				array(
					'day'       => 4,
					'lesson_id' => null,
					'title'     => 'Sufficient grace',
					'body'      => '<p>Where do you need God’s strength in weakness today?</p>',
					'verse_ref' => '2 Corinthians 12:9',
				),
				array(
					'day'       => 5,
					'lesson_id' => null,
					'title'     => 'Trust, not fear',
					'body'      => '<p>Replace one fearful thought with a promise from Scripture.</p>',
					'verse_ref' => 'Isaiah 41:10',
				),
				array(
					'day'       => 6,
					'lesson_id' => null,
					'title'     => 'Rest for the weary',
					'body'      => '<p>Come to Jesus with your burden. What would it look like to rest in Him?</p>',
					'verse_ref' => 'Matthew 11:28-30',
				),
				array(
					'day'       => 7,
					'lesson_id' => null,
					'title'     => 'Keep asking',
					'body'      => '<p>Finish by writing a short prayer of thanksgiving for God’s care.</p>',
					'verse_ref' => 'Matthew 6:25-34',
				),
			),
			'A seven-day walk through Scripture for anxious hearts—casting cares on God, receiving His peace, and practicing trust one day at a time.'
		);

		self::ensure_plan(
			'The Gospel (Romans Road)',
			'gospel',
			true,
			array(
				array(
					'day'       => 1,
					'lesson_id' => null,
					'title'     => 'All have sinned',
					'body'      => '<p>Every person has fallen short of God’s glory. We need a Savior.</p>',
					'verse_ref' => 'Romans 3:23',
				),
				array(
					'day'       => 2,
					'lesson_id' => null,
					'title'     => 'The wage of sin',
					'body'      => '<p>Sin earns death — but God offers a free gift of eternal life in Christ.</p>',
					'verse_ref' => 'Romans 6:23',
				),
				array(
					'day'       => 3,
					'lesson_id' => null,
					'title'     => 'Christ died for us',
					'body'      => '<p>While we were still sinners, Christ died for us.</p>',
					'verse_ref' => 'Romans 5:8',
				),
				array(
					'day'       => 4,
					'lesson_id' => null,
					'title'     => 'Confess and believe',
					'body'      => '<p>If you confess Jesus as Lord and believe God raised Him, you will be saved.</p>',
					'verse_ref' => 'Romans 10:9-10',
				),
				array(
					'day'       => 5,
					'lesson_id' => null,
					'title'     => 'No condemnation',
					'body'      => '<p>In Christ there is no condemnation — only new life by the Spirit.</p>',
					'verse_ref' => 'Romans 8:1',
				),
			),
			'A clear path through the gospel from Romans—sin, the cross, faith, and new life in Christ. Shareable for someone exploring faith.'
		);

		self::ensure_plan(
			'Faith Foundations',
			'foundations',
			false,
			array(
				array(
					'day'       => 1,
					'lesson_id' => null,
					'title'     => 'Who is God?',
					'body'      => '<p>God is the Creator — holy, loving, and worthy of worship.</p>',
					'verse_ref' => 'Genesis 1:1',
				),
				array(
					'day'       => 2,
					'lesson_id' => null,
					'title'     => 'Who is Jesus?',
					'body'      => '<p>Jesus is the Son of God who came to seek and save the lost.</p>',
					'verse_ref' => 'John 1:1-14',
				),
				array(
					'day'       => 3,
					'lesson_id' => null,
					'title'     => 'What is the Bible?',
					'body'      => '<p>Scripture is God-breathed and useful for teaching, correcting, and training in righteousness.</p>',
					'verse_ref' => '2 Timothy 3:16-17',
				),
				array(
					'day'       => 4,
					'lesson_id' => null,
					'title'     => 'What is prayer?',
					'body'      => '<p>Prayer is talking with God — with thanksgiving, confession, and petition.</p>',
					'verse_ref' => 'Philippians 4:6',
				),
				array(
					'day'       => 5,
					'lesson_id' => null,
					'title'     => 'The Church',
					'body'      => '<p>Believers gather to worship, grow, and serve together as Christ’s body.</p>',
					'verse_ref' => 'Hebrews 10:24-25',
				),
			),
			'Five days of core Christian foundations—who God is, who Jesus is, Scripture, prayer, and the church.'
		);
	}

	/**
	 * Create a published plan if title does not already exist.
	 *
	 * @param string                         $title     Title.
	 * @param string                         $topic     Topic.
	 * @param bool                           $shareable Shareable flag.
	 * @param array<int, array<string,mixed>> $days      Days.
	 * @param string                         $excerpt   Excerpt.
	 * @return int Plan ID.
	 */
	private static function ensure_plan( $title, $topic, $shareable, $days, $excerpt ) {
		$existing = get_posts(
			array(
				'post_type'      => HWBL_CPT_Plan::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				's'              => $title,
			)
		);
		foreach ( $existing as $eid ) {
			if ( get_the_title( $eid ) === $title ) {
				return (int) $eid;
			}
		}

		return self::upsert_plan( $title, $topic, $shareable, $days, $excerpt );
	}

	/**
	 * Create or update a plan by exact title.
	 *
	 * @param string                         $title     Title.
	 * @param string                         $topic     Topic.
	 * @param bool                           $shareable Shareable flag.
	 * @param array<int, array<string,mixed>> $days      Days.
	 * @param string                         $excerpt   Excerpt.
	 * @return int Plan ID.
	 */
	public static function upsert_plan( $title, $topic, $shareable, $days, $excerpt ) {
		$plan_id  = 0;
		$existing = get_posts(
			array(
				'post_type'      => HWBL_CPT_Plan::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				's'              => $title,
			)
		);
		foreach ( $existing as $eid ) {
			if ( get_the_title( $eid ) === $title ) {
				$plan_id = (int) $eid;
				break;
			}
		}

		$postarr = array(
			'post_type'    => HWBL_CPT_Plan::POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => $excerpt,
			'post_content' => $excerpt,
		);
		if ( $plan_id > 0 ) {
			$postarr['ID'] = $plan_id;
			$updated       = wp_update_post( $postarr, true );
			if ( is_wp_error( $updated ) ) {
				return $plan_id;
			}
		} else {
			$plan_id = wp_insert_post( $postarr, true );
			if ( is_wp_error( $plan_id ) || ! $plan_id ) {
				return 0;
			}
			$plan_id = (int) $plan_id;
		}

		$days = HWBL_CPT_Plan::sanitize_days( $days );
		update_post_meta( $plan_id, HWBL_CPT_Plan::META_TOPIC, sanitize_key( $topic ) );
		update_post_meta( $plan_id, HWBL_CPT_Plan::META_SHAREABLE, $shareable ? 1 : 0 );
		update_post_meta( $plan_id, HWBL_CPT_Plan::META_DAYS, $days );
		update_post_meta( $plan_id, HWBL_CPT_Plan::META_LENGTH, count( $days ) );
		return $plan_id;
	}

	/**
	 * Phase 8: rich study-voice plans (new + upgrades to thin samples).
	 */
	public static function seed_phase8_rich_plans() {
		$path = HWBL_PLUGIN_DIR . 'includes/data/plan-seed-phase8.php';
		if ( ! is_readable( $path ) ) {
			return;
		}
		require_once $path;
		if ( ! function_exists( 'hwbl_plan_seed_phase8_definitions' ) ) {
			return;
		}
		foreach ( hwbl_plan_seed_phase8_definitions() as $plan ) {
			if ( empty( $plan['title'] ) || empty( $plan['days'] ) ) {
				continue;
			}
			self::upsert_plan(
				(string) $plan['title'],
				(string) ( $plan['topic'] ?? 'other' ),
				! empty( $plan['shareable'] ),
				(array) $plan['days'],
				(string) ( $plan['excerpt'] ?? '' )
			);
		}
	}

	/**
	 * Phase 9: life-issues rich plans (health, addiction, finances, DV, doubt, etc.).
	 */
	public static function seed_phase9_life_plans() {
		$path = HWBL_PLUGIN_DIR . 'includes/data/plan-seed-phase9.php';
		if ( ! is_readable( $path ) ) {
			return;
		}
		require_once $path;
		if ( ! function_exists( 'hwbl_plan_seed_phase9_definitions' ) ) {
			return;
		}
		foreach ( hwbl_plan_seed_phase9_definitions() as $plan ) {
			if ( empty( $plan['title'] ) || empty( $plan['days'] ) ) {
				continue;
			}
			self::upsert_plan(
				(string) $plan['title'],
				(string) ( $plan['topic'] ?? 'other' ),
				! empty( $plan['shareable'] ),
				(array) $plan['days'],
				(string) ( $plan['excerpt'] ?? '' )
			);
		}
	}

	/**
	 * Phase 10: upgrade thin samples + parenting / character plans.
	 */
	public static function seed_phase10_plans() {
		$path = HWBL_PLUGIN_DIR . 'includes/data/plan-seed-phase10.php';
		if ( ! is_readable( $path ) ) {
			return;
		}
		require_once $path;
		if ( ! function_exists( 'hwbl_plan_seed_phase10_definitions' ) ) {
			return;
		}
		foreach ( hwbl_plan_seed_phase10_definitions() as $plan ) {
			if ( empty( $plan['title'] ) || empty( $plan['days'] ) ) {
				continue;
			}
			self::upsert_plan(
				(string) $plan['title'],
				(string) ( $plan['topic'] ?? 'other' ),
				! empty( $plan['shareable'] ),
				(array) $plan['days'],
				(string) ( $plan['excerpt'] ?? '' )
			);
		}
	}

	/**
	 * Phase 11: chronological overview + formation themes (waiting, rest, etc.).
	 */
	public static function seed_phase11_plans() {
		$path = HWBL_PLUGIN_DIR . 'includes/data/plan-seed-phase11.php';
		if ( ! is_readable( $path ) ) {
			return;
		}
		require_once $path;
		if ( ! function_exists( 'hwbl_plan_seed_phase11_definitions' ) ) {
			return;
		}
		foreach ( hwbl_plan_seed_phase11_definitions() as $plan ) {
			if ( empty( $plan['title'] ) || empty( $plan['days'] ) ) {
				continue;
			}
			self::upsert_plan(
				(string) $plan['title'],
				(string) ( $plan['topic'] ?? 'other' ),
				! empty( $plan['shareable'] ),
				(array) $plan['days'],
				(string) ( $plan['excerpt'] ?? '' )
			);
		}
	}
}
