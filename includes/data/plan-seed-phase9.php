<?php
/**
 * Phase 9 life-issues plan definitions (Hidden Word study voice).
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
 * Phase 9 plan payloads for upsert.
 *
 * @return array<int, array<string, mixed>>
 */
function hwbl_plan_seed_phase9_definitions() {
	$safety = 'This reading plan is pastoral Scripture study, not crisis counseling or medical care. If you are in immediate danger, call your local emergency number. In the US and Canada, call or text 988 (Suicide &amp; Crisis Lifeline). Reach a trusted pastor, counselor, or doctor alongside these daily readings.';

	return array(
		array(
			'title'     => 'When Life Feels Overwhelming (7 days)',
			'topic'     => 'life-problems',
			'shareable' => false,
			'excerpt'   => 'A seven-day walk for seasons when problems stack up—bringing chaos to God, receiving His strength, and taking one faithful step at a time.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Come with the whole load',
					'verse_ref' => 'Matthew 11:28',
					'body'      => hwbl_plan_day_body(
						'Some weeks the problems do not arrive one at a time. Bills, relationships, health, work, and worry pile into one heavy backpack. Jesus does not ask you to pretend it is light.',
						'“Come to me, all who labor and are heavy laden, and I will give you rest.” The invitation is personal—come—and it is for the weary, not the already-sorted. Rest is His gift, not a prize for finishing your to-do list.',
						'What load are you still carrying alone? Name it without editing for how “spiritual” it sounds.',
						'<strong>Practice:</strong> Write three weights on a card and pray Matthew 11:28 over each. <em>Jesus, I come with this load. Give me Your rest today. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Strength for this day',
					'verse_ref' => 'Isaiah 40:31',
					'body'      => hwbl_plan_day_body(
						'Overwhelm often comes from trying to live tomorrow’s battles with today’s strength. Scripture points exhausted people toward waiting on the Lord.',
						'Those who wait for the Lord “shall renew their strength… they shall run and not be weary.” Waiting is not passivity; it is active trust that God supplies what the day requires.',
						'Where have you been sprinting in your own power? What would it look like to wait before you act?',
						'<strong>Practice:</strong> Sit quietly for two minutes before your next hard task. Ask for renewed strength. <em>Lord, renew my strength for this day alone. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Cast the care, keep the trust',
					'verse_ref' => '1 Peter 5:7',
					'body'      => hwbl_plan_day_body(
						'Overwhelm keeps score of every unresolved “what if.” Peter offers a different move: cast the care because God cares for you.',
						'“Casting all your anxieties on him” is active—throw the weight onto the Lord. The reason is not that problems are imaginary, but that His care is personal.',
						'Which anxiety have you been rehearsing instead of releasing?',
						'<strong>Practice:</strong> Speak one worry aloud to God, then leave the room without picking it back up for ten minutes. <em>Father, I cast this care on You because You care for me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Peace in the storm',
					'verse_ref' => 'John 16:33',
					'body'      => hwbl_plan_day_body(
						'Life’s problems can make you feel like faith failed. Jesus prepared His friends for trouble without denying His victory.',
						'“In the world you will have tribulation. But take heart; I have overcome the world.” Trouble is expected; despair is not required. Courage rests on His finished overcoming.',
						'Where do you need “take heart” more than a quick fix?',
						'<strong>Practice:</strong> Write John 16:33 and underline “I have overcome.” Carry it today. <em>Jesus, You have overcome. Steady my heart in tribulation. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Ask for wisdom',
					'verse_ref' => 'James 1:5',
					'body'      => hwbl_plan_day_body(
						'Overwhelm multiplies when you do not know which problem to tackle first. James points the uncertain toward a generous God.',
						'If anyone lacks wisdom, “let him ask God, who gives generously to all without reproach.” God does not shame honest need; He supplies guidance.',
						'What decision feels foggy right now? Are you asking, or only spinning?',
						'<strong>Practice:</strong> Ask God for wisdom for one specific choice, then take the next small obedient step. <em>Lord, give me wisdom without reproach. Show the next step. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Do not grow weary',
					'verse_ref' => 'Galatians 6:9',
					'body'      => hwbl_plan_day_body(
						'Long problems drain hope. Paul encourages perseverance when doing good feels fruitless.',
						'“Let us not grow weary of doing good, for in due season we will reap, if we do not give up.” Faithfulness has a harvest timing you may not see yet.',
						'Where are you tempted to quit the good you know to do?',
						'<strong>Practice:</strong> Choose one small good act you can finish today. <em>Father, keep me from weariness. Help me not give up. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'God works for good',
					'verse_ref' => 'Romans 8:28',
					'body'      => hwbl_plan_day_body(
						'Not every problem is good—but God is not absent in the pile. Paul anchors suffering believers in God’s purposeful love.',
						'“We know that for those who love God all things work together for good” for those called according to His purpose. This is not a denial of evil; it is a promise that God weaves even hard threads toward Christlike good.',
						'Can you trust God with an unfinished story this week?',
						'<strong>Practice:</strong> Thank God for one past hardship He has already used for good. <em>Lord, I trust You to work for good even here. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Faith When the Body Hurts (7 days)',
			'topic'     => 'health',
			'shareable' => false,
			'excerpt'   => 'Scripture for illness, chronic pain, and waiting—honest lament, steadfast hope, and trust in the God who sees your body and soul.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'God knows your frame',
					'verse_ref' => 'Psalm 103:14',
					'body'      => hwbl_plan_day_body(
						'Pain can make you feel forgotten or weak in faith. The psalmist reminds us that God remembers what we are made of.',
						'“He knows our frame; he remembers that we are dust.” The Lord is not surprised by frailty. Compassion is part of His character toward limited people.',
						'Where have you been harsh with yourself for being human and hurting?',
						'<strong>Practice:</strong> Place a hand over your heart and thank God that He knows your frame. <em>Father, remember that I am dust. Meet me with compassion. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Lament is welcome',
					'verse_ref' => 'Psalm 6:2-3',
					'body'      => hwbl_plan_day_body(
						'Some Christian cultures push a smile through pain. Scripture gives language for groaning.',
						'David cries, “Be gracious to me, O Lord, for I am languishing… My soul also is greatly troubled.” Honest lament is not faithlessness; it is faith talking to God in the dark.',
						'What have you been editing out of your prayers because it sounded too raw?',
						'<strong>Practice:</strong> Pray one unfiltered sentence about your body or diagnosis. <em>Lord, be gracious to me in this languishing. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Grace in weakness',
					'verse_ref' => '2 Corinthians 12:9',
					'body'      => hwbl_plan_day_body(
						'Illness can feel like a thorn that will not leave. Paul learned to hear grace in unanswered removal.',
						'God said, “My grace is sufficient for you, for my power is made perfect in weakness.” Sufficient means enough for today—not a denial of need for doctors, rest, or help.',
						'Where might Christ’s power be meeting you precisely in limitation?',
						'<strong>Practice:</strong> Name one weakness without fixing language, then ask for sufficient grace. <em>Lord, Your grace is enough here. Let Your power rest on me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Pray and pursue care',
					'verse_ref' => 'James 5:14-15',
					'body'      => hwbl_plan_day_body(
						'Faith and medicine are not rivals. James joins prayer, the church, and healing in the same paragraph.',
						'The sick are to call the elders to pray and anoint—faith expressed in community. Scripture also honors ordinary means of care; seeking help is not unbelief.',
						'Have you isolated instead of asking the church or a clinician to walk with you?',
						'<strong>Practice:</strong> Text one trusted believer or schedule a needed appointment. <em>God of healing, guide my care and hear the prayers of Your people. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Steadfast under trial',
					'verse_ref' => 'James 1:2-4',
					'body'      => hwbl_plan_day_body(
						'Chronic waiting tests more than acute crisis. James reframes trials as places where steadfastness grows.',
						'“Count it all joy… when you meet trials of various kinds,” because testing produces steadfastness. Joy here is not cheerfulness about pain; it is confidence that God is forming endurance.',
						'What endurance is God growing in you that you could not learn in ease?',
						'<strong>Practice:</strong> Thank God for one inch of endurance you did not have last year. <em>Lord, produce steadfastness in me without wasting this trial. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Bodies that will be raised',
					'verse_ref' => '1 Corinthians 15:42-44',
					'body'      => hwbl_plan_day_body(
						'When the body fails, hope can shrink to “just get through today.” Paul lifts our eyes to resurrection.',
						'What is sown perishable is raised imperishable; what is sown in weakness is raised in power. Your present body matters—and it is not the final word.',
						'How does resurrection hope change the way you endure today’s symptoms?',
						'<strong>Practice:</strong> Read 1 Corinthians 15:42–44 slowly twice. <em>Risen Lord, anchor me in the hope of a raised body. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'His presence is enough',
					'verse_ref' => 'Psalm 23:4',
					'body'      => hwbl_plan_day_body(
						'Some valleys do not end on our preferred timeline. The shepherd promise is presence, not always an instant exit.',
						'“Even though I walk through the valley of the shadow of death, I will fear no evil, for you are with me.” The rod and staff comfort because the Shepherd walks with you.',
						'Can you receive “You are with me” as today’s deepest need?',
						'<strong>Practice:</strong> Whisper “You are with me” on each of three slow breaths. <em>Shepherd, walk with me through this valley. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Marriage Under Strain (7 days)',
			'topic'     => 'marriage',
			'shareable' => false,
			'excerpt'   => 'A seven-day study for marriages under pressure—Christlike love, honest repentance, patient hope, and help that refuses contempt.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Love that stays',
					'verse_ref' => '1 Corinthians 13:4-7',
					'body'      => hwbl_plan_day_body(
						'Strain turns small frictions into stories about “always” and “never.” Paul describes love that behaves differently under pressure.',
						'Love is patient and kind; it does not envy or boast; it is not arrogant or rude. It “bears… believes… hopes… endures all things.” This is not romantic fluff—it is cruciform character.',
						'Which phrase in 1 Corinthians 13 most confronts how you have spoken lately?',
						'<strong>Practice:</strong> Choose one patient or kind act toward your spouse today—no speech attached. <em>Lord, form this love in me when I feel thin. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Outdo one another in honor',
					'verse_ref' => 'Romans 12:10',
					'body'      => hwbl_plan_day_body(
						'Contempt is marital poison. Scripture calls believers to honor even when feelings run cold.',
						'“Love one another with brotherly affection. Outdo one another in showing honor.” Honor is a choice to treat the other as valuable before God.',
						'Where has contempt (eye-rolls, sarcasm, silent scorekeeping) crept in?',
						'<strong>Practice:</strong> Speak one specific honor sentence to your spouse, or write it if speaking is hard. <em>Father, help me outdo in honor, not in winning. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Quick to hear',
					'verse_ref' => 'James 1:19',
					'body'      => hwbl_plan_day_body(
						'Under strain we rehearse rebuttals while the other is still talking. James gives a posture for conflict.',
						'“Let every person be quick to hear, slow to speak, slow to anger.” Hearing is ministry. Slow speech protects the bond.',
						'In your last argument, were you primarily listening or loading ammunition?',
						'<strong>Practice:</strong> Ask your spouse one clarifying question and paraphrase before you reply. <em>Lord, make me quick to hear and slow to anger. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Confess and forgive',
					'verse_ref' => 'Colossians 3:13',
					'body'      => hwbl_plan_day_body(
						'Strain hardens when neither spouse will go first in repentance. Paul roots forgiveness in how Christ treated us.',
						'“Bear with one another and, if one has a complaint against another, forgiving each other; as the Lord has forgiven you, so you also must forgive.” Bearing and forgiving are ongoing, not one-time events.',
						'What apology do you owe—without the word “but”?',
						'<strong>Practice:</strong> Confess one specific fault to God, then (if safe) to your spouse. <em>Lord, as You forgave me, help me forgive and seek forgiveness. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Serve like Christ',
					'verse_ref' => 'Ephesians 5:25',
					'body'      => hwbl_plan_day_body(
						'Marriage strain often becomes a contest of who gives less. Christ models self-giving love.',
						'Husbands are called to love as Christ loved the church and gave Himself up. The pattern for all Christian love is sacrificial service, not domination. (If you are in danger, seek safety—sacrifice never means enduring abuse.)',
						'What self-giving act would bless your spouse this week without keeping score?',
						'<strong>Practice:</strong> Do one hidden act of service today. <em>Jesus, teach me to give myself up in love, not in control. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Seek wise help',
					'verse_ref' => 'Proverbs 15:22',
					'body'      => hwbl_plan_day_body(
						'Pride isolates struggling couples. Wisdom invites counsel.',
						'“Without counsel plans fail, but with many advisers they succeed.” A pastor, mature couple, or Christian counselor can interrupt destructive cycles you cannot see alone.',
						'What keeps you from asking for help—fear, shame, or habit?',
						'<strong>Practice:</strong> Identify one wise person or counselor and take one step toward a conversation. <em>Lord, give us humility to seek wise help. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Hope for rebuilders',
					'verse_ref' => 'Joel 2:25',
					'body'      => hwbl_plan_day_body(
						'Some marriages feel like years of locusts. God speaks restoration to a devastated people.',
						'“I will restore to you the years that the swarming locust has eaten.” Restoration is God’s work; your part is repentance, faithfulness, and hope. Not every story looks the same—but despair is not required.',
						'Where do you need to ask God to restore what strain has eaten?',
						'<strong>Practice:</strong> Pray Joel 2:25 over your marriage by name. <em>God of restoration, rebuild what strain has damaged. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Freedom from Alcohol’s Grip (7 days)',
			'topic'     => 'alcoholism',
			'shareable' => false,
			'excerpt'   => 'Scripture for those wrestling with alcohol—truth, repentance, community, and hope in Christ’s freedom. Pair this with recovery support and pastoral care.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Walk in the light',
					'verse_ref' => 'John 8:32',
					'body'      => hwbl_plan_day_body(
						$safety . ' Alcohol’s grip thrives in secrecy and half-truths. Jesus ties freedom to truth.',
						'“You will know the truth, and the truth will set you free.” Freedom begins when we stop negotiating with what is destroying us and name reality before God.',
						'What truth about your drinking have you been minimizing?',
						'<strong>Practice:</strong> Tell God the honest truth in one sentence. Consider telling one safe person this week. <em>Jesus, I want Your truth more than my cover story. Set me free. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Do not be mastered',
					'verse_ref' => '1 Corinthians 6:12',
					'body'      => hwbl_plan_day_body(
						'Culture sells “I can handle it.” Scripture asks who is handling whom.',
						'“All things are lawful for me, but I will not be dominated by anything.” Even permitted things become chains when they master you. Christ’s people are not meant to live under a chemical lord.',
						'Where has alcohol begun to dictate mood, schedule, or relationships?',
						'<strong>Practice:</strong> Skip the next habitual drink and note what emotion rises instead. <em>Lord, I refuse to be mastered. Be my Master today. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Be filled with the Spirit',
					'verse_ref' => 'Ephesians 5:18',
					'body'      => hwbl_plan_day_body(
						'Paul contrasts two fillings: wine that controls, and the Spirit who fills.',
						'“Do not get drunk with wine… but be filled with the Spirit.” Sobriety is not emptiness; it is room for God’s presence, worship, and wise community.',
						'What do you reach for when you feel empty, angry, or alone?',
						'<strong>Practice:</strong> When craving hits, pray “Fill me with Your Spirit” and call/text a support person. <em>Holy Spirit, fill what wine cannot satisfy. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Confess to walk free',
					'verse_ref' => 'James 5:16',
					'body'      => hwbl_plan_day_body(
						'Isolation feeds addiction. James links confession, prayer, and healing.',
						'“Confess your sins to one another and pray for one another, that you may be healed.” Healing often travels the path of humble disclosure in safe community (pastor, recovery group, trusted believer).',
						'Who could hear your confession without shaming you?',
						'<strong>Practice:</strong> Schedule a conversation with a pastor, sponsor, or recovery meeting. <em>Lord, give me courage to confess and receive prayer. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Put on the new self',
					'verse_ref' => 'Ephesians 4:22-24',
					'body'      => hwbl_plan_day_body(
						'Freedom is not only stopping a habit; it is becoming someone new in Christ.',
						'Put off the old self, be renewed in mind, and put on the new self created after God’s likeness. Recovery includes new patterns, friends, and rhythms—not only willpower.',
						'What “new self” habit (sleep, worship, friendship, meeting) can replace an old trigger?',
						'<strong>Practice:</strong> Choose one replacement habit for the next 24 hours. <em>Christ, clothe me in the new self today. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Temptation’s way out',
					'verse_ref' => '1 Corinthians 10:13',
					'body'      => hwbl_plan_day_body(
						'Cravings lie: “This is unique; no one understands; you must give in.” Paul contradicts that lie.',
						'God is faithful; He will not let you be tempted beyond what you can bear, but with the temptation also provides the way of escape. Escape may look like leaving the room, calling someone, or going to a meeting.',
						'What is your pre-planned escape route when craving spikes?',
						'<strong>Practice:</strong> Write your escape plan on a card and keep it with you. <em>Faithful God, show the way of escape and help me take it. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'If the Son sets you free',
					'verse_ref' => 'John 8:36',
					'body'      => hwbl_plan_day_body(
						'Shame says you will always be defined by the grip. Jesus defines freedom.',
						'“If the Son sets you free, you will be free indeed.” Freedom is Christ’s work applied over time with community, honesty, and grace for setbacks that turn back to Him.',
						'Are you willing to receive freedom as a gift you walk in daily, not a one-time feeling?',
						'<strong>Practice:</strong> Thank Jesus for freedom as a present gift, then take one recovery step today. <em>Son of God, set me free indeed—and keep me walking free. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Breaking Free from Addiction (7 days)',
			'topic'     => 'addiction',
			'shareable' => false,
			'excerpt'   => 'Hope in Christ for those bound by drugs or compulsive substance use—truth, surrender, community, and daily dependence. Use with professional and recovery help.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Call it what it is',
					'verse_ref' => 'Psalm 32:5',
					'body'      => hwbl_plan_day_body(
						$safety . ' Addiction thrives when we rename it. David found relief in honest confession.',
						'“I acknowledged my sin to you… and you forgave the iniquity of my sin.” Naming the bondage before God is the beginning of mercy, not the end of dignity.',
						'What word have you been avoiding—dependence, craving, out-of-control?',
						'<strong>Practice:</strong> Confess the true name of the struggle to God aloud. <em>Lord, I acknowledge this before You. Cover me with mercy. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Who will deliver me?',
					'verse_ref' => 'Romans 7:24-25',
					'body'      => hwbl_plan_day_body(
						'Willpower alone often collapses. Paul voices the cry of someone trapped in a cycle.',
						'“Wretched man that I am! Who will deliver me from this body of death? Thanks be to God through Jesus Christ our Lord!” Deliverance has a name: Jesus—not a pep talk.',
						'Where have you trusted grit more than the Deliverer?',
						'<strong>Practice:</strong> Pray Romans 7:24–25 slowly, inserting your struggle. <em>Jesus, deliver me. I thank God through You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Present yourself to God',
					'verse_ref' => 'Romans 6:12-13',
					'body'      => hwbl_plan_day_body(
						'Addiction commandeers the body. Paul calls believers to present their members to God as instruments of righteousness.',
						'Do not let sin reign in your mortal body… present yourselves to God as those who have been brought from death to life. Surrender is daily, practical, and bodily.',
						'What would it mean to present your hands, mind, and schedule to God today?',
						'<strong>Practice:</strong> Physically open your hands and present this day to God. Remove one access point to the substance if you can. <em>Lord, I present myself to You as alive from the dead. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Flee and pursue',
					'verse_ref' => '2 Timothy 2:22',
					'body'      => hwbl_plan_day_body(
						'Freedom is both flight and pursuit. Paul tells Timothy to flee youthful passions and pursue righteousness with those who call on the Lord.',
						'Fleeing alone is incomplete; pursue faith, love, and peace in community. Recovery rooms, church, and wise friends are part of that pursuit.',
						'What must you flee—and whom will you pursue righteousness with?',
						'<strong>Practice:</strong> Attend a meeting, call a sponsor, or ask a believer to check in daily this week. <em>Lord, help me flee what destroys and pursue You with others. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Renew the mind',
					'verse_ref' => 'Romans 12:2',
					'body'      => hwbl_plan_day_body(
						'Cravings speak in lies: “Just once,” “You deserve this,” “No one will know.” Transformation includes mind renewal.',
						'“Do not be conformed to this world, but be transformed by the renewal of your mind.” Scripture, prayer, and honest counsel push back on addictive scripts.',
						'Which lie shows up most often before use?',
						'<strong>Practice:</strong> Write the lie and a true verse beside it; read both when triggered. <em>Spirit, renew my mind; silence the lie. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Bear one another’s burdens',
					'verse_ref' => 'Galatians 6:2',
					'body'      => hwbl_plan_day_body(
						'Shame says handle it alone. The gospel forms a burden-bearing people.',
						'“Bear one another’s burdens, and so fulfill the law of Christ.” Letting others carry with you is obedience, not weakness. Professional treatment can be part of that bearing.',
						'Whose burden are you refusing to share—and who might share yours?',
						'<strong>Practice:</strong> Ask one person to pray specifically for your sobriety today. <em>Christ, teach me to receive burden-bearers. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'A future and a hope',
					'verse_ref' => 'Jeremiah 29:11',
					'body'      => hwbl_plan_day_body(
						'Addiction shrinks the future to the next fix. God speaks hope to exiles who thought the story was over.',
						'“For I know the plans I have for you… plans for welfare and not for evil, to give you a future and a hope.” This hope does not erase consequences, but it refuses despair.',
						'What hopeful next step (treatment, meeting, restitution, prayer) will you take this week?',
						'<strong>Practice:</strong> Write one future-facing step and do it or schedule it today. <em>God of hope, give me a future in You beyond this grip. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Stewardship in Tight Places (7 days)',
			'topic'     => 'finances',
			'shareable' => false,
			'excerpt'   => 'Biblical wisdom for financial pressure—trust, integrity, contentment, generosity, and wise work without panic or pretense.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Your Father knows',
					'verse_ref' => 'Matthew 6:31-33',
					'body'      => hwbl_plan_day_body(
						'Money fear wakes people at 3 a.m. Jesus addresses anxious hearts about food and clothing—the basics.',
						'“Do not be anxious… your heavenly Father knows that you need them all. But seek first the kingdom of God and his righteousness.” Priority is kingdom; provision is the Father’s care.',
						'What financial fear is loudest—and have you brought it to the Father who knows?',
						'<strong>Practice:</strong> List needs (not wants) and pray Matthew 6:33 over them. <em>Father, You know my needs. I seek Your kingdom first. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'The Lord provides',
					'verse_ref' => 'Philippians 4:19',
					'body'      => hwbl_plan_day_body(
						'Scarcity can make God feel distant. Paul writes from hardship about divine supply.',
						'“My God will supply every need of yours according to his riches in glory in Christ Jesus.” Needs, not every want—and according to His riches, not our panic.',
						'Where have you confused wants with needs?',
						'<strong>Practice:</strong> Cross out one non-essential expense this week as an act of trust. <em>God, supply every need according to Your riches in Christ. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Integrity under pressure',
					'verse_ref' => 'Proverbs 11:1',
					'body'      => hwbl_plan_day_body(
						'Tight places tempt shortcuts—lying on forms, hiding debt, using people. Wisdom honors honest scales.',
						'“A false balance is an abomination to the Lord, but a just weight is his delight.” God cares how we handle money when no one is watching.',
						'Is there a financial half-truth you need to correct?',
						'<strong>Practice:</strong> Make one honest move (call a creditor, tell a spouse the real number, fix a form). <em>Lord, make my balances just. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Contentment learned',
					'verse_ref' => 'Philippians 4:11-13',
					'body'      => hwbl_plan_day_body(
						'Comparison deepens financial misery. Paul learned contentment in plenty and in need.',
						'“I have learned in whatever situation I am to be content… I can do all things through him who strengthens me.” Strength here is for faithful living in either state—not a slogan for unlimited achievement.',
						'What comparison is stealing contentment from you?',
						'<strong>Practice:</strong> Thank God for three provisions you already have. <em>Christ, strengthen me for contentment today. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Diligent work',
					'verse_ref' => 'Proverbs 14:23',
					'body'      => hwbl_plan_day_body(
						'Prayer does not replace work; wisdom joins them. Proverbs honors diligent labor.',
						'“In all toil there is profit, but mere talk tends only to poverty.” Faithful effort—job search, skill building, showing up—is part of stewardship.',
						'What one diligent step have you been only talking about?',
						'<strong>Practice:</strong> Spend 30 focused minutes on a concrete financial or work step. <em>Lord, bless honest toil and guide my efforts. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Open-handed even now',
					'verse_ref' => '2 Corinthians 9:6-8',
					'body'      => hwbl_plan_day_body(
						'Scarcity can close every fist. Paul teaches cheerful generosity rooted in God’s ability to provide.',
						'God loves a cheerful giver and is able to make all grace abound so you may abound in every good work. Generosity in tight places is often small but powerful against fear.',
						'What small generous act could you do without pretending wealth?',
						'<strong>Practice:</strong> Give a modest gift or meal to someone in need, cheerfully. <em>God, make grace abound; free my hands. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Treasure in heaven',
					'verse_ref' => 'Matthew 6:19-21',
					'body'      => hwbl_plan_day_body(
						'Financial stress reveals what we treasure. Jesus relocates the heart’s bank.',
						'“Do not lay up for yourselves treasures on earth… but lay up for yourselves treasures in heaven… For where your treasure is, there your heart will be also.” Eternal investment reorders earthly anxiety.',
						'Where is your heart following your treasure right now?',
						'<strong>Practice:</strong> Pray over your budget as worship, asking where treasure needs relocating. <em>Jesus, move my treasure—and my heart—toward heaven. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Safe in the Shadow of the Almighty (7 days)',
			'topic'     => 'domestic-violence',
			'shareable' => false,
			'excerpt'   => 'Scripture for those harmed in the home—God sees injustice, values your life, and never requires you to stay in danger. Seek safety and wise help.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'God sees and shelters',
					'verse_ref' => 'Psalm 91:1-2',
					'body'      => hwbl_plan_day_body(
						$safety . ' If someone is hurting you, God’s Word is not a chain to keep you in danger. He is a refuge.',
						'“He who dwells in the shelter of the Most High will abide in the shadow of the Almighty… My refuge and my fortress.” Refuge means safety. Seeking help, leaving danger, and involving authorities can be acts of wisdom, not faithlessness.',
						'Have you believed a lie that God wants you to endure abuse silently?',
						'<strong>Practice:</strong> If unsafe, contact local emergency services or a domestic violence hotline. Tell one safe person. <em>Most High, be my refuge. Lead me to safety. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'The Lord hates violence',
					'verse_ref' => 'Psalm 11:5',
					'body'      => hwbl_plan_day_body(
						'Some misuse Scripture to excuse harm. God’s character opposes the violence of the wicked.',
						'“The Lord tests the righteous, but his soul hates the wicked and the one who loves violence.” Abuse is not “discipline” or “leadership.” God sides with justice.',
						'What false spiritual excuse have you heard—or told yourself—about the harm?',
						'<strong>Practice:</strong> Write: “God hates this violence.” Keep it where you can see it. <em>Lord, You hate this harm. Vindicate truth. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'You are precious',
					'verse_ref' => 'Isaiah 43:1-2',
					'body'      => hwbl_plan_day_body(
						'Abuse attacks identity. God speaks belonging over His people.',
						'“Fear not, for I have redeemed you; I have called you by name, you are mine… When you pass through the waters, I will be with you.” You are not property of an abuser; you belong to God.',
						'Which shame message needs to be replaced with “you are mine”?',
						'<strong>Practice:</strong> Read Isaiah 43:1–2 aloud with your name. <em>God who calls me by name, I am Yours. Be with me in these waters. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Speak for the silenced',
					'verse_ref' => 'Proverbs 31:8-9',
					'body'      => hwbl_plan_day_body(
						'Silence often protects the powerful. Wisdom opens the mouth for the afflicted.',
						'“Open your mouth for the mute, for the rights of all who are destitute… defend the rights of the poor and needy.” Seeking advocates, counselors, and just authorities aligns with this call.',
						'Who could safely advocate with you—or for someone you know?',
						'<strong>Practice:</strong> Contact a hotline, advocate, or trusted leader who understands abuse dynamics. <em>Lord, open mouths for justice and protection. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Jesus and the bruised',
					'verse_ref' => 'Matthew 12:20',
					'body'      => hwbl_plan_day_body(
						'Fear says one more failure will finish you. Jesus fulfills the Servant who tends the fragile.',
						'“A bruised reed he will not break, and a smoldering wick he will not quench.” Christ does not crush the wounded. He is gentle with the bruised.',
						'Where do you need His gentleness instead of self-blame?',
						'<strong>Practice:</strong> Sit quietly and receive Matthew 12:20 as spoken over you. <em>Gentle Jesus, do not break this bruised reed. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Boundaries are wise',
					'verse_ref' => 'Proverbs 22:3',
					'body'      => hwbl_plan_day_body(
						'Some confuse endless access with love. Wisdom sees danger and hides.',
						'“The prudent sees danger and hides himself, but the simple go on and suffer for it.” Distance, locks, legal orders, and no-contact can be prudence—not unforgiveness.',
						'What prudent boundary would increase safety this week?',
						'<strong>Practice:</strong> Take one concrete safety step (plan, document, leave bag, call advocate). <em>Lord, give prudence to see danger and hide wisely. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'He will wipe every tear',
					'verse_ref' => 'Revelation 21:4',
					'body'      => hwbl_plan_day_body(
						'Trauma can make the future feel impossible. God promises a world without the pain of this age.',
						'“He will wipe away every tear from their eyes, and death shall be no more, neither shall there be mourning, nor crying, nor pain anymore.” Hope does not erase today’s need for safety; it anchors you beyond the abuser’s power.',
						'Can you hold both—seek safety now and hope in Christ’s future?',
						'<strong>Practice:</strong> Thank God for one person or resource helping you toward safety. <em>Lord Jesus, wipe these tears and lead me into hope and peace. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'When Faith Feels Thin (7 days)',
			'topic'     => 'doubt',
			'shareable' => false,
			'excerpt'   => 'For dry seasons and honest questions—bringing doubt to Jesus, holding to His Word, and rediscovering trust without fake certainty.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'I believe; help my unbelief',
					'verse_ref' => 'Mark 9:24',
					'body'      => hwbl_plan_day_body(
						'Thin faith often feels like failure. A desperate father modeled honesty before Jesus.',
						'“I believe; help my unbelief!” Jesus did not crush the mixture—He met it. Faith can coexist with the cry for help in believing.',
						'Where is your belief mixed with unbelief today?',
						'<strong>Practice:</strong> Pray Mark 9:24 out loud without cleaning it up. <em>Jesus, I believe; help my unbelief. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Bring the questions',
					'verse_ref' => 'Psalm 73:16-17',
					'body'      => hwbl_plan_day_body(
						'Doubt grows toxic in isolation. Asaph nearly slipped until he entered God’s presence with his confusion.',
						'“When I thought how to understand this, it seemed to me a wearisome task, until I went into the sanctuary of God.” Sanctuary—Word, worship, and people of God—reframes painful questions.',
						'Have you been thinking your doubts alone instead of bringing them to God?',
						'<strong>Practice:</strong> Write one hard question and pray it in a quiet place or church gathering. <em>God, I bring this wearisome question into Your presence. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Faith comes by hearing',
					'verse_ref' => 'Romans 10:17',
					'body'      => hwbl_plan_day_body(
						'Feelings of thin faith often follow a starved intake of Scripture. Paul ties faith to hearing Christ’s word.',
						'“So faith comes from hearing, and hearing through the word of Christ.” Faith is fueled by the gospel heard again—not by staring at your spiritual temperature.',
						'What has crowded out regular hearing of the Word?',
						'<strong>Practice:</strong> Read one Gospel paragraph aloud twice today. <em>Lord, let faith come as I hear Your word. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Look to Jesus',
					'verse_ref' => 'Hebrews 12:1-2',
					'body'      => hwbl_plan_day_body(
						'Doubt fixates on self: “Do I feel enough faith?” Hebrews redirects the eyes.',
						'“Looking to Jesus, the founder and perfecter of our faith.” He starts and finishes faith. Your job is to look, not to manufacture certainty.',
						'What are you staring at instead of Jesus—news, failure, unanswered prayer?',
						'<strong>Practice:</strong> Sit one minute simply looking to Jesus in prayerful attention. <em>Jesus, founder and perfecter, I look to You. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Blessed are those who believe',
					'verse_ref' => 'John 20:27-29',
					'body'      => hwbl_plan_day_body(
						'Thomas wanted proof. Jesus met him without shaming, then blessed those who trust without seeing.',
						'“Do not disbelieve, but believe… Blessed are those who have not seen and yet have believed.” Honest doubt can move toward worship; Jesus still invites touch and trust.',
						'What would it look like to move one step from disbelieve toward believe today?',
						'<strong>Practice:</strong> Tell Jesus one reason you still trust Him despite unanswered questions. <em>My Lord and my God—help me believe what I have not seen. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Hold fast the confession',
					'verse_ref' => 'Hebrews 10:23',
					'body'      => hwbl_plan_day_body(
						'Dry seasons tempt us to drop spiritual habits. Hebrews calls for holding fast.',
						'“Let us hold fast the confession of our hope without wavering, for he who promised is faithful.” We hold because He is faithful—not because we feel strong.',
						'Which confession or habit will you hold fast this week?',
						'<strong>Practice:</strong> Attend worship or recite a creed/simple gospel summary aloud. <em>Faithful God, I hold fast because You promised. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'A bruised faith still belongs',
					'verse_ref' => 'Jude 1:22',
					'body'      => hwbl_plan_day_body(
						'Some churches only celebrate strong certainty. Jude tells the church to be gentle with doubt.',
						'“And have mercy on those who doubt.” If God commands mercy toward doubters, you may receive mercy for your own thin places too.',
						'Can you extend to yourself the mercy Jude commands the church to give?',
						'<strong>Practice:</strong> Thank God that mercy meets doubt, then encourage one struggling believer. <em>Lord, have mercy on my doubt and make me merciful. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'God With You in Loneliness (7 days)',
			'topic'     => 'loneliness',
			'shareable' => false,
			'excerpt'   => 'For isolated hearts—God’s nearness, the gift of the church, courageous connection, and hope that you are not abandoned.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Never forsaken',
					'verse_ref' => 'Hebrews 13:5',
					'body'      => hwbl_plan_day_body(
						'Loneliness can feel like abandonment by God and people. Scripture answers with a promise stronger than feelings.',
						'“I will never leave you nor forsake you.” God’s presence is covenant fact, not a mood. Loneliness is real—and so is His nearness.',
						'When loneliness spikes, what story do you tell yourself about God?',
						'<strong>Practice:</strong> Repeat Hebrews 13:5 three times slowly when alone tonight. <em>Lord, You will not forsake me. Make Your nearness known. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'God sets the lonely in families',
					'verse_ref' => 'Psalm 68:6',
					'body'      => hwbl_plan_day_body(
						'Isolation whispers that you will always be outside. The psalmist celebrates God’s placing work.',
						'“God settles the solitary in a home.” His design includes belonging—often through the household of faith when biological family is absent or unsafe.',
						'Where might God already be offering a “home” you have not stepped into?',
						'<strong>Practice:</strong> Visit or contact one church community event or small group. <em>God, settle me among Your people. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Jesus knows solitude',
					'verse_ref' => 'Mark 1:35',
					'body'      => hwbl_plan_day_body(
						'Loneliness and chosen solitude are different—but Jesus knew empty spaces and also the pain of desertion.',
						'Jesus rose early to pray alone—and later His friends fled. He understands both holy quiet and human abandonment. You can meet Him in either.',
						'Will you bring loneliness to Jesus as prayer rather than only as ache?',
						'<strong>Practice:</strong> Spend five undistracted minutes telling Jesus how alone you feel. <em>Jesus, You understand. Sit with me here. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'One another',
					'verse_ref' => 'Romans 12:10',
					'body'      => hwbl_plan_day_body(
						'Modern life can leave us surrounded and still unknown. The church is called into affectionate belonging.',
						'“Love one another with brotherly affection. Outdo one another in showing honor.” Belonging grows when someone goes first in honor and warmth.',
						'Could you be the first to show honor to someone else who looks lonely?',
						'<strong>Practice:</strong> Send a sincere check-in message to one person today. <em>Lord, help me give and receive brotherly affection. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Carry each other’s burdens',
					'verse_ref' => 'Galatians 6:2',
					'body'      => hwbl_plan_day_body(
						'Loneliness deepens when we never ask for help. Burden-bearing goes both ways.',
						'“Bear one another’s burdens, and so fulfill the law of Christ.” Letting someone carry with you is a gift you give them as well.',
						'What burden have you been determined to hide?',
						'<strong>Practice:</strong> Share one real need with a trusted believer. <em>Christ, teach me to let others bear my burden. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Hospitality received and given',
					'verse_ref' => '1 Peter 4:9',
					'body'      => hwbl_plan_day_body(
						'Friendship often starts with a table or a doorway. Peter calls for ungrudging hospitality.',
						'“Show hospitality to one another without grumbling.” You can both receive invitations and offer simple ones—coffee, a walk, a shared pew.',
						'What simple hospitality could you offer or accept this week?',
						'<strong>Practice:</strong> Invite someone or say yes to an invitation. <em>Lord, open doors of welcome without grumbling. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'With you always',
					'verse_ref' => 'Matthew 28:20',
					'body'      => hwbl_plan_day_body(
						'Mission can feel impossible when you feel alone. Jesus’ last word is presence.',
						'“And behold, I am with you always, to the end of the age.” The risen Christ walks with His people into every ordinary Monday.',
						'How would tomorrow change if you truly believed He is with you always?',
						'<strong>Practice:</strong> Begin tomorrow’s first minute by naming: “Jesus, You are with me.” <em>Jesus, be with me always—and help me notice. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Light in the Valley (7 days)',
			'topic'     => 'depression',
			'shareable' => false,
			'excerpt'   => 'Compassionate Scripture for dark valleys—lament, presence, small faithfulness, and hope. Not a substitute for medical or crisis care.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'God is near the crushed',
					'verse_ref' => 'Psalm 34:18',
					'body'      => hwbl_plan_day_body(
						$safety . ' Depression can feel like a fog no sermon can scold away. God draws near to the crushed.',
						'“The Lord is near to the brokenhearted and saves the crushed in spirit.” Nearness is His posture—not disappointment at your low mood.',
						'Have you assumed God is distant because you feel distant?',
						'<strong>Practice:</strong> Whisper “You are near” once each hour today. If thoughts of harming yourself arise, call/text 988 or local emergency help now. <em>Lord, come near to this crushed spirit. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'How long, O Lord?',
					'verse_ref' => 'Psalm 13:1-2',
					'body'      => hwbl_plan_day_body(
						'Dark valleys make time feel endless. David gives language for the wait.',
						'“How long, O Lord? Will you forget me forever?… How long must I take counsel in my soul and have sorrow in my heart all the day?” Lament is permitted prayer.',
						'What “how long” question do you need to stop polishing?',
						'<strong>Practice:</strong> Pray Psalm 13 aloud, then add your own “how long.” <em>O Lord, I bring You my how long. Do not forget me. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'He leads beside still waters',
					'verse_ref' => 'Psalm 23:1-3',
					'body'      => hwbl_plan_day_body(
						'Depression drains energy for “productive” spirituality. The Shepherd restores souls gently.',
						'“He makes me lie down in green pastures. He leads me beside still waters. He restores my soul.” Rest can be obedience. Care from doctors and counselors can be part of His leading.',
						'What rest or help have you refused because it felt “unspiritual”?',
						'<strong>Practice:</strong> Take one restoring step (nap, walk, medication as prescribed, therapy call). <em>Shepherd, lead me beside still waters and restore my soul. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'A bruised reed',
					'verse_ref' => 'Isaiah 42:3',
					'body'      => hwbl_plan_day_body(
						'Low seasons come with self-contempt. The Servant of the Lord is gentle with the fragile.',
						'“A bruised reed he will not break, and a faintly burning wick he will not quench.” God does not despise your small flame.',
						'Where are you breaking yourself with demands Jesus would not make?',
						'<strong>Practice:</strong> Replace one harsh self-statement with Isaiah 42:3. <em>Gentle Lord, do not quench this faintly burning wick. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'One day at a time',
					'verse_ref' => 'Matthew 6:34',
					'body'      => hwbl_plan_day_body(
						'Depression often loads every future fear into today. Jesus limits the load.',
						'“Therefore do not be anxious about tomorrow, for tomorrow will be anxious for itself. Sufficient for the day is its own trouble.” Today’s manna of strength is enough for today’s trouble.',
						'What tomorrow-weight can you set down until its day?',
						'<strong>Practice:</strong> Write tomorrow’s worries on paper and close the notebook until evening prayer. <em>Jesus, give me today’s bread of strength only. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Encouragement in the body',
					'verse_ref' => '1 Thessalonians 5:14',
					'body'      => hwbl_plan_day_body(
						'Isolation worsens the valley. Paul tells the church how to treat the fainthearted.',
						'“Encourage the fainthearted, help the weak, be patient with them all.” You may receive that help—and on stronger days offer it. Asking is not failing.',
						'Who can patiently walk with you this week?',
						'<strong>Practice:</strong> Tell one safe person you are in a hard valley and need check-ins. <em>Lord, send patient encouragers—and help me receive them. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Weeping and joy',
					'verse_ref' => 'Psalm 30:5',
					'body'      => hwbl_plan_day_body(
						'Depression can make morning feel mythical. The psalmist holds both night and dawn.',
						'“Weeping may tarry for the night, but joy comes with the morning.” Timing belongs to God. Hope is not denial of tonight’s tears; it is confidence that morning is real.',
						'Can you let hope be as small as “joy may come” without forcing a smile?',
						'<strong>Practice:</strong> Light a candle or open a curtain as a sign of coming morning. <em>God of morning, hold me through this night until joy comes. Amen.</em>'
					),
				),
			),
		),
	);
}
