<?php
/**
 * Phase 11 plan definitions: chronological overview + formation themes.
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
 * Phase 11 plan payloads for upsert.
 *
 * @return array<int, array<string, mixed>>
 */
function hwbl_plan_seed_phase11_definitions() {
	return array(
		array(
			'title'     => 'The Big Story (7 days)',
			'topic'     => 'chronological',
			'shareable' => true,
			'excerpt'   => 'Seven days through the Bible’s storyline—creation, fall, promise, Christ, church, and hope—so you can see where you stand in God’s plan.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'God makes and blesses',
					'verse_ref' => 'Genesis 1:27-28',
					'body'      => hwbl_plan_day_body(
						'Many people open the Bible as scattered verses. The story begins with a good Creator and image-bearing people.',
						'“So God created man in his own image… And God blessed them.” Dignity, vocation, and blessing come from God before any human achievement.',
						'Do you receive your worth as gift—or scramble to manufacture it?',
						'<strong>Practice:</strong> Thank God for one good gift of creation today. <em>Creator God, I belong to You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'The fracture',
					'verse_ref' => 'Genesis 3:6-7',
					'body'      => hwbl_plan_day_body(
						'The world is beautiful and broken. Genesis names the break: distrust and grasping for godhood.',
						'Eve and Adam take what God withheld for their good. Shame enters. Exile begins. Sin is not only “bad behavior”—it is rebellion against the Giver.',
						'Where are you reaching past God’s word for a shortcut?',
						'<strong>Practice:</strong> Confess one distrustful grab to God by name. <em>Lord, forgive my grasping and restore trust. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Promise that holds',
					'verse_ref' => 'Genesis 12:2-3',
					'body'      => hwbl_plan_day_body(
						'God does not abandon the story. He calls Abraham and promises blessing that will reach the nations.',
						'“I will bless you… and in you all the families of the earth shall be blessed.” Redemption moves through covenant promise toward Christ.',
						'How does God’s global promise reshape a small, anxious life?',
						'<strong>Practice:</strong> Pray for one “family of the earth” you know by name. <em>God of Abraham, keep Your promise through Christ. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Rescue and law',
					'verse_ref' => 'Exodus 20:2-3',
					'body'      => hwbl_plan_day_body(
						'Israel’s story is rescue first, then instruction. Grace precedes the commandments.',
						'“I am the Lord your God, who brought you out of the land of Egypt… You shall have no other gods before me.” Freedom from slavery is meant for loyal worship.',
						'What “Egypt” still claims a competing lordship in your week?',
						'<strong>Practice:</strong> Name one false god and refuse it in a concrete act today. <em>Lord who frees, be my only God. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'The King arrives',
					'verse_ref' => 'Luke 4:18-19',
					'body'      => hwbl_plan_day_body(
						'Prophets longed; Gospels announce: Jesus is the promised King bringing good news to the poor and liberty to captives.',
						'Jesus reads Isaiah and says the Scripture is fulfilled. The Big Story centers on Him—not a moral upgrade, but God’s presence among us.',
						'Is Jesus the center of your story, or an add-on chapter?',
						'<strong>Practice:</strong> Read Luke 4:16–21 slowly and thank Him for one freedom He brings. <em>Jesus, be the center of my story. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Cross and empty tomb',
					'verse_ref' => '1 Corinthians 15:3-4',
					'body'      => hwbl_plan_day_body(
						'The climax is not advice but news: Christ died for our sins and rose on the third day.',
						'Paul calls this “of first importance.” Without the cross and resurrection, the Bible’s story collapses into inspiration without salvation.',
						'What would change if this news were truly first importance in your week?',
						'<strong>Practice:</strong> Tell someone one sentence of this gospel today. <em>Risen Lord, keep the cross and empty tomb first. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Church and hope',
					'verse_ref' => 'Revelation 21:3-4',
					'body'      => hwbl_plan_day_body(
						'The story ends with God dwelling with His people—tears wiped, death undone. Between now and then, the church bears witness.',
						'“Behold, the dwelling place of God is with man… He will wipe away every tear.” Hope is not escape from history but God’s renewal of it.',
						'How does this ending steady you in today’s unfinished chapter?',
						'<strong>Practice:</strong> Encourage one believer with this hope in a short message. <em>Coming King, dwell with us and wipe every tear. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Waiting Without Wasting (7 days)',
			'topic'     => 'waiting',
			'shareable' => false,
			'excerpt'   => 'Seven days for seasons of delay—learning to wait on the Lord without bitterness, hurry, or wasted hope.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Wait for the Lord',
					'verse_ref' => 'Psalm 27:13-14',
					'body'      => hwbl_plan_day_body(
						'Waiting feels like inactivity, but Scripture treats it as trust under pressure.',
						'“Wait for the Lord; be strong, and let your heart take courage; wait for the Lord!” Courage and waiting belong together—not resignation.',
						'Where has waiting turned into quiet despair?',
						'<strong>Practice:</strong> Write one sentence of hope you still believe about God. <em>Lord, strengthen my heart while I wait. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Those who wait renew strength',
					'verse_ref' => 'Isaiah 40:31',
					'body'      => hwbl_plan_day_body(
						'Exhaustion often comes from forcing outcomes God has not yet given.',
						'“They who wait for the Lord shall renew their strength; they shall mount up with wings like eagles…” Waiting is exchange: our spent strength for His.',
						'What outcome are you trying to manufacture ahead of God?',
						'<strong>Practice:</strong> Take a ten-minute walk and ask only for renewed strength. <em>Lord, renew what hurry has drained. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'In due season',
					'verse_ref' => 'Galatians 6:9',
					'body'      => hwbl_plan_day_body(
						'Delay tempts us to quit faithful work. Paul ties perseverance to harvest timing.',
						'“And let us not grow weary of doing good, for in due season we will reap, if we do not give up.” Season belongs to God; sowing belongs to us.',
						'What good work are you tempted to abandon because fruit is slow?',
						'<strong>Practice:</strong> Do one small act of good you had postponed. <em>Lord of seasons, keep me from quitting. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Abraham waited',
					'verse_ref' => 'Hebrews 6:15',
					'body'      => hwbl_plan_day_body(
						'Biblical waiting is not empty; it is anchored to promise.',
						'“And thus Abraham, having patiently waited, obtained the promise.” Patience is not passive—it refuses shortcuts that wreck trust (see Genesis 16).',
						'What shortcut looks attractive in your delay?',
						'<strong>Practice:</strong> Name the shortcut and choose the slower obedient path today. <em>Faithful God, teach me Abraham’s patience. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'My times are in Your hand',
					'verse_ref' => 'Psalm 31:14-15',
					'body'      => hwbl_plan_day_body(
						'Anxiety about timing often hides a deeper question: Who holds the calendar?',
						'“But I trust in you, O Lord… My times are in your hand.” Your seasons—delay included—are not random.',
						'What “time” are you clutching as if God might drop it?',
						'<strong>Practice:</strong> Open your hands and pray Psalm 31:15 aloud twice. <em>Lord, my times are in Your hand. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Watch and pray',
					'verse_ref' => 'Mark 13:33',
					'body'      => hwbl_plan_day_body(
						'Waiting for Christ’s return trains everyday waiting: alertness without panic.',
						'“Be on guard, keep awake. For you do not know when the time will come.” Watchfulness is readiness, not restless control.',
						'Are you more awake to Christ’s ways—or more numb in delay?',
						'<strong>Practice:</strong> End the day with a short watch: confess, thank, ask. <em>Come, Lord Jesus—keep me awake in love. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Hope that is seen is not hope',
					'verse_ref' => 'Romans 8:24-25',
					'body'      => hwbl_plan_day_body(
						'If everything were already visible, hope would be unnecessary. Waiting belongs to Christian life.',
						'“For in this hope we were saved… But if we hope for what we do not see, we wait for it with patience.” Patience is hope’s pace.',
						'What unseen hope is God inviting you to keep?',
						'<strong>Practice:</strong> Tell a trusted friend the hope you are waiting for and ask them to pray. <em>Spirit of hope, teach me patient waiting. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Rest for Weary Souls (7 days)',
			'topic'     => 'rest',
			'shareable' => false,
			'excerpt'   => 'Seven days on Sabbath rhythms, trustful rest, and the rest Jesus gives when productivity has become a false god.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Come to Me and rest',
					'verse_ref' => 'Matthew 11:28-30',
					'body'      => hwbl_plan_day_body(
						'Weariness is not only physical. Jesus invites the overloaded to Himself.',
						'“Come to me, all who labor and are heavy laden, and I will give you rest… For my yoke is easy, and my burden is light.” Rest is a Person before it is a nap.',
						'What heavy load are you carrying alone?',
						'<strong>Practice:</strong> Sit five quiet minutes and hand one burden to Jesus by name. <em>Jesus, I come—give me Your rest. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Sabbath was made for man',
					'verse_ref' => 'Mark 2:27',
					'body'      => hwbl_plan_day_body(
						'Rest can become rule-keeping or ignored entirely. Jesus recovers its gift.',
						'“The Sabbath was made for man, not man for the Sabbath.” God designed stopping for human flourishing under His care.',
						'Do you treat rest as weakness, luxury, or obedience?',
						'<strong>Practice:</strong> Block one protected hour this week with no productivity goal. <em>Lord of the Sabbath, teach me to stop. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Be still',
					'verse_ref' => 'Psalm 46:10',
					'body'      => hwbl_plan_day_body(
						'Noise and urgency can drown out God. Stillness is not emptiness—it is surrender.',
						'“Be still, and know that I am God.” Knowing God requires ceasing the frantic self-saving that pretends we are God.',
						'What noise do you use to avoid being still?',
						'<strong>Practice:</strong> Silence phones for fifteen minutes and breathe Psalm 46:10. <em>God, I am still—You are God. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'He gives to His beloved sleep',
					'verse_ref' => 'Psalm 127:1-2',
					'body'      => hwbl_plan_day_body(
						'Anxious labor can feel holy while it denies God’s care.',
						'“Unless the Lord builds the house, those who build it labor in vain… he gives to his beloved sleep.” Sleep can be trust embodied.',
						'Where has sleepless striving become your theology?',
						'<strong>Practice:</strong> End work at a set time tonight and receive sleep as gift. <em>Builder of the house, I trust You with what I leave unfinished. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Six days you shall labor',
					'verse_ref' => 'Exodus 20:8-10',
					'body'      => hwbl_plan_day_body(
						'Biblical rest assumes honest work. Sabbath is not laziness; it is holy interruption.',
						'“Remember the Sabbath day, to keep it holy… Six days you shall labor, and do all your work.” Work and rest both belong to discipleship.',
						'Is your imbalance overwork, underwork, or restless half-work?',
						'<strong>Practice:</strong> Finish one neglected duty, then stop without guilt. <em>Lord, order my work and my rest. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'A quiet life',
					'verse_ref' => '1 Thessalonians 4:11-12',
					'body'      => hwbl_plan_day_body(
						'Rest includes a quieter ambition—faithfulness over spectacle.',
						'“Aspire to live quietly, and to mind your own affairs, and to work with your hands…” Quiet lives can be deeply fruitful.',
						'Where is spectacle crowding out quiet faithfulness?',
						'<strong>Practice:</strong> Do one unnoticed act of service today. <em>Lord, form a quiet, faithful life in me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Enter His rest',
					'verse_ref' => 'Hebrews 4:9-11',
					'body'      => hwbl_plan_day_body(
						'The deepest rest is gospel rest—ceasing from self-justification.',
						'“So then, there remains a Sabbath rest for the people of God… Let us therefore strive to enter that rest.” We strive to stop striving for righteousness we cannot earn.',
						'Are you still trying to prove yourself to God—or resting in Christ?',
						'<strong>Practice:</strong> Confess any performance-for-God and thank Christ for finished work. <em>Jesus, I enter Your rest by faith. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Walking in Humility (7 days)',
			'topic'     => 'humility',
			'shareable' => false,
			'excerpt'   => 'Seven days on the upside-down way of Jesus—laying down pride, receiving grace, and lifting others up.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'God opposes the proud',
					'verse_ref' => 'James 4:6',
					'body'      => hwbl_plan_day_body(
						'Pride is not confidence; it is competition with God. James is blunt about the stakes.',
						'“God opposes the proud but gives grace to the humble.” Humility is the doorway to grace; pride picks a fight with God.',
						'Where might God be opposing a proud posture in you?',
						'<strong>Practice:</strong> Ask a trusted person where they see pride in you—and listen. <em>Lord, give grace to a humbled heart. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'The mind of Christ',
					'verse_ref' => 'Philippians 2:5-8',
					'body'      => hwbl_plan_day_body(
						'Humility is not self-hatred. It is Christlike self-giving.',
						'Christ “emptied himself, by taking the form of a servant… he humbled himself by becoming obedient to the point of death.” The cross defines true greatness.',
						'What status or preference are you refusing to empty?',
						'<strong>Practice:</strong> Choose one servant task no one will applaud. <em>Jesus, give me Your mind among others. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Do nothing from rivalry',
					'verse_ref' => 'Philippians 2:3-4',
					'body'      => hwbl_plan_day_body(
						'Comparison fuels pride and despair. Paul redirects our eyes.',
						'“Do nothing from selfish ambition or conceit, but in humility count others more significant than yourselves.” Significance here means attentive value, not false flattery.',
						'Who have you been measuring yourself against?',
						'<strong>Practice:</strong> Celebrate someone else’s win without adding your story. <em>Lord, free me from rivalry. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Clothe yourselves',
					'verse_ref' => '1 Peter 5:5-6',
					'body'      => hwbl_plan_day_body(
						'Humility is daily clothing, not a personality type.',
						'“Clothe yourselves, all of you, with humility toward one another… Humble yourselves, therefore, under the mighty hand of God so that at the proper time he may exalt you.” Timing of exaltation belongs to God.',
						'Are you grabbing exaltation ahead of God’s hand?',
						'<strong>Practice:</strong> Yield one decision to God’s timing without lobbying. <em>Mighty God, I humble myself under Your hand. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Take the lowest place',
					'verse_ref' => 'Luke 14:10-11',
					'body'      => hwbl_plan_day_body(
						'Jesus watches how we scramble for honor and tells a better way.',
						'“For everyone who exalts himself will be humbled, and he who humbles himself will be exalted.” Kingdom seating charts run opposite to ours.',
						'Where do you instinctively claim the higher seat?',
						'<strong>Practice:</strong> Let someone else go first—line, credit, or conversation. <em>Lord, teach me the lowest place with joy. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Boasting only in the Lord',
					'verse_ref' => 'Jeremiah 9:23-24',
					'body'      => hwbl_plan_day_body(
						'We boast in wisdom, strength, or riches—subtle or loud. God redirects glory.',
						'“Let him who boasts boast in this, that he understands and knows me…” Knowing the Lord is the only safe boast.',
						'What résumé line quietly props up your identity?',
						'<strong>Practice:</strong> Thank God for one gift without mentioning it to others today. <em>Lord, let my only boast be knowing You. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Wash one another’s feet',
					'verse_ref' => 'John 13:14-15',
					'body'      => hwbl_plan_day_body(
						'Jesus’ humility gets dirty. Authority kneels with a towel.',
						'“If I then, your Lord and Teacher, have washed your feet, you also ought to wash one another’s feet.” Example, not mere metaphor.',
						'Whose “feet” has pride kept you from washing?',
						'<strong>Practice:</strong> Do one concrete care act for someone “beneath” your schedule. <em>Lord and Teacher, I follow Your towel. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'When Temptation Knocks (7 days)',
			'topic'     => 'temptation',
			'shareable' => false,
			'excerpt'   => 'Seven days for resisting temptation—with honesty about desire, the way of escape, and the mercy of Christ our Helper.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Tempted as we are',
					'verse_ref' => 'Hebrews 4:15-16',
					'body'      => hwbl_plan_day_body(
						'Shame often isolates us in temptation. Jesus draws near instead.',
						'“For we do not have a high priest who is unable to sympathize with our weaknesses, but one who in every respect has been tempted as we are, yet without sin. Let us then with confidence draw near…” Help is available at the throne.',
						'Have you been hiding from God in the very place you need Him?',
						'<strong>Practice:</strong> Draw near in prayer and name the temptation without polishing it. <em>Sympathetic High Priest, give mercy and help. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Desire conceives',
					'verse_ref' => 'James 1:14-15',
					'body'      => hwbl_plan_day_body(
						'Temptation is not only external. James traces the inner path.',
						'“Each person is tempted when he is lured and enticed by his own desire. Then desire when it has conceived gives birth to sin…” Desire needs early interruption.',
						'Where does desire usually get a free pass in your mind?',
						'<strong>Practice:</strong> Interrupt the first lure today—leave the room, change the input, call a friend. <em>Lord, stop desire before it conceives. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'The way of escape',
					'verse_ref' => '1 Corinthians 10:13',
					'body'      => hwbl_plan_day_body(
						'We often believe our temptation is unique and irresistible. Scripture disagrees.',
						'“God is faithful, and he will not let you be tempted beyond your ability, but with the temptation he will also provide the way of escape…” Escape is usually ordinary obedience, not a miracle feeling.',
						'What escape path has God already provided that you ignore?',
						'<strong>Practice:</strong> Pre-decide your escape for tonight’s likely weak moment. <em>Faithful God, I will take the way You provide. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Watch and pray',
					'verse_ref' => 'Matthew 26:41',
					'body'      => hwbl_plan_day_body(
						'Jesus knows willing spirits and weak flesh. Vigilance is love, not paranoia.',
						'“Watch and pray that you may not enter into temptation. The spirit indeed is willing, but the flesh is weak.” Prayer is resistance training.',
						'Where is your flesh weakest—and are you watching there?',
						'<strong>Practice:</strong> Set two watch-alarms today to pray a one-sentence plea for strength. <em>Jesus, keep me watching and praying. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Flee and pursue',
					'verse_ref' => '2 Timothy 2:22',
					'body'      => hwbl_plan_day_body(
						'Some temptations require flight, not negotiation. Paul pairs fleeing with pursuing.',
						'“So flee youthful passions and pursue righteousness, faith, love, and peace, along with those who call on the Lord from a pure heart.” Community is part of the chase.',
						'What should you flee—and what holy pursuit should replace it?',
						'<strong>Practice:</strong> Invite one believer into your pursuit this week. <em>Lord, I flee and I pursue with Your people. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Take every thought captive',
					'verse_ref' => '2 Corinthians 10:5',
					'body'      => hwbl_plan_day_body(
						'Battles often begin in the imagination. Paul speaks of mental warfare.',
						'“We… take every thought captive to obey Christ.” Captivity here is liberation—thoughts obey Jesus instead of ruling you.',
						'Which recurring thought needs captivity today?',
						'<strong>Practice:</strong> When it returns, speak aloud: “This thought obeys Christ.” <em>Christ, take my thoughts captive. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'If anyone sins',
					'verse_ref' => '1 John 1:9',
					'body'      => hwbl_plan_day_body(
						'Victory is the goal; confession is the recovery path when we fall. Grace is not a license—it is a door back.',
						'“If we confess our sins, he is faithful and just to forgive us our sins and to cleanse us from all unrighteousness.” Faithful and just—because of the cross.',
						'What unconfessed failure is keeping you distant?',
						'<strong>Practice:</strong> Confess specifically to God (and a safe person if needed) and receive cleansing. <em>Faithful God, forgive and cleanse me. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Joy That Remains (7 days)',
			'topic'     => 'joy',
			'shareable' => false,
			'excerpt'   => 'Seven days on Christian joy—deeper than mood, rooted in Christ, able to sing even when circumstances do not.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Rejoice in the Lord',
					'verse_ref' => 'Philippians 4:4',
					'body'      => hwbl_plan_day_body(
						'Joy is commanded because it is possible in Christ—not because life is easy.',
						'“Rejoice in the Lord always; again I will say, rejoice.” The location of joy is “in the Lord,” not in outcomes.',
						'Where have you tied joy to circumstances you cannot control?',
						'<strong>Practice:</strong> Name three ways the Lord Himself is good today. <em>Lord, I rejoice in You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'The joy of Your salvation',
					'verse_ref' => 'Psalm 51:12',
					'body'      => hwbl_plan_day_body(
						'Sin and shame steal joy. David asks not for a new feeling first, but restored salvation-joy.',
						'“Restore to me the joy of your salvation, and uphold me with a willing spirit.” Salvation is the fountain; joy is its overflow.',
						'Has guilt muted your gladness in being saved?',
						'<strong>Practice:</strong> Thank God specifically for one mercy of the gospel. <em>Restore, O God, the joy of Your salvation. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Strength for the day',
					'verse_ref' => 'Nehemiah 8:10',
					'body'      => hwbl_plan_day_body(
						'Joy is not cosmetic; it strengthens weary people for obedience.',
						'“Do not be grieved, for the joy of the Lord is your strength.” God’s joy fuels courage when grief is real.',
						'Where do you need strength that only glad trust can give?',
						'<strong>Practice:</strong> Sing or play one worship song before a hard task. <em>Lord, let Your joy be my strength. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'These things I have spoken',
					'verse_ref' => 'John 15:11',
					'body'      => hwbl_plan_day_body(
						'Jesus links His words and abiding to joy that is full—not thin positivity.',
						'“These things I have spoken to you, that my joy may be in you, and that your joy may be full.” His joy becomes ours as we abide.',
						'Are you abiding—or skim-reading Jesus from a distance?',
						'<strong>Practice:</strong> Linger ten minutes in John 15 without multitasking. <em>Jesus, plant Your joy in me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Count it all joy',
					'verse_ref' => 'James 1:2-3',
					'body'      => hwbl_plan_day_body(
						'Joy in trials is not denial. It is trust that God is forming endurance.',
						'“Count it all joy, my brothers, when you meet trials of various kinds, for you know that the testing of your faith produces steadfastness.” Counting is a deliberate valuation.',
						'What trial could you “count” differently today?',
						'<strong>Practice:</strong> Write one sentence: “This trial can produce _____ in me.” <em>Lord, teach me to count joy wisely. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Joy in the Holy Spirit',
					'verse_ref' => 'Romans 14:17',
					'body'      => hwbl_plan_day_body(
						'The kingdom is not first about preferences and debates—it is Spirit-given life.',
						'“For the kingdom of God is not a matter of eating and drinking but of righteousness and peace and joy in the Holy Spirit.” Joy is kingdom evidence.',
						'Have secondary fights stolen primary joy?',
						'<strong>Practice:</strong> Release one preference battle and ask the Spirit for joy. <em>Holy Spirit, fill me with kingdom joy. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Everlasting joy',
					'verse_ref' => 'Isaiah 35:10',
					'body'      => hwbl_plan_day_body(
						'Present joy is a down payment. Full joy is coming home.',
						'“And the ransomed of the Lord shall return… everlasting joy shall be upon their heads; they shall obtain gladness and joy, and sorrow and sighing shall flee away.”',
						'How does future joy reshape today’s sighing?',
						'<strong>Practice:</strong> Encourage someone sorrowing with this hope gently. <em>Redeeming Lord, keep everlasting joy before my eyes. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Single and Secure in Christ (7 days)',
			'topic'     => 'singleness',
			'shareable' => false,
			'excerpt'   => 'Seven days for unmarried believers—undivided devotion, belonging in the family of God, and contentment without bitterness.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Undivided devotion',
					'verse_ref' => '1 Corinthians 7:32-35',
					'body'      => hwbl_plan_day_body(
						'Singleness is not a waiting room for real life. Paul calls it a gift for undivided devotion.',
						'“I say this for your own benefit… to secure your undivided devotion to the Lord.” Marriage is good; so is focused availability to Christ.',
						'Where have you treated singleness as unfinished rather than entrusted?',
						'<strong>Practice:</strong> Offer this season to God as gift, not delay. <em>Lord, secure my undivided devotion. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Content in every circumstance',
					'verse_ref' => 'Philippians 4:11-13',
					'body'      => hwbl_plan_day_body(
						'Contentment is learned, not found by arrival at a life stage.',
						'“I have learned in whatever situation I am to be content… I can do all things through him who strengthens me.” Strength is for faithfulness now.',
						'What story says you cannot be content until status changes?',
						'<strong>Practice:</strong> List three present gifts of this season. <em>Christ, strengthen me for contentment today. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Family of God',
					'verse_ref' => 'Mark 3:34-35',
					'body'      => hwbl_plan_day_body(
						'Jesus expands family beyond household walls.',
						'“Here are my mother and my brothers! For whoever does the will of God, he is my brother and sister and mother.” The church is meant to be real kinship.',
						'Are you isolated—or invested—in God’s family?',
						'<strong>Practice:</strong> Initiate one family-of-God connection this week (meal, call, serve). <em>Jesus, plant me in Your family. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Purity and honor',
					'verse_ref' => '1 Thessalonians 4:3-4',
					'body'      => hwbl_plan_day_body(
						'Singleness includes embodied holiness—not repression for its own sake, but honor.',
						'“For this is the will of God, your sanctification: that you abstain from sexual immorality; that each one of you know how to control his own body in holiness and honor.”',
						'What boundary protects honor in your relationships and media?',
						'<strong>Practice:</strong> Strengthen one purity boundary today. <em>Holy God, form honor in my body and mind. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Do not envy',
					'verse_ref' => 'Proverbs 14:30',
					'body'      => hwbl_plan_day_body(
						'Scrolling other people’s marriages and milestones can poison the soul.',
						'“A tranquil heart gives life to the flesh, but envy makes the bones rot.” Envy rots; thanksgiving heals.',
						'Whose life has envy fixed in your imagination?',
						'<strong>Practice:</strong> Bless that person by name in prayer without comparison. <em>Lord, replace envy with a tranquil heart. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Serve with your freedom',
					'verse_ref' => 'Galatians 5:13',
					'body'      => hwbl_plan_day_body(
						'Freedom is for love. Singleness can mean flexible service.',
						'“For you were called to freedom, brothers. Only do not use your freedom as an opportunity for the flesh, but through love serve one another.”',
						'How can your current freedom serve someone this week?',
						'<strong>Practice:</strong> Offer help someone married or burdened cannot easily give. <em>Lord, use my freedom to love. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Your Maker is your husband',
					'verse_ref' => 'Isaiah 54:5',
					'body'      => hwbl_plan_day_body(
						'Whether single, widowed, or waiting, God claims a deeper covenant care.',
						'“For your Maker is your husband, the Lord of hosts is his name…” This does not erase desire for marriage; it anchors identity beyond marital status.',
						'What would change if God’s covenant care were your deepest security?',
						'<strong>Practice:</strong> Journal one fear of the future and place it under this verse. <em>Maker and Husband of Your people, secure me. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Ready to Give a Reason (7 days)',
			'topic'     => 'witness',
			'shareable' => true,
			'excerpt'   => 'Seven days on gentle, courageous witness—living as light, speaking the gospel, and giving a reason for the hope within you.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Always prepared',
					'verse_ref' => '1 Peter 3:15',
					'body'      => hwbl_plan_day_body(
						'Witness starts with hope that others can see—and a readiness to explain it gently.',
						'“Always being prepared to make a defense to anyone who asks you for a reason for the hope that is in you; yet do it with gentleness and respect.”',
						'Could you give a gentle reason for your hope in two minutes?',
						'<strong>Practice:</strong> Write a 3-sentence hope-reason and practice it aloud. <em>Lord, prepare me with gentleness and respect. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'You are the light',
					'verse_ref' => 'Matthew 5:14-16',
					'body'      => hwbl_plan_day_body(
						'Before many words, Jesus calls for visible good works that point to the Father.',
						'“You are the light of the world… let your light shine before others, so that they may see your good works and give glory to your Father who is in heaven.”',
						'Where is your light hidden under busyness or fear?',
						'<strong>Practice:</strong> Do one public kindness that can lead to glory for God, not you. <em>Father, let my light point to You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Ambassadors for Christ',
					'verse_ref' => '2 Corinthians 5:20',
					'body'      => hwbl_plan_day_body(
						'We do not freelance the message. We represent the King.',
						'“Therefore, we are ambassadors for Christ, God making his appeal through us. We implore you on behalf of Christ, be reconciled to God.”',
						'Does your speech sound more like an ambassador—or a critic?',
						'<strong>Practice:</strong> Pray for one person by name: “Be reconciled to God.” <em>Christ, make Your appeal through me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'How will they hear?',
					'verse_ref' => 'Romans 10:14-15',
					'body'      => hwbl_plan_day_body(
						'Beautiful feet are ordinary feet that go. Hearing requires speaking.',
						'“And how are they to believe in him of whom they have never heard? And how are they to hear without someone preaching?”',
						'Whom has God placed near you who has not clearly heard?',
						'<strong>Practice:</strong> Share one gospel sentence or a verse card with someone this week. <em>Lord, send me with beautiful feet. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Walk in wisdom toward outsiders',
					'verse_ref' => 'Colossians 4:5-6',
					'body'      => hwbl_plan_day_body(
						'Witness includes timing, tone, and seasoned speech.',
						'“Walk in wisdom toward outsiders, making the best use of the time. Let your speech always be gracious, seasoned with salt…”',
						'Is your witness rushed, harsh, silent—or wise?',
						'<strong>Practice:</strong> Ask one curious question and listen longer than you speak. <em>Lord, season my speech with grace. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Do not be ashamed',
					'verse_ref' => 'Romans 1:16',
					'body'      => hwbl_plan_day_body(
						'Shame silences good news. Paul stakes identity on the gospel’s power.',
						'“For I am not ashamed of the gospel, for it is the power of God for salvation to everyone who believes…”',
						'Where does fear of opinion mute you?',
						'<strong>Practice:</strong> Mention Jesus naturally once today without forcing a speech. <em>God, free me from shame about Your gospel. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Go and make disciples',
					'verse_ref' => 'Matthew 28:19-20',
					'body'      => hwbl_plan_day_body(
						'The mission is disciples, not decisions alone—baptizing and teaching under Christ’s authority.',
						'“Go therefore and make disciples of all nations… teaching them to observe all that I have commanded you. And behold, I am with you always…”',
						'Who could you help take one next step as a disciple?',
						'<strong>Practice:</strong> Invite someone to read a Gospel with you or join a plan. <em>Risen Lord, I go with Your presence. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Wisdom for the Path (7 days)',
			'topic'     => 'wisdom',
			'shareable' => false,
			'excerpt'   => 'Seven days in the way of wisdom—fearing the Lord, seeking counsel, watching your words, and walking carefully in a confusing age.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'The fear of the Lord',
					'verse_ref' => 'Proverbs 9:10',
					'body'      => hwbl_plan_day_body(
						'Wisdom does not begin with tips. It begins with reverence.',
						'“The fear of the Lord is the beginning of wisdom, and the knowledge of the Holy One is insight.” Without awe of God, cleverness becomes folly.',
						'Does God weigh more than opinion in your decisions?',
						'<strong>Practice:</strong> Before a decision, pray: “What honors You?” <em>Holy One, begin wisdom in me with holy fear. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Ask God',
					'verse_ref' => 'James 1:5',
					'body'      => hwbl_plan_day_body(
						'God is not stingy with wisdom for those who ask in faith.',
						'“If any of you lacks wisdom, let him ask God, who gives generously to all without reproach, and it will be given him.”',
						'Where have you researched everyone except God?',
						'<strong>Practice:</strong> Ask specifically for wisdom on one live decision. <em>Generous Father, give wisdom without reproach. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Many counselors',
					'verse_ref' => 'Proverbs 15:22',
					'body'      => hwbl_plan_day_body(
						'Isolation invents bad plans. Wisdom seeks counsel.',
						'“Without counsel plans fail, but with many advisers they succeed.” Advisers should be godly, not merely agreeable.',
						'Whose counsel do you avoid because it might correct you?',
						'<strong>Practice:</strong> Ask one wise believer for input this week. <em>Lord, give me advisers who love truth. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'A soft answer',
					'verse_ref' => 'Proverbs 15:1',
					'body'      => hwbl_plan_day_body(
						'Wise speech de-escalates; foolish speech multiplies heat.',
						'“A soft answer turns away wrath, but a harsh word stirs up anger.” Softness here is strength under control.',
						'Where does harshness feel justified—but prove unwise?',
						'<strong>Practice:</strong> Answer one tense moment with a soft clarifying question. <em>Lord, put a soft answer on my tongue. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Look carefully then how you walk',
					'verse_ref' => 'Ephesians 5:15-17',
					'body'      => hwbl_plan_day_body(
						'Wisdom is attentive walking in evil days—not naïveté.',
						'“Look carefully then how you walk, not as unwise but as wise, making the best use of the time… understand what the will of the Lord is.”',
						'Where is your walk careless with time or attention?',
						'<strong>Practice:</strong> Redeem one wasted slot for Scripture, prayer, or service. <em>Lord, help me walk carefully. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Trust and acknowledge',
					'verse_ref' => 'Proverbs 3:5-6',
					'body'      => hwbl_plan_day_body(
						'Self-reliance feels like wisdom until paths twist.',
						'“Trust in the Lord with all your heart, and do not lean on your own understanding. In all your ways acknowledge him, and he will make straight your paths.”',
						'Where are you leaning hard on your own understanding?',
						'<strong>Practice:</strong> Acknowledge God aloud before your next three decisions. <em>Lord, I trust You—make my path straight. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Christ our wisdom',
					'verse_ref' => '1 Corinthians 1:30',
					'body'      => hwbl_plan_day_body(
						'Ultimate wisdom is not a technique—it is a Person given to us.',
						'“And because of him you are in Christ Jesus, who became to us wisdom from God, righteousness and sanctification and redemption.”',
						'Are you seeking tips more than seeking Christ?',
						'<strong>Practice:</strong> Thank Jesus that He Himself is your wisdom today. <em>Christ, be my wisdom in every path. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Faith in the Fire (7 days)',
			'topic'     => 'suffering',
			'shareable' => false,
			'excerpt'   => 'Seven days for those in ongoing hardship—suffering with Christ, refusing despair, and holding hope when the fire does not quickly end.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Do not be surprised',
					'verse_ref' => '1 Peter 4:12-13',
					'body'      => hwbl_plan_day_body(
						'Suffering can feel like failure. Peter says fiery trials are not strange for followers of Jesus.',
						'“Beloved, do not be surprised at the fiery trial when it comes upon you to test you, as though something strange were happening to you. But rejoice insofar as you share Christ’s sufferings…”',
						'Have you been interpreting hardship as abandonment?',
						'<strong>Practice:</strong> Tell God honestly how the fire feels—and that you still belong to Christ. <em>Lord, meet me in this fire. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'God of all comfort',
					'verse_ref' => '2 Corinthians 1:3-4',
					'body'      => hwbl_plan_day_body(
						'Comfort is not only relief; it is presence that later overflows to others.',
						'“Blessed be… the God of all comfort, who comforts us in all our affliction, so that we may be able to comfort those who are in any affliction…”',
						'Whom might your present pain someday help you comfort?',
						'<strong>Practice:</strong> Receive comfort in prayer; note one person who suffers similarly. <em>God of all comfort, hold me and use me. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'My grace is sufficient',
					'verse_ref' => '2 Corinthians 12:9',
					'body'      => hwbl_plan_day_body(
						'Not every thorn is removed. Sometimes grace is the answer.',
						'“My grace is sufficient for you, for my power is made perfect in weakness.” Weakness can become the place God’s power shows.',
						'What weakness are you demanding God erase rather than inhabit?',
						'<strong>Practice:</strong> Stop fighting one unremoved thorn for ten minutes and ask for sufficient grace. <em>Lord, Your grace is enough today. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Though He slay me',
					'verse_ref' => 'Job 13:15',
					'body'      => hwbl_plan_day_body(
						'Job’s faith is not tidy. It clings when explanations fail.',
						'“Though he slay me, I will hope in him…” Honest lament and stubborn hope can share a sentence.',
						'Can you hope in God without having answers?',
						'<strong>Practice:</strong> Pray a lament: “This hurts—and I still hope in You.” <em>God, I hope in You in the dark. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Momentary affliction',
					'verse_ref' => '2 Corinthians 4:16-18',
					'body'      => hwbl_plan_day_body(
						'Paul does not minimize pain; he relocates it beside eternal weight of glory.',
						'“For this light momentary affliction is preparing for us an eternal weight of glory beyond all comparison, as we look not to the things that are seen but to the things that are unseen.”',
						'What seen thing has filled your whole field of vision?',
						'<strong>Practice:</strong> Read this passage twice, then name one unseen hope. <em>Lord, fix my eyes beyond what is seen. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Cast your cares',
					'verse_ref' => '1 Peter 5:7',
					'body'      => hwbl_plan_day_body(
						'Suffering multiplies cares. Peter invites casting, not carrying alone.',
						'“Casting all your anxieties on him, because he cares for you.” God’s care is personal, not theoretical.',
						'Which anxiety are you still carrying as if He does not care?',
						'<strong>Practice:</strong> Write the cares, then cross them out as cast. <em>Caring God, I cast these on You. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'He will wipe every tear',
					'verse_ref' => 'Revelation 21:4',
					'body'      => hwbl_plan_day_body(
						'The fire is not forever. The story ends with wiped tears and no more death.',
						'“He will wipe away every tear from their eyes, and death shall be no more, neither shall there be mourning, nor crying, nor pain anymore…”',
						'How does this ending steady you for one more faithful day?',
						'<strong>Practice:</strong> Encourage another sufferer with this promise. <em>Coming King, wipe tears—and keep me until You do. Amen.</em>'
					),
				),
			),
		),
	);
}
