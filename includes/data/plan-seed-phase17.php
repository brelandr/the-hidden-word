<?php
/**
 * Phase 17: second plans for love, community, Spirit, Word, and leadership.
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hwbl_plan_day_body' ) ) {
	/**
	 * Build a four-beat day body (open, text, heart, practice).
	 *
	 * @param string $open     Pastoral hook.
	 * @param string $text     Exposition.
	 * @param string $heart    Heart/application.
	 * @param string $practice Practice + prayer prompt.
	 * @return string
	 */
	function hwbl_plan_day_body( $open, $text, $heart, $practice ) {
		return '<p>' . $open . '</p><p>' . $text . '</p><p>' . $heart . '</p><p>' . $practice . '</p>';
	}
}

/**
 * Phase 17 plan payloads for upsert.
 *
 * @return array<int, array<string, mixed>>
 */
function hwbl_plan_seed_phase17_definitions() {
	return array(
		array(
			'title'     => 'Love Never Ends (7 days)',
			'topic'     => 'love',
			'shareable' => false,
			'excerpt'   => 'A second love plan—patient love, enemy love, and love that never ends.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Love never ends',
					'verse_ref' => '1 Corinthians 13:8',
					'body'      => hwbl_plan_day_body(
						'Love outlasts gifts and impressiveness.',
						'“Love never ends. As for prophecies, they will pass away…”',
						'Where have you chased what ends more than love?',
						'<strong>Practice:</strong> Choose one lasting loving act. <em>Lord, teach me love that never ends. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Love is patient',
					'verse_ref' => '1 Corinthians 13:4',
					'body'      => hwbl_plan_day_body(
						'Patience is love’s first description.',
						'“Love is patient and kind; love does not envy or boast…”',
						'Where do you need patient love today?',
						'<strong>Practice:</strong> Practice patience in one irritation. <em>Lord, make my love patient. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Love your neighbor',
					'verse_ref' => 'Mark 12:31',
					'body'      => hwbl_plan_day_body(
						'Neighbor-love is the second great command.',
						'“You shall love your neighbor as yourself.”',
						'Who is your neighbor in reach today?',
						'<strong>Practice:</strong> Meet one neighbor need concretely. <em>Lord, I love my neighbor. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Love your enemies',
					'verse_ref' => 'Matthew 5:44',
					'body'      => hwbl_plan_day_body(
						'Enemy-love reveals the Father’s heart.',
						'“But I say to you, Love your enemies and pray for those who persecute you.”',
						'Whom will you pray for that is hard?',
						'<strong>Practice:</strong> Pray blessing over an enemy. <em>Father, teach me to love enemies. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'As I have loved you',
					'verse_ref' => 'John 13:34',
					'body'      => hwbl_plan_day_body(
						'Jesus sets the measure of love.',
						'“A new commandment I give to you, that you love one another: just as I have loved you…”',
						'How did Jesus love—and how can you copy that?',
						'<strong>Practice:</strong> Serve someone as Jesus would. <em>Jesus, help me love as You loved. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Love covers',
					'verse_ref' => '1 Peter 4:8',
					'body'      => hwbl_plan_day_body(
						'Earnest love covers a multitude of sins.',
						'“Above all, keep loving one another earnestly, since love covers a multitude of sins.”',
						'Where can earnest love cover rather than expose?',
						'<strong>Practice:</strong> Cover one offense with grace. <em>Lord, grow earnest love in me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'The greatest is love',
					'verse_ref' => '1 Corinthians 13:13',
					'body'      => hwbl_plan_day_body(
						'Faith and hope remain; love is greatest.',
						'“So now faith, hope, and love abide, these three; but the greatest of these is love.”',
						'How will you prioritize love today?',
						'<strong>Practice:</strong> Let love set your schedule once. <em>God, make love greatest in me. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Bear One Another’s Burdens (7 days)',
			'topic'     => 'community',
			'shareable' => false,
			'excerpt'   => 'A second community plan—belonging, burden-bearing, and life together in Christ.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Bear one another’s burdens',
					'verse_ref' => 'Galatians 6:2',
					'body'      => hwbl_plan_day_body(
						'Community fulfills Christ’s law by shared burdens.',
						'“Bear one another’s burdens, and so fulfill the law of Christ.”',
						'Whose burden can you help carry?',
						'<strong>Practice:</strong> Take one burden-sharing action. <em>Christ, teach me to bear burdens. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Not neglecting to meet',
					'verse_ref' => 'Hebrews 10:24-25',
					'body'      => hwbl_plan_day_body(
						'Gathering stirs love and good works.',
						'“And let us consider how to stir up one another to love and good works, not neglecting to meet together…”',
						'Have you neglected meeting?',
						'<strong>Practice:</strong> Show up and encourage one person. <em>Lord, stir love through me. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'One body, many members',
					'verse_ref' => '1 Corinthians 12:12',
					'body'      => hwbl_plan_day_body(
						'Belonging means different gifts, one body.',
						'“For just as the body is one and has many members… so it is with Christ.”',
						'Where do you withdraw instead of belonging?',
						'<strong>Practice:</strong> Use your gift for the body once. <em>Christ, plant me in Your body. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Confess and pray',
					'verse_ref' => 'James 5:16',
					'body'      => hwbl_plan_day_body(
						'Healing community includes honest prayer.',
						'“Confess your sins to one another and pray for one another, that you may be healed.”',
						'What can you safely share?',
						'<strong>Practice:</strong> Ask one believer to pray with you. <em>God, heal us together. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Speak truth in love',
					'verse_ref' => 'Ephesians 4:15',
					'body'      => hwbl_plan_day_body(
						'Growth happens in truthful love.',
						'“Speaking the truth in love, we are to grow up in every way into him…”',
						'What truth needs love’s tone in community?',
						'<strong>Practice:</strong> Speak one loving truth carefully. <em>Lord, help me speak truth in love. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Welcome one another',
					'verse_ref' => 'Romans 15:7',
					'body'      => hwbl_plan_day_body(
						'Welcome mirrors Christ’s welcome.',
						'“Therefore welcome one another as Christ has welcomed you, for the glory of God.”',
						'Whom can you welcome who feels outside?',
						'<strong>Practice:</strong> Welcome someone warmly today. <em>Christ, help me welcome as You welcomed. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'They had all things in common',
					'verse_ref' => 'Acts 2:44-47',
					'body'      => hwbl_plan_day_body(
						'Early church joy included shared life and generosity.',
						'“And all who believed were together and had all things in common… praising God and having favor with all the people.”',
						'What shared life step can you take?',
						'<strong>Practice:</strong> Share time, meal, or resource. <em>Lord, grow Acts 2 life among us. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Enough in Christ (7 days)',
			'topic'     => 'contentment',
			'shareable' => false,
			'excerpt'   => 'A second contentment plan—enough in Christ, freedom from coveting, and learned contentment.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Godliness with contentment',
					'verse_ref' => '1 Timothy 6:6',
					'body'      => hwbl_plan_day_body(
						'Great gain is godliness paired with contentment.',
						'“But godliness with contentment is great gain.”',
						'Where has discontent redefined gain?',
						'<strong>Practice:</strong> Thank God for enough today. <em>Lord, teach me content godliness. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'I have learned',
					'verse_ref' => 'Philippians 4:11-13',
					'body'      => hwbl_plan_day_body(
						'Contentment is learned in Christ who strengthens.',
						'“I have learned in whatever situation I am to be content… I can do all things through him who strengthens me.”',
						'What situation is your classroom?',
						'<strong>Practice:</strong> Ask Christ for strength to be content there. <em>Christ, strengthen contentment in me. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Do not covet',
					'verse_ref' => 'Exodus 20:17',
					'body'      => hwbl_plan_day_body(
						'Coveting steals joy from present gifts.',
						'“You shall not covet… anything that is your neighbor’s.”',
						'What neighbor’s portion are you coveting?',
						'<strong>Practice:</strong> Bless that person and release coveting. <em>Lord, free me from coveting. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Be content with what you have',
					'verse_ref' => 'Hebrews 13:5',
					'body'      => hwbl_plan_day_body(
						'Contentment rests on God’s unfailing presence.',
						'“Keep your life free from love of money, and be content with what you have, for he has said, ‘I will never leave you nor forsake you.’”',
						'Is money-love fueling discontent?',
						'<strong>Practice:</strong> Fast one purchase desire today. <em>God, You will not forsake me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'The Lord is my shepherd',
					'verse_ref' => 'Psalm 23:1',
					'body'      => hwbl_plan_day_body(
						'Shepherd-care means “I shall not want.”',
						'“The Lord is my shepherd; I shall not want.”',
						'Where do you feel want that denies His shepherding?',
						'<strong>Practice:</strong> Pray Psalm 23:1 slowly. <em>Shepherd, I shall not want. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Seek first',
					'verse_ref' => 'Matthew 6:33',
					'body'      => hwbl_plan_day_body(
						'Kingdom-first living untangles anxious want.',
						'“But seek first the kingdom of God and his righteousness, and all these things will be added to you.”',
						'What “thing” has become first?',
						'<strong>Practice:</strong> Put kingdom first in one choice. <em>Father, I seek You first. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Give us this day',
					'verse_ref' => 'Matthew 6:11',
					'body'      => hwbl_plan_day_body(
						'Daily bread trains daily contentment.',
						'“Give us this day our daily bread.”',
						'Can you trust God for today’s portion?',
						'<strong>Practice:</strong> Ask only for today’s bread and give thanks. <em>Father, give us this day. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Cheerful Giver (7 days)',
			'topic'     => 'generosity',
			'shareable' => false,
			'excerpt'   => 'A second generosity plan—cheerful giving, open hands, and treasure in heaven.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'God loves a cheerful giver',
					'verse_ref' => '2 Corinthians 9:7',
					'body'      => hwbl_plan_day_body(
						'Giving is worship when cheerful, not coerced.',
						'“Each one must give as he has decided in his heart, not reluctantly or under compulsion, for God loves a cheerful giver.”',
						'Is your giving cheerful?',
						'<strong>Practice:</strong> Give one gift with gladness. <em>Lord, make me a cheerful giver. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'It is more blessed to give',
					'verse_ref' => 'Acts 20:35',
					'body'      => hwbl_plan_day_body(
						'Jesus’ words reverse the world’s math.',
						'“…remembering the words of the Lord Jesus, how he himself said, ‘It is more blessed to give than to receive.’”',
						'Where can you choose the more blessed path?',
						'<strong>Practice:</strong> Give without announcing. <em>Jesus, I trust Your blessing in giving. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Store up treasure in heaven',
					'verse_ref' => 'Matthew 6:19-21',
					'body'      => hwbl_plan_day_body(
						'Treasure location reveals heart location.',
						'“Do not lay up for yourselves treasures on earth… but lay up for yourselves treasures in heaven… For where your treasure is, there your heart will be also.”',
						'Where is your treasure migrating?',
						'<strong>Practice:</strong> Move one resource toward heaven’s priorities. <em>Lord, relocate my treasure. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Open your hand',
					'verse_ref' => 'Deuteronomy 15:7-8',
					'body'      => hwbl_plan_day_body(
						'Open hands toward the needy mirror God’s heart.',
						'“…you shall not harden your heart or shut your hand against your poor brother, but you shall open your hand to him…”',
						'Whose need can your open hand meet?',
						'<strong>Practice:</strong> Open your hand to one need. <em>Lord, keep my hand open. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Sow bountifully',
					'verse_ref' => '2 Corinthians 9:6',
					'body'      => hwbl_plan_day_body(
						'Scarcity-sowing shrinks harvest imagination.',
						'“Whoever sows sparingly will also reap sparingly, and whoever sows bountifully will also reap bountifully.”',
						'Where are you sowing sparingly from fear?',
						'<strong>Practice:</strong> Sow one bountiful seed. <em>God, I sow in trust. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Freely you received',
					'verse_ref' => 'Matthew 10:8',
					'body'      => hwbl_plan_day_body(
						'Grace received becomes grace given.',
						'“You received without paying; give without pay.”',
						'What free gift of grace can you pass on?',
						'<strong>Practice:</strong> Give time or skill freely once. <em>Lord, I give as I received. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'All things come from You',
					'verse_ref' => '1 Chronicles 29:14',
					'body'      => hwbl_plan_day_body(
						'We give from God’s own gifts.',
						'“For all things come from you, and of your own have we given you.”',
						'How does stewardship change your grip?',
						'<strong>Practice:</strong> Pray over your wallet or budget as His. <em>Lord, all I have is Yours. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Keep in Step with the Spirit (7 days)',
			'topic'     => 'holy-spirit',
			'shareable' => false,
			'excerpt'   => 'A second Holy Spirit plan—walking, fruit, and keeping in step with the Spirit.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Walk by the Spirit',
					'verse_ref' => 'Galatians 5:16',
					'body'      => hwbl_plan_day_body(
						'Spirit-walking is the path away from fleshly rule.',
						'“But I say, walk by the Spirit, and you will not gratify the desires of the flesh.”',
						'What would walking by the Spirit change today?',
						'<strong>Practice:</strong> Ask the Spirit to lead each hour. <em>Holy Spirit, I walk with You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Keep in step',
					'verse_ref' => 'Galatians 5:25',
					'body'      => hwbl_plan_day_body(
						'Life in the Spirit means matched steps.',
						'“If we live by the Spirit, let us also keep in step with the Spirit.”',
						'Where are you out of step?',
						'<strong>Practice:</strong> Slow down to match His pace. <em>Spirit, keep me in step. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Fruit of the Spirit',
					'verse_ref' => 'Galatians 5:22-23',
					'body'      => hwbl_plan_day_body(
						'The Spirit grows recognizable fruit.',
						'“But the fruit of the Spirit is love, joy, peace, patience, kindness, goodness, faithfulness, gentleness, self-control…”',
						'Which fruit do you need most?',
						'<strong>Practice:</strong> Ask for that fruit and practice it once. <em>Spirit, grow Your fruit in me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Helper forever',
					'verse_ref' => 'John 14:16-17',
					'body'      => hwbl_plan_day_body(
						'The Spirit abides with and in believers.',
						'“And I will ask the Father, and he will give you another Helper, to be with you forever, even the Spirit of truth…”',
						'Have you treated the Helper as optional?',
						'<strong>Practice:</strong> Welcome the Helper’s presence consciously. <em>Holy Spirit, be my Helper today. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Led by the Spirit',
					'verse_ref' => 'Romans 8:14',
					'body'      => hwbl_plan_day_body(
						'Sons and daughters are Spirit-led.',
						'“For all who are led by the Spirit of God are sons of God.”',
						'Where do you need leading more than driving?',
						'<strong>Practice:</strong> Yield one decision to His leading. <em>Spirit of God, lead me. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Do not grieve',
					'verse_ref' => 'Ephesians 4:30',
					'body'      => hwbl_plan_day_body(
						'Sin grieves the Spirit who sealed us.',
						'“And do not grieve the Holy Spirit of God, by whom you were sealed for the day of redemption.”',
						'What habit grieves Him?',
						'<strong>Practice:</strong> Confess and turn from one grieving pattern. <em>Holy Spirit, I do not want to grieve You. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Be filled',
					'verse_ref' => 'Ephesians 5:18',
					'body'      => hwbl_plan_day_body(
						'Fullness is ongoing, not a one-time event.',
						'“And do not get drunk with wine… but be filled with the Spirit.”',
						'Will you ask to be filled again today?',
						'<strong>Practice:</strong> Pray to be filled, then obey promptly. <em>Spirit, fill me afresh. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Delight in the Law (7 days)',
			'topic'     => 'scripture',
			'shareable' => false,
			'excerpt'   => 'A second Scripture plan—delight, meditation, and living by every word from God’s mouth.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Delight in the law',
					'verse_ref' => 'Psalm 1:2',
					'body'      => hwbl_plan_day_body(
						'Blessed people delight and meditate in God’s law.',
						'“But his delight is in the law of the Lord, and on his law he meditates day and night.”',
						'Is Scripture duty or delight for you?',
						'<strong>Practice:</strong> Read slowly until one line delights you. <em>Lord, make Your word my delight. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Man shall not live by bread alone',
					'verse_ref' => 'Matthew 4:4',
					'body'      => hwbl_plan_day_body(
						'Scripture is necessary food.',
						'“Man shall not live by bread alone, but by every word that comes from the mouth of God.”',
						'Have you been skipping spiritual meals?',
						'<strong>Practice:</strong> Feed on one passage before other media. <em>God, feed me by Your word. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Your word is a lamp',
					'verse_ref' => 'Psalm 119:105',
					'body'      => hwbl_plan_day_body(
						'Scripture lights the next step.',
						'“Your word is a lamp to my feet and a light to my path.”',
						'What dark step needs lamp-light?',
						'<strong>Practice:</strong> Ask Scripture to light one decision. <em>Lord, lamp my feet. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'All Scripture is breathed out',
					'verse_ref' => '2 Timothy 3:16-17',
					'body'      => hwbl_plan_day_body(
						'God-breathed Scripture equips for every good work.',
						'“All Scripture is breathed out by God and profitable… that the man of God may be complete, equipped for every good work.”',
						'What good work needs Scripture’s equipping?',
						'<strong>Practice:</strong> Read for equipping, not only information. <em>God, equip me by Your word. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Let the word dwell richly',
					'verse_ref' => 'Colossians 3:16',
					'body'      => hwbl_plan_day_body(
						'Rich dwelling includes teaching and song.',
						'“Let the word of Christ dwell in you richly, teaching and admonishing one another in all wisdom, singing psalms…”',
						'How richly does the word dwell in you?',
						'<strong>Practice:</strong> Memorize one verse and sing it if you can. <em>Christ, dwell richly in me. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Hear and do',
					'verse_ref' => 'James 1:22',
					'body'      => hwbl_plan_day_body(
						'Hearing without doing is self-deception.',
						'“But be doers of the word, and not hearers only, deceiving yourselves.”',
						'What heard word needs doing today?',
						'<strong>Practice:</strong> Do one concrete obedience. <em>Lord, I will do Your word. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'The word of God is living',
					'verse_ref' => 'Hebrews 4:12',
					'body'      => hwbl_plan_day_body(
						'Scripture is living and active, discerning the heart.',
						'“For the word of God is living and active, sharper than any two-edged sword… discerning the thoughts and intentions of the heart.”',
						'What is God’s word discerning in you?',
						'<strong>Practice:</strong> Invite the word to search you. <em>Living Word, search my heart. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Worship in Spirit and Truth (7 days)',
			'topic'     => 'worship',
			'shareable' => false,
			'excerpt'   => 'A second worship plan—spirit and truth, living sacrifice, and whole-life praise.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'In spirit and truth',
					'verse_ref' => 'John 4:23-24',
					'body'      => hwbl_plan_day_body(
						'The Father seeks worshipers in spirit and truth.',
						'“But the hour is coming, and is now here, when the true worshipers will worship the Father in spirit and truth…”',
						'Is your worship spirit-and-truth or habit-only?',
						'<strong>Practice:</strong> Worship privately with honesty and reverence. <em>Father, I worship in spirit and truth. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Living sacrifice',
					'verse_ref' => 'Romans 12:1',
					'body'      => hwbl_plan_day_body(
						'Worship includes bodily, daily offering.',
						'“Present your bodies as a living sacrifice, holy and acceptable to God, which is your spiritual worship.”',
						'How will your body worship today?',
						'<strong>Practice:</strong> Offer your next hours as sacrifice. <em>God, I present my body to You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Ascribe greatness',
					'verse_ref' => 'Deuteronomy 32:3-4',
					'body'      => hwbl_plan_day_body(
						'Worship ascribes greatness to God the Rock.',
						'“For I will proclaim the name of the Lord; ascribe greatness to our God! The Rock, his work is perfect…”',
						'What greatness of God will you proclaim?',
						'<strong>Practice:</strong> Speak one attribute of God aloud. <em>Lord, I ascribe greatness to You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Sing to the Lord',
					'verse_ref' => 'Psalm 96:1-2',
					'body'      => hwbl_plan_day_body(
						'Singing announces salvation day by day.',
						'“Oh sing to the Lord a new song… Sing to the Lord, bless his name; tell of his salvation from day to day.”',
						'Will you sing today—even simply?',
						'<strong>Practice:</strong> Sing one verse of praise. <em>Lord, I sing to You. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Through him then',
					'verse_ref' => 'Hebrews 13:15',
					'body'      => hwbl_plan_day_body(
						'Praise is a continual sacrifice through Jesus.',
						'“Through him then let us continually offer up a sacrifice of praise to God, that is, the fruit of lips that acknowledge his name.”',
						'What praise can your lips offer continually?',
						'<strong>Practice:</strong> Acknowledge His name in conversation once. <em>Jesus, through You I praise. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Whatever you do',
					'verse_ref' => '1 Corinthians 10:31',
					'body'      => hwbl_plan_day_body(
						'Ordinary acts can glorify God.',
						'“So, whether you eat or drink, or whatever you do, do all to the glory of God.”',
						'What ordinary act becomes glory today?',
						'<strong>Practice:</strong> Do one meal or chore for God’s glory. <em>God, this is for Your glory. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Worthy is the Lamb',
					'verse_ref' => 'Revelation 5:12',
					'body'      => hwbl_plan_day_body(
						'Heaven’s worship centers on the Lamb.',
						'“Worthy is the Lamb who was slain, to receive power and wealth and wisdom and might and honor and glory and blessing!”',
						'How does the Lamb shape your worship?',
						'<strong>Practice:</strong> Worship Jesus as worthy aloud. <em>Lamb of God, You are worthy. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Truth in the Inward Being (7 days)',
			'topic'     => 'integrity',
			'shareable' => false,
			'excerpt'   => 'A second integrity plan—truth in the inward being, honest scales, and walking blamelessly.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Truth in the inward being',
					'verse_ref' => 'Psalm 51:6',
					'body'      => hwbl_plan_day_body(
						'God desires truth deep within, not image management.',
						'“Behold, you delight in truth in the inward being, and you teach me wisdom in the secret heart.”',
						'Where is inward truth missing?',
						'<strong>Practice:</strong> Confess one hidden falsehood to God. <em>God, truth in my inward being. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Who shall dwell',
					'verse_ref' => 'Psalm 15:1-2',
					'body'      => hwbl_plan_day_body(
						'Integrity walks blamelessly and speaks truth.',
						'“O Lord, who shall sojourn in your tent?… He who walks blamelessly and does what is right and speaks truth in his heart.”',
						'What speech needs heart-truth alignment?',
						'<strong>Practice:</strong> Speak one costly truth kindly. <em>Lord, help me speak truth in my heart. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Abomination of false scales',
					'verse_ref' => 'Proverbs 11:1',
					'body'      => hwbl_plan_day_body(
						'God hates dishonest measures.',
						'“A false balance is an abomination to the Lord, but a just weight is his delight.”',
						'Where are your “scales” dishonest?',
						'<strong>Practice:</strong> Correct one unfair measure at work or home. <em>Lord, make my scales just. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Let your yes be yes',
					'verse_ref' => 'Matthew 5:37',
					'body'      => hwbl_plan_day_body(
						'Simple honesty needs no spin.',
						'“Let what you say be simply ‘Yes’ or ‘No’; anything more than this comes from evil.”',
						'Where has your yes become soft or false?',
						'<strong>Practice:</strong> Keep one promise fully today. <em>Lord, make my yes yes. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Walk in integrity',
					'verse_ref' => 'Proverbs 10:9',
					'body'      => hwbl_plan_day_body(
						'Integrity walks securely.',
						'“Whoever walks in integrity walks securely, but he who makes his ways crooked will be found out.”',
						'What crooked shortcut tempts you?',
						'<strong>Practice:</strong> Choose the straight path once. <em>Lord, I walk in integrity. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Provide for honest things',
					'verse_ref' => '2 Corinthians 8:21',
					'body'      => hwbl_plan_day_body(
						'Integrity includes how things appear and are.',
						'“For we aim at what is honorable not only in the Lord’s sight but also in the sight of man.”',
						'Where do you need honorable transparency?',
						'<strong>Practice:</strong> Invite accountability on one matter. <em>Lord, make me honorable. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Blameless and innocent',
					'verse_ref' => 'Philippians 2:15',
					'body'      => hwbl_plan_day_body(
						'Integrity shines in a crooked generation.',
						'“…that you may be blameless and innocent, children of God without blemish in the midst of a crooked and twisted generation, among whom you shine as lights in the world.”',
						'Where can blameless light shine?',
						'<strong>Practice:</strong> Refuse one crooked norm. <em>Father, help me shine blamelessly. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Soft Answer, Hard Truth (7 days)',
			'topic'     => 'conflict',
			'shareable' => false,
			'excerpt'   => 'A second conflict plan—soft answers, hard truth in love, and peacemaking under pressure.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'A soft answer',
					'verse_ref' => 'Proverbs 15:1',
					'body'      => hwbl_plan_day_body(
						'Tone can turn away wrath.',
						'“A soft answer turns away wrath, but a harsh word stirs up anger.”',
						'Where do you need a soft answer?',
						'<strong>Practice:</strong> Soften your first sentence in conflict. <em>Lord, give me a soft answer. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Speak truth in love',
					'verse_ref' => 'Ephesians 4:15',
					'body'      => hwbl_plan_day_body(
						'Truth without love wounds; love without truth enables.',
						'“Speaking the truth in love, we are to grow up in every way into him…”',
						'What hard truth needs love’s tone?',
						'<strong>Practice:</strong> Pray, then speak one loving truth. <em>Christ, help me speak truth in love. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Go to your brother',
					'verse_ref' => 'Matthew 18:15',
					'body'      => hwbl_plan_day_body(
						'Jesus starts conflict repair privately.',
						'“If your brother sins against you, go and tell him his fault, between you and him alone.”',
						'What conversation have you avoided or gossiped instead?',
						'<strong>Practice:</strong> Go directly if wise and safe. <em>Lord, help me go in love. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Be quick to hear',
					'verse_ref' => 'James 1:19',
					'body'      => hwbl_plan_day_body(
						'Listening first reduces conflict fuel.',
						'“Let every person be quick to hear, slow to speak, slow to anger.”',
						'Will you listen fully before defending?',
						'<strong>Practice:</strong> Listen without interrupting once. <em>Lord, make me quick to hear. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'As far as it depends on you',
					'verse_ref' => 'Romans 12:18',
					'body'      => hwbl_plan_day_body(
						'Peace requires your part, not their script.',
						'“If possible, so far as it depends on you, live peaceably with all.”',
						'What depends on you right now?',
						'<strong>Practice:</strong> Do your part without controlling theirs. <em>Lord, I will live peaceably as far as I can. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Forgive from the heart',
					'verse_ref' => 'Matthew 18:35',
					'body'      => hwbl_plan_day_body(
						'Unresolved conflict often hides unforgiveness.',
						'“…so also my heavenly Father will do to every one of you, if you do not forgive your brother from your heart.”',
						'What forgiveness still needs heart depth?',
						'<strong>Practice:</strong> Forgive from the heart in prayer. <em>Father, I forgive from my heart. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Blessed are the peacemakers',
					'verse_ref' => 'Matthew 5:9',
					'body'      => hwbl_plan_day_body(
						'Peacemaking is costly and blessed.',
						'“Blessed are the peacemakers, for they shall be called sons of God.”',
						'Where will you make peace this week?',
						'<strong>Practice:</strong> Take one peacemaking initiative. <em>Father, make me a peacemaker. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Welcome the Stranger (7 days)',
			'topic'     => 'hospitality',
			'shareable' => false,
			'excerpt'   => 'A second hospitality plan—welcoming strangers, open doors, and Christlike reception.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Do not neglect hospitality',
					'verse_ref' => 'Hebrews 13:2',
					'body'      => hwbl_plan_day_body(
						'Hospitality may entertain angels unawares.',
						'“Do not neglect to show hospitality to strangers, for thereby some have entertained angels unawares.”',
						'Whom can you welcome that feels strange to you?',
						'<strong>Practice:</strong> Invite or greet someone new. <em>Lord, I will not neglect hospitality. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Welcome one another',
					'verse_ref' => 'Romans 15:7',
					'body'      => hwbl_plan_day_body(
						'Christ’s welcome is our pattern.',
						'“Therefore welcome one another as Christ has welcomed you, for the glory of God.”',
						'How did Christ welcome you—and how can you copy that?',
						'<strong>Practice:</strong> Welcome someone as Christ would. <em>Christ, help me welcome for God’s glory. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'When I was a stranger',
					'verse_ref' => 'Matthew 25:35',
					'body'      => hwbl_plan_day_body(
						'Jesus identifies with the stranger.',
						'“For I was hungry and you gave me food… I was a stranger and you welcomed me.”',
						'Where is Jesus waiting in a stranger?',
						'<strong>Practice:</strong> Welcome someone overlooked. <em>Jesus, I welcome You in them. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Seek to show hospitality',
					'verse_ref' => 'Romans 12:13',
					'body'      => hwbl_plan_day_body(
						'Hospitality is pursued, not only offered when convenient.',
						'“Contribute to the needs of the saints and seek to show hospitality.”',
						'How will you seek hospitality this week?',
						'<strong>Practice:</strong> Plan one hospitable act on the calendar. <em>Lord, I seek to show hospitality. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Open your home',
					'verse_ref' => '1 Peter 4:9',
					'body'      => hwbl_plan_day_body(
						'Hospitality without grumbling is a gift.',
						'“Show hospitality to one another without grumbling.”',
						'What grumbling needs repentance before you host?',
						'<strong>Practice:</strong> Host or help host gladly. <em>Lord, remove grumbling from my hospitality. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Love the sojourner',
					'verse_ref' => 'Deuteronomy 10:19',
					'body'      => hwbl_plan_day_body(
						'God’s people love sojourners because they were sojourners.',
						'“Love the sojourner, therefore, for you were sojourners in the land of Egypt.”',
						'Who is sojourning near you?',
						'<strong>Practice:</strong> Show practical love to a newcomer. <em>God, help me love the sojourner. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'A place for them',
					'verse_ref' => 'Luke 14:12-14',
					'body'      => hwbl_plan_day_body(
						'Kingdom hospitality invites those who cannot repay.',
						'“…invite the poor, the crippled, the lame, the blind, and you will be blessed, because they cannot repay you.”',
						'Whom can you invite who cannot repay?',
						'<strong>Practice:</strong> Plan a meal or kindness for someone who cannot repay. <em>Lord, bless kingdom hospitality. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Blessed Are the Merciful (7 days)',
			'topic'     => 'mercy',
			'shareable' => false,
			'excerpt'   => 'A second mercy plan—merciful hearts, forgiveness, and compassion like the Father.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Blessed are the merciful',
					'verse_ref' => 'Matthew 5:7',
					'body'      => hwbl_plan_day_body(
						'Mercy received becomes mercy given.',
						'“Blessed are the merciful, for they shall receive mercy.”',
						'Where do you need to show mercy you want to receive?',
						'<strong>Practice:</strong> Show mercy in one concrete way. <em>Father, make me merciful. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Be merciful',
					'verse_ref' => 'Luke 6:36',
					'body'      => hwbl_plan_day_body(
						'Mercy mirrors the Father.',
						'“Be merciful, even as your Father is merciful.”',
						'How is the Father merciful to you?',
						'<strong>Practice:</strong> Copy one mercy of the Father toward someone. <em>Father, I will be merciful as You are. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'I desire mercy',
					'verse_ref' => 'Matthew 9:13',
					'body'      => hwbl_plan_day_body(
						'Jesus prefers mercy over sacrifice theater.',
						'“Go and learn what this means: ‘I desire mercy, and not sacrifice.’”',
						'Where have you chosen sacrifice image over mercy?',
						'<strong>Practice:</strong> Choose mercy in one conflict. <em>Jesus, teach me mercy. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'The Good Samaritan',
					'verse_ref' => 'Luke 10:36-37',
					'body'      => hwbl_plan_day_body(
						'Mercy crosses the road.',
						'“Which of these three, do you think, proved to be a neighbor…? …‘You go, and do likewise.’”',
						'Whom have you walked past?',
						'<strong>Practice:</strong> Cross the road for someone in need. <em>Lord, make me a neighbor. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Forgive as God forgave',
					'verse_ref' => 'Ephesians 4:32',
					'body'      => hwbl_plan_day_body(
						'Mercy forgives as God forgave in Christ.',
						'“Be kind to one another, tenderhearted, forgiving one another, as God in Christ forgave you.”',
						'What forgiveness is mercy asking of you?',
						'<strong>Practice:</strong> Forgive as Christ forgave you. <em>God, I forgive as You forgave. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Mercy triumphs',
					'verse_ref' => 'James 2:13',
					'body'      => hwbl_plan_day_body(
						'Mercy triumphs over judgment.',
						'“For judgment is without mercy to one who has shown no mercy. Mercy triumphs over judgment.”',
						'Where is judgment louder than mercy in you?',
						'<strong>Practice:</strong> Let mercy triumph in one judgmental thought. <em>Lord, let mercy triumph. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'His mercy is more',
					'verse_ref' => 'Psalm 103:8-12',
					'body'      => hwbl_plan_day_body(
						'God’s mercy removes transgressions far from us.',
						'“The Lord is merciful and gracious… as far as the east is from the west, so far does he remove our transgressions from us.”',
						'Have you received His mercy deeply enough to give it?',
						'<strong>Practice:</strong> Rest in removed sin, then show mercy. <em>Merciful Lord, thank You—and make me like You. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Shepherd the Flock (7 days)',
			'topic'     => 'leadership',
			'shareable' => false,
			'excerpt'   => 'A second leadership plan—shepherding, serving, and watching over God’s flock willingly.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Shepherd the flock',
					'verse_ref' => '1 Peter 5:2-3',
					'body'      => hwbl_plan_day_body(
						'Leaders shepherd willingly, not domineering.',
						'“Shepherd the flock of God that is among you, exercising oversight, not under compulsion, but willingly… not domineering over those in your charge, but being examples…”',
						'Where might you be domineering instead of exemplifying?',
						'<strong>Practice:</strong> Lead by example in one humble act. <em>Chief Shepherd, make me a willing shepherd. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Not to be served',
					'verse_ref' => 'Mark 10:42-45',
					'body'      => hwbl_plan_day_body(
						'Kingdom leadership serves.',
						'“…whoever would be great among you must be your servant… For even the Son of Man came not to be served but to serve…”',
						'What servant task have you avoided as “beneath” leadership?',
						'<strong>Practice:</strong> Do the servant task yourself. <em>Son of Man, teach me to serve. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Watch over souls',
					'verse_ref' => 'Hebrews 13:17',
					'body'      => hwbl_plan_day_body(
						'Oversight is soul-care with joy, not burden-dumping.',
						'“Obey your leaders and submit to them, for they are keeping watch over your souls, as those who will have to give an account…”',
						'How does accountability reshape your leading?',
						'<strong>Practice:</strong> Pray for those you watch over by name. <em>Lord, help me watch with joy. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Equip the saints',
					'verse_ref' => 'Ephesians 4:11-12',
					'body'      => hwbl_plan_day_body(
						'Leaders equip others for ministry.',
						'“…to equip the saints for the work of ministry, for building up the body of Christ.”',
						'Whom can you equip instead of replacing?',
						'<strong>Practice:</strong> Delegate with coaching, not dumping. <em>Lord, help me equip the saints. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Be an example',
					'verse_ref' => '1 Timothy 4:12',
					'body'      => hwbl_plan_day_body(
						'Example leads louder than title.',
						'“…set the believers an example in speech, in conduct, in love, in faith, in purity.”',
						'Which example area needs attention?',
						'<strong>Practice:</strong> Set an example in that area today. <em>Lord, make my life an example. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Wisdom from above',
					'verse_ref' => 'James 3:17',
					'body'      => hwbl_plan_day_body(
						'Leadership needs heavenly wisdom’s tone.',
						'“But the wisdom from above is first pure, then peaceable, gentle, open to reason, full of mercy…”',
						'Does your leadership sound like this?',
						'<strong>Practice:</strong> Lead one conversation with gentleness. <em>Lord, give wisdom from above. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'When the Chief Shepherd appears',
					'verse_ref' => '1 Peter 5:4',
					'body'      => hwbl_plan_day_body(
						'Leadership aims at the unfading crown from Christ.',
						'“And when the chief Shepherd appears, you will receive the unfading crown of glory.”',
						'How does His appearing reframe your motives?',
						'<strong>Practice:</strong> Lead today for His “well done,” not applause. <em>Chief Shepherd, I lead for You. Amen.</em>'
					),
				),
			),
		),
	);
}
