<?php
/**
 * Phase 12 plan definitions: love, community, peace, Spirit, Word, worship, etc.
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
 * Phase 12 plan payloads for upsert.
 *
 * @return array<int, array<string, mixed>>
 */
function hwbl_plan_seed_phase12_definitions() {
	return array(
		array(
			'title'     => 'Love Your Neighbor (7 days)',
			'topic'     => 'love',
			'shareable' => true,
			'excerpt'   => 'Seven days on the second great commandment—loving neighbors with action, mercy, truth, and the love we first received from Christ.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'The great commandments',
					'verse_ref' => 'Matthew 22:37-39',
					'body'      => hwbl_plan_day_body(
						'Love can feel vague until Jesus orders it: God first, neighbor next—like yourself.',
						'“You shall love the Lord your God with all your heart… You shall love your neighbor as yourself. On these two commandments depend all the Law and the Prophets.” Neighbor-love is not optional spirituality.',
						'Whom have you treated as optional while claiming to love God?',
						'<strong>Practice:</strong> Name one neighbor (literal or relational) and one concrete act of love. <em>Lord, teach me to love You and my neighbor. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Who is my neighbor?',
					'verse_ref' => 'Luke 10:36-37',
					'body'      => hwbl_plan_day_body(
						'We shrink “neighbor” to people who are easy. Jesus widens it with a Samaritan’s mercy.',
						'“Which of these three, do you think, proved to be a neighbor…? You go, and do likewise.” Neighbor is defined by mercy given, not by tribe preferred.',
						'Whom have you crossed the road to avoid?',
						'<strong>Practice:</strong> Cross toward one inconvenient need today. <em>Jesus, make me a neighbor who stops. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Love one another',
					'verse_ref' => 'John 13:34-35',
					'body'      => hwbl_plan_day_body(
						'Jesus gives a new measure: love as He loved—self-giving, foot-washing love.',
						'“A new commandment I give to you, that you love one another: just as I have loved you… By this all people will know that you are my disciples.” Love is the church’s public credential.',
						'Would observers know you follow Jesus by how you love His people?',
						'<strong>Practice:</strong> Encourage or serve one fellow believer specifically. <em>Lord, let Your love be visible in me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Love in deed and truth',
					'verse_ref' => '1 John 3:16-18',
					'body'      => hwbl_plan_day_body(
						'Warm feelings without cost are not the love John describes.',
						'“By this we know love, that he laid down his life for us, and we ought to lay down our lives for the brothers… let us not love in word or talk but in deed and in truth.”',
						'Where has talk outrun deed?',
						'<strong>Practice:</strong> Meet one tangible need—meal, money, time, ride—without announcement. <em>Christ, love through my deeds. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Love your enemies',
					'verse_ref' => 'Matthew 5:44-45',
					'body'      => hwbl_plan_day_body(
						'Enemy-love is where Christian love looks most like the Father.',
						'“Love your enemies and pray for those who persecute you, so that you may be sons of your Father who is in heaven.” Prayer for enemies interrupts revenge’s script.',
						'Whom do you still refuse to pray for?',
						'<strong>Practice:</strong> Pray blessing (not curse) over one hard name. <em>Father, form Your enemy-love in me. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Love bears and believes',
					'verse_ref' => '1 Corinthians 13:4-7',
					'body'      => hwbl_plan_day_body(
						'Paul’s love chapter is a mirror for ordinary relationships, not only weddings.',
						'Love is patient and kind… it bears all things, believes all things, hopes all things, endures all things. This is Christ’s character applied to people who frustrate us.',
						'Which verb of love is missing at home or church?',
						'<strong>Practice:</strong> Practice one patience or kindness act where you usually snap. <em>Lord, put 1 Corinthians 13 into my day. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'We love because He first loved us',
					'verse_ref' => '1 John 4:19',
					'body'      => hwbl_plan_day_body(
						'Neighbor-love runs dry when it starts with us. It begins with being loved.',
						'“We love because he first loved us.” The gospel is the fountain; our love is the overflow.',
						'Have you been trying to manufacture love without receiving it?',
						'<strong>Practice:</strong> Sit with the cross and thank God for loving you first—then love someone from that fullness. <em>Father, I receive Your love and pass it on. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'A People Together (7 days)',
			'topic'     => 'community',
			'shareable' => false,
			'excerpt'   => 'Seven days on belonging to Christ’s body—gathering, bearing burdens, using gifts, and refusing isolation.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Not neglecting to meet',
					'verse_ref' => 'Hebrews 10:24-25',
					'body'      => hwbl_plan_day_body(
						'Isolation feels safe until it becomes a drift. Scripture calls us to gather and stir each other up.',
						'“And let us consider how to stir up one another to love and good works, not neglecting to meet together… but encouraging one another.” Meeting is for mutual courage.',
						'Where has neglect of gathering weakened your love?',
						'<strong>Practice:</strong> Commit to one gathering this week and encourage one person there. <em>Lord, plant me among Your people. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'One body, many members',
					'verse_ref' => '1 Corinthians 12:12-14',
					'body'      => hwbl_plan_day_body(
						'You are not a spare part. The body needs variety under one Head.',
						'“For just as the body is one and has many members… so it is with Christ.” Independence that says “I don’t need them” amputates what God joined.',
						'Do you act like a whole body alone—or a member among members?',
						'<strong>Practice:</strong> Thank God for two people whose gifts differ from yours. <em>Christ, keep me joined to Your body. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Bear one another’s burdens',
					'verse_ref' => 'Galatians 6:2',
					'body'      => hwbl_plan_day_body(
						'Community is not networking; it is burden-bearing that fulfills Christ’s law.',
						'“Bear one another’s burdens, and so fulfill the law of Christ.” Shared weight is discipleship.',
						'Whose burden could you help carry this week—and whose help have you refused?',
						'<strong>Practice:</strong> Ask or offer help in one specific burden. <em>Lord, teach us to carry together. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Devoted to the fellowship',
					'verse_ref' => 'Acts 2:42',
					'body'      => hwbl_plan_day_body(
						'The early church’s shared life had a rhythm: teaching, fellowship, table, prayers.',
						'“And they devoted themselves to the apostles’ teaching and the fellowship, to the breaking of bread and the prayers.” Devotion is steady, not sporadic.',
						'Which of these four is thinnest in your life?',
						'<strong>Practice:</strong> Strengthen the thinnest one with a concrete appointment. <em>Spirit, form devoted fellowship in us. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Speak the truth in love',
					'verse_ref' => 'Ephesians 4:15-16',
					'body'      => hwbl_plan_day_body(
						'Real community needs truth without cruelty and love without flattery.',
						'“Speaking the truth in love, we are to grow up in every way into him who is the head, into Christ… when each part is working properly.” Growth is corporate.',
						'Do you lean toward silent peacekeeping or harsh truth-telling?',
						'<strong>Practice:</strong> Have one honest, gentle conversation aimed at growth. <em>Head of the church, help us speak truth in love. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Welcome one another',
					'verse_ref' => 'Romans 15:7',
					'body'      => hwbl_plan_day_body(
						'Cliques and cold rooms deny the gospel’s welcome.',
						'“Therefore welcome one another as Christ has welcomed you, for the glory of God.” Christ’s welcome is the pattern and power.',
						'Whom do you overlook in gatherings?',
						'<strong>Practice:</strong> Welcome someone new or peripheral with time and attention. <em>Christ, let Your welcome shape mine. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'If one member suffers',
					'verse_ref' => '1 Corinthians 12:26',
					'body'      => hwbl_plan_day_body(
						'The body feels together. Distance from others’ joy and pain is not maturity.',
						'“If one member suffers, all suffer together; if one member is honored, all rejoice together.” Empathy is belonging practiced.',
						'Whose suffering or joy have you treated as none of your business?',
						'<strong>Practice:</strong> Enter one person’s sorrow or celebration with a call, note, or presence. <em>Lord, make us a body that feels together. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Peace That Guards (7 days)',
			'topic'     => 'peace',
			'shareable' => false,
			'excerpt'   => 'Seven days on the peace of Christ—reconciled to God, peacemaking with others, and guarded hearts in a restless world.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Peace with God',
					'verse_ref' => 'Romans 5:1',
					'body'      => hwbl_plan_day_body(
						'Before peace of mind comes peace with God—justified by faith.',
						'“Therefore, since we have been justified by faith, we have peace with God through our Lord Jesus Christ.” The war of condemnation ends at the cross.',
						'Are you still negotiating peace God has already given in Christ?',
						'<strong>Practice:</strong> Thank God aloud that peace with Him is gift, not wage. <em>Jesus, You are my peace with God. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'My peace I give to you',
					'verse_ref' => 'John 14:27',
					'body'      => hwbl_plan_day_body(
						'The world’s peace depends on calm circumstances. Jesus gives a different peace.',
						'“Peace I leave with you; my peace I give to you. Not as the world gives do I give to you. Let not your hearts be troubled, neither let them be afraid.”',
						'Where are you chasing world-peace instead of receiving His?',
						'<strong>Practice:</strong> When trouble rises, pray John 14:27 slowly. <em>Jesus, give me Your peace, not the world’s. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'The peace of God will guard',
					'verse_ref' => 'Philippians 4:6-7',
					'body'      => hwbl_plan_day_body(
						'Anxiety thrives on unprayed cares. Paul ties petition to guarded peace.',
						'“Do not be anxious about anything, but in everything by prayer and supplication with thanksgiving let your requests be made known to God. And the peace of God… will guard your hearts and your minds in Christ Jesus.”',
						'What unprayed anxiety is standing guard over you instead?',
						'<strong>Practice:</strong> Write three cares as prayers with one thanksgiving each. <em>God of peace, guard my heart and mind. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Blessed are the peacemakers',
					'verse_ref' => 'Matthew 5:9',
					'body'      => hwbl_plan_day_body(
						'Peacemaking is active, not passive conflict-avoidance.',
						'“Blessed are the peacemakers, for they shall be called sons of God.” Children resemble the Father who reconciles.',
						'Where can you make peace without pretending sin never happened?',
						'<strong>Practice:</strong> Take one reconciling step—apology, listening, or mediated talk. <em>Father, make me a peacemaker. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'As far as it depends on you',
					'verse_ref' => 'Romans 12:18',
					'body'      => hwbl_plan_day_body(
						'Not every relationship yields peace. Paul limits the burden wisely.',
						'“If possible, so far as it depends on you, live peaceably with all.” Your part is faithfulness; outcomes are not all yours.',
						'Are you owning more than depends on you—or less?',
						'<strong>Practice:</strong> Do your peaceable part; release what you cannot control. <em>Lord, help me live peaceably as far as it depends on me. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Let the peace of Christ rule',
					'verse_ref' => 'Colossians 3:15',
					'body'      => hwbl_plan_day_body(
						'Peace is meant to umpire decisions in the body of Christ.',
						'“And let the peace of Christ rule in your hearts, to which indeed you were called in one body. And be thankful.” Ruling peace replaces impulsive reaction.',
						'What decision needs Christ’s peace as umpire today?',
						'<strong>Practice:</strong> Pause before reacting and ask which path peace rules. <em>Christ, rule in my heart with Your peace. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'He Himself is our peace',
					'verse_ref' => 'Ephesians 2:14',
					'body'      => hwbl_plan_day_body(
						'Christ does not only teach peace; He is peace—breaking dividing walls.',
						'“For he himself is our peace, who has made us both one and has broken down in his flesh the dividing wall of hostility.”',
						'What dividing wall still stands in your relationships or church?',
						'<strong>Practice:</strong> Cross one wall with gospel welcome this week. <em>Jesus, You are our peace—break down hostility. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Content in Christ (7 days)',
			'topic'     => 'contentment',
			'shareable' => false,
			'excerpt'   => 'Seven days learning contentment—freedom from comparison, greed, and the lie that the next thing will finally be enough.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Godliness with contentment',
					'verse_ref' => '1 Timothy 6:6-8',
					'body'      => hwbl_plan_day_body(
						'The world sells discontent as ambition. Paul calls contentment great gain.',
						'“But godliness with contentment is great gain, for we brought nothing into the world, and we cannot take anything out of the world. But if we have food and clothing, with these we will be content.”',
						'What “enough” line keeps moving for you?',
						'<strong>Practice:</strong> Thank God for food, covering, and one non-negotiable mercy. <em>Lord, teach me godliness with contentment. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'I have learned',
					'verse_ref' => 'Philippians 4:11-13',
					'body'      => hwbl_plan_day_body(
						'Contentment is learned in both lack and plenty—through Christ’s strength.',
						'“I have learned in whatever situation I am to be content… I can do all things through him who strengthens me.” Strength is for faithfulness, not for endless craving.',
						'In plenty or lack, where is discontent loudest?',
						'<strong>Practice:</strong> Refuse one upgrade impulse and thank Christ for strength today. <em>Christ, strengthen me to be content. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Keep your life free from love of money',
					'verse_ref' => 'Hebrews 13:5',
					'body'      => hwbl_plan_day_body(
						'Money-love and discontent travel together. Presence is the cure.',
						'“Keep your life free from love of money, and be content with what you have, for he has said, ‘I will never leave you nor forsake you.’”',
						'Does money-anxiety reveal fear that God might forsake you?',
						'<strong>Practice:</strong> Give or share something today as trust practiced. <em>Lord, You will not forsake me—I will be content. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Do not covet',
					'verse_ref' => 'Exodus 20:17',
					'body'      => hwbl_plan_day_body(
						'Coveting turns neighbors into mirrors of what we lack.',
						'“You shall not covet your neighbor’s house… or anything that is your neighbor’s.” Coveting is desire that disputes God’s providence.',
						'Whose life are you coveting in quiet comparison?',
						'<strong>Practice:</strong> Bless that person by name and delete one envy trigger (mute, unfollow, scroll less). <em>Lord, free me from coveting. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'The Lord is my shepherd',
					'verse_ref' => 'Psalm 23:1',
					'body'      => hwbl_plan_day_body(
						'Contentment rests on who shepherds you.',
						'“The Lord is my shepherd; I shall not want.” Not want—because His care is sufficient for the path He leads.',
						'Where are you acting like an uns shepherdless sheep?',
						'<strong>Practice:</strong> Pray Psalm 23 slowly as your day’s true inventory. <em>Shepherd, I shall not want. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Take care, and be on your guard',
					'verse_ref' => 'Luke 12:15',
					'body'      => hwbl_plan_day_body(
						'Jesus warns that life is not abundance of possessions.',
						'“Take care, and be on your guard against all covetousness, for one’s life does not consist in the abundance of his possessions.” Guardedness is wisdom, not gloom.',
						'What possession or status are you treating as “life”?',
						'<strong>Practice:</strong> Fast from shopping or browsing wishlists for 24 hours. <em>Jesus, my life is in You, not in abundance. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Better is a little with righteousness',
					'verse_ref' => 'Proverbs 16:8',
					'body'      => hwbl_plan_day_body(
						'Wisdom prefers clean little over corrupt much.',
						'“Better is a little with righteousness than great revenues with injustice.” Contentment chooses integrity over grasping.',
						'Where might injustice-for-gain be tempting you?',
						'<strong>Practice:</strong> Choose the clean “little” path in one decision today. <em>Lord, better a little with righteousness. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Open Hands (7 days)',
			'topic'     => 'generosity',
			'shareable' => false,
			'excerpt'   => 'Seven days on cheerful giving—reflecting the Giver, freeing the grip of greed, and practicing open-handed love.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'God loves a cheerful giver',
					'verse_ref' => '2 Corinthians 9:6-7',
					'body'      => hwbl_plan_day_body(
						'Giving can become guilt or performance. Paul aims at cheerful trust.',
						'“Whoever sows sparingly will also reap sparingly… Each one must give as he has decided in his heart, not reluctantly or under compulsion, for God loves a cheerful giver.”',
						'Is your giving reluctant, compulsive, or cheerful?',
						'<strong>Practice:</strong> Give one gift today with glad intention, not pressure. <em>Lord, form a cheerful giver’s heart. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'It is more blessed to give',
					'verse_ref' => 'Acts 20:35',
					'body'      => hwbl_plan_day_body(
						'Jesus flips the blessing calculus: giving is the happier path.',
						'“It is more blessed to give than to receive.” Receiving is grace; giving is grace shared.',
						'Where do you chase the blessing of receiving more than giving?',
						'<strong>Practice:</strong> Give time or money where you usually only receive. <em>Jesus, teach me the blessedness of giving. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Freely you received',
					'verse_ref' => 'Matthew 10:8',
					'body'      => hwbl_plan_day_body(
						'Generosity starts with memory: we are receivers first.',
						'“You received without paying; give without pay.” Gospel economics overflows.',
						'What free gift from God have you treated as earned private property?',
						'<strong>Practice:</strong> Share a skill, meal, or resource without keeping score. <em>Lord, I received freely—help me give freely. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Store up treasure in heaven',
					'verse_ref' => 'Matthew 6:19-21',
					'body'      => hwbl_plan_day_body(
						'Where treasure goes, the heart follows. Jesus relocates investment.',
						'“Do not lay up for yourselves treasures on earth… but lay up for yourselves treasures in heaven… For where your treasure is, there your heart will be also.”',
						'What does your spending say about your heart’s address?',
						'<strong>Practice:</strong> Move one portion toward kingdom good (church, mercy, mission). <em>Lord, relocate my treasure and my heart. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'The widow’s offering',
					'verse_ref' => 'Mark 12:43-44',
					'body'      => hwbl_plan_day_body(
						'Jesus measures generosity by sacrifice, not by size of the gift.',
						'“Truly, I say to you, this poor widow has put in more than all those… For they all contributed out of their abundance, but she out of her poverty has put in everything she had.”',
						'Are you waiting to be “abundant” before you are generous?',
						'<strong>Practice:</strong> Give a stretch gift that costs something real. <em>Lord, receive what costs me—like the widow. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Do not neglect to do good',
					'verse_ref' => 'Hebrews 13:16',
					'body'      => hwbl_plan_day_body(
						'Generosity includes shared life, not only transfers.',
						'“Do not neglect to do good and to share what you have, for such sacrifices are pleasing to God.” Sharing is worship.',
						'What good sharing have you been neglecting?',
						'<strong>Practice:</strong> Share a meal, tool, or evening with someone. <em>God, may shared life please You. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Thanks be to God for His gift',
					'verse_ref' => '2 Corinthians 9:15',
					'body'      => hwbl_plan_day_body(
						'All Christian generosity points back to the inexpressible Gift.',
						'“Thanks be to God for his inexpressible gift!” Christ Himself is the fountain of open hands.',
						'Has giving become duty detached from thanksgiving for Jesus?',
						'<strong>Practice:</strong> Thank God for Christ, then give as overflow. <em>Father, thanks for Your inexpressible gift—make me generous. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Walking by the Spirit (7 days)',
			'topic'     => 'holy-spirit',
			'shareable' => false,
			'excerpt'   => 'Seven days on life in the Spirit—new birth, freedom from the flesh, fruit that grows, and daily dependence on God’s presence.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Born of the Spirit',
					'verse_ref' => 'John 3:5-6',
					'body'      => hwbl_plan_day_body(
						'Christian life is not self-improvement. It begins with new birth.',
						'“Unless one is born of water and the Spirit, he cannot enter the kingdom of God… That which is born of the Spirit is spirit.” The Spirit gives life we cannot manufacture.',
						'Are you relying on effort where you need new birth?',
						'<strong>Practice:</strong> Thank the Spirit for life in Christ; ask for fresh dependence. <em>Holy Spirit, I need the life only You give. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Walk by the Spirit',
					'verse_ref' => 'Galatians 5:16',
					'body'      => hwbl_plan_day_body(
						'Walking is continuous, not a one-time surge. The Spirit opposes the flesh’s pull.',
						'“But I say, walk by the Spirit, and you will not gratify the desires of the flesh.” Spirit-walking is the path of freedom.',
						'Where is the flesh currently winning the walk?',
						'<strong>Practice:</strong> Before a known weak moment, pray: “Spirit, lead my next step.” <em>Holy Spirit, I walk with You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'The fruit of the Spirit',
					'verse_ref' => 'Galatians 5:22-23',
					'body'      => hwbl_plan_day_body(
						'Fruit grows; it is not a costume. The Spirit produces Christlike character.',
						'“But the fruit of the Spirit is love, joy, peace, patience, kindness, goodness, faithfulness, gentleness, self-control…” One fruit with many flavors.',
						'Which flavor is least ripe in you right now?',
						'<strong>Practice:</strong> Ask the Spirit to grow that one trait in a specific relationship today. <em>Spirit, bear Your fruit in me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'The Spirit helps us in our weakness',
					'verse_ref' => 'Romans 8:26',
					'body'      => hwbl_plan_day_body(
						'When words fail, the Spirit does not.',
						'“Likewise the Spirit helps us in our weakness. For we do not know what to pray for as we ought, but the Spirit himself intercedes for us with groanings too deep for words.”',
						'Where have wordless weakness kept you from praying?',
						'<strong>Practice:</strong> Sit in honest silence and trust the Spirit’s help. <em>Spirit, help my weakness and intercede. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Led by the Spirit of God',
					'verse_ref' => 'Romans 8:14',
					'body'      => hwbl_plan_day_body(
						'Sons and daughters are led—away from fear-slavery into family freedom.',
						'“For all who are led by the Spirit of God are sons of God.” Leading is relational, not merely mystical shortcuts.',
						'Are you asking the Spirit to rubber-stamp your plans—or to lead?',
						'<strong>Practice:</strong> Yield one plan and ask, “Spirit, lead.” <em>Spirit of adoption, lead me as a child of God. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Do not grieve the Holy Spirit',
					'verse_ref' => 'Ephesians 4:30-32',
					'body'      => hwbl_plan_day_body(
						'The Spirit can be grieved by bitterness and corrosive speech among God’s people.',
						'“And do not grieve the Holy Spirit of God, by whom you were sealed for the day of redemption. Let all bitterness and wrath… be put away from you… Be kind to one another…”',
						'What bitterness might be grieving the Spirit in you?',
						'<strong>Practice:</strong> Put away one bitter word or rehearsed grievance; practice kindness. <em>Holy Spirit, I do not want to grieve You. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Be filled with the Spirit',
					'verse_ref' => 'Ephesians 5:18-20',
					'body'      => hwbl_plan_day_body(
						'Filling is ongoing influence—replacing numbing escapes with worshipful life together.',
						'“And do not get drunk with wine… but be filled with the Spirit, addressing one another in psalms and hymns and spiritual songs… giving thanks always…”',
						'What are you using to fill what only the Spirit should fill?',
						'<strong>Practice:</strong> Replace one numbing habit today with thanksgiving and a song to God. <em>Spirit, fill me afresh. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Hungry for the Word (7 days)',
			'topic'     => 'scripture',
			'shareable' => false,
			'excerpt'   => 'Seven days on Scripture—delight, meditation, obedience, and letting the Word dwell richly when attention is scarce.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Man shall not live by bread alone',
					'verse_ref' => 'Matthew 4:4',
					'body'      => hwbl_plan_day_body(
						'Jesus meets temptation with Scripture and names the Word as life-food.',
						'“It is written, ‘Man shall not live by bread alone, but by every word that comes from the mouth of God.’” Neglecting Scripture is a kind of starvation.',
						'What have you been feeding on more than God’s Word?',
						'<strong>Practice:</strong> Read one chapter slowly before other media today. <em>Lord, feed me by every word from Your mouth. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'His delight is in the law',
					'verse_ref' => 'Psalm 1:1-2',
					'body'      => hwbl_plan_day_body(
						'Blessed living is shaped by delight and meditation, not occasional glances.',
						'“Blessed is the man… his delight is in the law of the Lord, and on his law he meditates day and night.” Meditation is turning the Word over until it turns you.',
						'Is Scripture duty, scarcity, or delight for you?',
						'<strong>Practice:</strong> Choose one verse to carry and repeat through the day. <em>Lord, make Your Word my delight. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'All Scripture is breathed out',
					'verse_ref' => '2 Timothy 3:16-17',
					'body'      => hwbl_plan_day_body(
						'Scripture is God-breathed and useful—for teaching, correcting, and equipping.',
						'“All Scripture is breathed out by God and profitable… that the man of God may be complete, equipped for every good work.” The Bible is not optional enrichment; it equips.',
						'Where do you need equipping you have not sought in Scripture?',
						'<strong>Practice:</strong> Read a passage asking: teach, correct, train—what is God saying? <em>God, breathe Your Word into my life. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Your word is a lamp',
					'verse_ref' => 'Psalm 119:105',
					'body'      => hwbl_plan_day_body(
						'Guidance is often a lamp for the next step, not a floodlight for the whole road.',
						'“Your word is a lamp to my feet and a light to my path.” Scripture clarifies obedience in the dark.',
						'What next step needs the lamp more than a full map?',
						'<strong>Practice:</strong> Obey the next clear step the Word already gives. <em>Lord, lamp my feet for today’s path. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Let the word of Christ dwell',
					'verse_ref' => 'Colossians 3:16',
					'body'      => hwbl_plan_day_body(
						'Dwelling means the Word takes up residence—shaping speech and worship together.',
						'“Let the word of Christ dwell in you richly, teaching and admonishing one another in all wisdom, singing psalms and hymns and spiritual songs…”',
						'Does the Word visit you—or dwell?',
						'<strong>Practice:</strong> Share one verse with someone and sing or speak thanks. <em>Christ, let Your word dwell richly in me. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Be doers of the word',
					'verse_ref' => 'James 1:22',
					'body'      => hwbl_plan_day_body(
						'Hearing without doing is self-deception.',
						'“But be doers of the word, and not hearers only, deceiving yourselves.” Application is love for the Speaker.',
						'What Word have you heard repeatedly without doing?',
						'<strong>Practice:</strong> Do the neglected obedience today. <em>Lord, make me a doer of Your word. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'The word of God is living',
					'verse_ref' => 'Hebrews 4:12',
					'body'      => hwbl_plan_day_body(
						'Scripture is not a museum text. It is living and active—searching us.',
						'“For the word of God is living and active, sharper than any two-edged sword… discerning the thoughts and intentions of the heart.”',
						'Are you letting the Word search you—or only searching it for tips?',
						'<strong>Practice:</strong> Ask God to expose one intention as you read; confess what He shows. <em>Living Word, search and shape my heart. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Worship With Your Life (7 days)',
			'topic'     => 'worship',
			'shareable' => false,
			'excerpt'   => 'Seven days on worship beyond the song—living sacrifice, gathered praise, and adoring God in Spirit and truth.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Present your bodies',
					'verse_ref' => 'Romans 12:1',
					'body'      => hwbl_plan_day_body(
						'Worship starts with a living sacrifice—ordinary bodies offered to God.',
						'“I appeal to you therefore, brothers, by the mercies of God, to present your bodies as a living sacrifice, holy and acceptable to God, which is your spiritual worship.”',
						'What part of your bodily life have you withheld from worship?',
						'<strong>Practice:</strong> Offer today’s schedule to God as sacrifice before you start. <em>Merciful God, I present my body to You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'In spirit and truth',
					'verse_ref' => 'John 4:23-24',
					'body'      => hwbl_plan_day_body(
						'Place and preference matter less than Spirit and truth.',
						'“True worshipers will worship the Father in spirit and truth, for the Father is seeking such people to worship him. God is spirit, and those who worship him must worship in spirit and truth.”',
						'Are you more attached to style than to the Father who seeks worshipers?',
						'<strong>Practice:</strong> Worship without your preferred soundtrack—Scripture and simple thanks. <em>Father, I worship in spirit and truth. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Ascribe to the Lord',
					'verse_ref' => 'Psalm 29:1-2',
					'body'      => hwbl_plan_day_body(
						'Worship ascribes—giving God the glory already His.',
						'“Ascribe to the Lord, O heavenly beings, ascribe to the Lord glory and strength. Ascribe to the Lord the glory due his name; worship the Lord in the splendor of holiness.”',
						'What glory have you been ascribing to yourself or lesser things?',
						'<strong>Practice:</strong> Speak aloud three glories due His name. <em>Lord, Yours is the glory due Your name. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Enter his gates with thanksgiving',
					'verse_ref' => 'Psalm 100:4',
					'body'      => hwbl_plan_day_body(
						'Thanksgiving is the doorway into God’s presence.',
						'“Enter his gates with thanksgiving, and his courts with praise! Give thanks to him; bless his name!” Grumbling closes what gratitude opens.',
						'What complaint is blocking your entry into praise?',
						'<strong>Practice:</strong> Replace one complaint loop with specific thanks. <em>Lord, I enter with thanksgiving. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Sing to the Lord a new song',
					'verse_ref' => 'Psalm 96:1-3',
					'body'      => hwbl_plan_day_body(
						'Song declares God’s glory among the nations—not only among the already convinced.',
						'“Oh sing to the Lord a new song… Declare his glory among the nations, his marvelous works among all the peoples!”',
						'Has your singing become private preference more than public witness?',
						'<strong>Practice:</strong> Sing one song of praise; tell someone one marvelous work of God. <em>Lord, I sing and declare Your glory. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Whatever you do',
					'verse_ref' => 'Colossians 3:17',
					'body'      => hwbl_plan_day_body(
						'Worship expands into work, words, and ordinary tasks.',
						'“And whatever you do, in word or deed, do everything in the name of the Lord Jesus, giving thanks to God the Father through him.”',
						'Which task will you reclaim as done in Jesus’ name today?',
						'<strong>Practice:</strong> Begin one chore or work block with “in Jesus’ name” and thanksgiving. <em>Lord Jesus, this task is Yours. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Worthy is the Lamb',
					'verse_ref' => 'Revelation 5:12',
					'body'      => hwbl_plan_day_body(
						'Heaven’s worship centers on the slain-and-risen Lamb.',
						'“Worthy is the Lamb who was slain, to receive power and wealth and wisdom and might and honor and glory and blessing!”',
						'Is the Lamb at the center of your worship—or your feelings about worship?',
						'<strong>Practice:</strong> Read Revelation 5 and join heaven’s worthiness song in prayer. <em>Worthy Lamb, receive my blessing and praise. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Walk in Integrity (7 days)',
			'topic'     => 'integrity',
			'shareable' => false,
			'excerpt'   => 'Seven days on integrity—truthful speech, faithful work, secret holiness, and a life that matches confession.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Who shall dwell on Your holy hill?',
					'verse_ref' => 'Psalm 15:1-2',
					'body'      => hwbl_plan_day_body(
						'Integrity is not perfectionism; it is blunt honesty before God.',
						'“O Lord, who shall sojourn in your tent?… He who walks blamelessly and does what is right and speaks truth in his heart.” Truth starts inside.',
						'Where is your private heart out of step with your public words?',
						'<strong>Practice:</strong> Confess one inner untruth to God without excuse. <em>Lord, form truth in my heart. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Let your yes be yes',
					'verse_ref' => 'Matthew 5:37',
					'body'      => hwbl_plan_day_body(
						'Jesus simplifies speech: reliable yes and no without manipulative oaths.',
						'“Let what you say be simply ‘Yes’ or ‘No’; anything more than this comes from evil.” Integrity keeps promises small enough to keep.',
						'What half-promise needs a clear yes or a clear no?',
						'<strong>Practice:</strong> Clarify one fuzzy commitment today. <em>Jesus, make my yes yes and my no no. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Whoever walks in integrity walks securely',
					'verse_ref' => 'Proverbs 10:9',
					'body'      => hwbl_plan_day_body(
						'Hidden double lives feel clever until they collapse. Integrity walks securely.',
						'“Whoever walks in integrity walks securely, but he who makes his ways crooked will be found out.” Security is moral, not only circumstantial.',
						'What crooked shortcut still feels necessary?',
						'<strong>Practice:</strong> Straighten one crooked practice (time, money, speech). <em>Lord, I choose the secure path of integrity. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Work heartily as for the Lord',
					'verse_ref' => 'Colossians 3:23-24',
					'body'      => hwbl_plan_day_body(
						'Integrity at work means the Lord is the true audience.',
						'“Whatever you do, work heartily, as for the Lord and not for men… You are serving the Lord Christ.” Half-hearted work for human eyes fails this test.',
						'Where does your work quality drop when no one is watching?',
						'<strong>Practice:</strong> Do one unseen task excellently for Christ. <em>Lord Christ, I work for You. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Nothing is covered that will not be revealed',
					'verse_ref' => 'Luke 12:2-3',
					'body'      => hwbl_plan_day_body(
						'Secret sin banks on darkness. Jesus says the lights are coming on.',
						'“Nothing is covered up that will not be revealed, or hidden that will not be known.” Integrity lives now as you will wish you had when all is open.',
						'What cover needs removing while mercy is near?',
						'<strong>Practice:</strong> Bring one hidden thing into light with God and a trusted believer. <em>Lord, I choose light over cover. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Keep your way pure',
					'verse_ref' => 'Psalm 119:9',
					'body'      => hwbl_plan_day_body(
						'Purity of path is guarded by the Word, not by vibes.',
						'“How can a young man keep his way pure? By guarding it according to your word.” Guarding is active.',
						'What unguarded input is staining your way?',
						'<strong>Practice:</strong> Set one Word-shaped guard on media, speech, or relationship. <em>Lord, guard my way by Your word. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Abhor what is evil',
					'verse_ref' => 'Romans 12:9',
					'body'      => hwbl_plan_day_body(
						'Genuine love is not soft on evil. Integrity hates what God hates and clings to good.',
						'“Let love be genuine. Abhor what is evil; hold fast to what is good.” Soft compromise is not kindness.',
						'Where have you been polite toward evil you should abhor?',
						'<strong>Practice:</strong> Name evil honestly and cling to one good act instead. <em>Lord, make my love genuine—hate evil, hold good. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'When Conflict Comes (7 days)',
			'topic'     => 'conflict',
			'shareable' => false,
			'excerpt'   => 'Seven days for conflict with wisdom—slow speech, gentle restoration, pursuing peace, and refusing revenge.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Quick to hear, slow to speak',
					'verse_ref' => 'James 1:19-20',
					'body'      => hwbl_plan_day_body(
						'Conflict accelerates tongues. James slows us down.',
						'“Let every person be quick to hear, slow to speak, slow to anger; for the anger of man does not produce the righteousness of God.” Speed usually serves pride.',
						'In your last conflict, were you quick to hear—or quick to win?',
						'<strong>Practice:</strong> In the next disagreement, paraphrase before you reply. <em>Lord, make me quick to hear and slow to speak. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'If your brother sins against you',
					'verse_ref' => 'Matthew 18:15',
					'body'      => hwbl_plan_day_body(
						'Jesus prioritizes private, direct address over gossip and pile-ons.',
						'“If your brother sins against you, go and tell him his fault, between you and him alone. If he listens to you, you have gained your brother.” The goal is gain, not victory.',
						'Have you talked about someone more than to them?',
						'<strong>Practice:</strong> Go directly (or schedule to) with humility. <em>Jesus, help me gain my brother, not win an argument. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Restore in a spirit of gentleness',
					'verse_ref' => 'Galatians 6:1',
					'body'      => hwbl_plan_day_body(
						'Correction without gentleness becomes another sin.',
						'“Brothers, if anyone is caught in any transgression, you who are spiritual should restore him in a spirit of gentleness. Keep watch on yourself, lest you too be tempted.”',
						'Does your correction aim to restore—or to punish?',
						'<strong>Practice:</strong> Soften one hard confrontation with gentleness and self-watch. <em>Spirit, restore through gentleness. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'A soft answer',
					'verse_ref' => 'Proverbs 15:1',
					'body'      => hwbl_plan_day_body(
						'Tone can heal or ignite. Wisdom chooses softness with truth.',
						'“A soft answer turns away wrath, but a harsh word stirs up anger.” Soft is not dishonest—it is strength under control.',
						'Where does harshness feel righteous but prove foolish?',
						'<strong>Practice:</strong> Answer one heated moment with a soft, clear sentence. <em>Lord, put a soft answer on my tongue. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Do not repay evil for evil',
					'verse_ref' => 'Romans 12:17-19',
					'body'      => hwbl_plan_day_body(
						'Revenge feels like justice; God claims that seat.',
						'“Repay no one evil for evil… Beloved, never avenge yourselves, but leave it to the wrath of God, for it is written, ‘Vengeance is mine, I will repay, says the Lord.’”',
						'What revenge fantasy needs surrendering?',
						'<strong>Practice:</strong> Refuse one retaliatory act or post; pray blessing instead. <em>Lord, vengeance is Yours—I will not repay evil. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Pursue what makes for peace',
					'verse_ref' => 'Romans 14:19',
					'body'      => hwbl_plan_day_body(
						'Peace and mutual upbuilding are pursuits, not accidents.',
						'“So then let us pursue what makes for peace and for mutual upbuilding.” Pursuit means initiative.',
						'What peacemaking step have you delayed?',
						'<strong>Practice:</strong> Initiate one upbuilding act toward someone in tension. <em>Lord, I pursue peace and upbuilding. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Forgive as the Lord forgave you',
					'verse_ref' => 'Colossians 3:13',
					'body'      => hwbl_plan_day_body(
						'Conflict’s endgame in Christ is forgiveness that mirrors the gospel.',
						'“Bearing with one another and, if one has a complaint against another, forgiving each other; as the Lord has forgiven you, so you also must forgive.”',
						'What complaint are you still nursing past the Lord’s forgiveness of you?',
						'<strong>Practice:</strong> Release one complaint to God and, as far as possible, to the person. <em>Lord, as You forgave me, I forgive. Amen.</em>'
					),
				),
			),
		),
	);
}
