<?php
/**
 * Phase 8 rich plan definitions (Hidden Word study voice).
 *
 * @package Hidden_Word_Bible_Lessons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

/**
 * Phase 8 plan payloads for upsert.
 *
 * @return array<int, array<string, mixed>>
 */
function hwbl_plan_seed_phase8_definitions() {
	return array(
		array(
			'title'     => 'Anxiety / Worry (7 days)',
			'topic'     => 'anxiety',
			'shareable' => false,
			'excerpt'   => 'A seven-day walk through Scripture for anxious hearts—casting cares on God, receiving His peace, and practicing trust one day at a time.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Cast your cares',
					'verse_ref' => '1 Peter 5:7',
					'body'      => hwbl_plan_day_body(
						'Anxiety often arrives as a stack of “what ifs.” Before you try to manage every outcome, Scripture invites a different first move: place the weight in God’s hands.',
						'Peter writes to believers under pressure. “Cast” is active—throw the care onto the Lord—because He cares for you personally, not as a distant manager of the universe.',
						'Name one worry you have been carrying alone. Notice how it sits in your body and mind. God does not shame you for the feeling; He invites trust.',
						'<strong>Practice:</strong> Write the worry in one sentence, then pray it aloud to God. <em>Father, I cast this care on You because You care for me. Teach me to leave it with You today. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Pray instead of spiral',
					'verse_ref' => 'Philippians 4:6',
					'body'      => hwbl_plan_day_body(
						'When worry loops, our minds rehearse problems without an exit. Paul offers a path out of the spiral: prayer with thanksgiving.',
						'“Do not be anxious about anything” is not a scolding—it is an invitation into communion. Bring requests to God with honesty and gratitude for who He is.',
						'Thanksgiving does not deny hard things; it anchors you in God’s character while you ask. Anxiety shrinks when petition becomes relationship.',
						'<strong>Practice:</strong> List three requests and one thank-you. Pray them slowly. <em>Lord, I bring You my needs with thanksgiving. Guard my heart as I trust You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Peace that guards',
					'verse_ref' => 'Philippians 4:7',
					'body'      => hwbl_plan_day_body(
						'We often want peace as a calm feeling. Scripture describes something stronger: peace that stands guard like a sentry over heart and mind.',
						'The peace of God “surpasses understanding”—it is not always explainable by circumstances. It is given “in Christ Jesus,” rooted in union with Him.',
						'Where do you need a guard today—your racing thoughts, your restless heart, or both? Ask Christ to station His peace there.',
						'<strong>Practice:</strong> Breathe slowly and repeat: “Your peace guards me in Christ.” <em>Jesus, station Your peace over my mind and heart today. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Strength in weakness',
					'verse_ref' => '2 Corinthians 12:9',
					'body'      => hwbl_plan_day_body(
						'Anxiety can feel like weakness—and we hate looking weak. Paul learned that weakness is not the end of the story with God.',
						'God’s reply to Paul’s plea was grace: “My power is made perfect in weakness.” Sufficient grace means enough for this day, not a stockpile for every imagined tomorrow.',
						'Where have you been pretending to be strong? What if that thin place is where Christ’s power shows?',
						'<strong>Practice:</strong> Tell God one weakness without fixing language. <em>Lord, Your grace is enough for me here. Let Your power rest on my weakness. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Fear not—I am with you',
					'verse_ref' => 'Isaiah 41:10',
					'body'      => hwbl_plan_day_body(
						'Fear says, “You are alone with this.” God answers fear with presence and promise.',
						'Isaiah speaks God’s word to His people: do not fear, for I am with you; I will strengthen, help, and uphold you. The command rests on the character of God.',
						'Replace one fearful sentence in your mind with this truth: God is with you and upholds you.',
						'<strong>Practice:</strong> Write Isaiah 41:10 on a card and read it when fear rises. <em>God of Jacob, You are with me. Strengthen and uphold me today. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Come and rest',
					'verse_ref' => 'Matthew 11:28-30',
					'body'      => hwbl_plan_day_body(
						'Weariness is more than tired muscles—it is a soul overloaded. Jesus does not add another burden; He offers Himself.',
						'“Come to Me… and I will give you rest.” His yoke is kindly leadership, not crushing religion. Learn from Him: gentle and lowly in heart.',
						'What would resting in Jesus look like for the next hour—not escape, but trustful nearness?',
						'<strong>Practice:</strong> Sit quietly for three minutes and picture handing Jesus your load. <em>Jesus, I come to You. Teach me Your gentle rest. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Seek first the kingdom',
					'verse_ref' => 'Matthew 6:33',
					'body'      => hwbl_plan_day_body(
						'Worry multiplies when secondary things become ultimate. Jesus reorders life around the Father’s kingdom and righteousness.',
						'In the Sermon on the Mount, Jesus names food, drink, and clothing—real needs—then calls disciples to seek first God’s reign. Provision follows priority, not panic.',
						'What “first” has worry claimed in your week? Return the center to Christ and His kingdom.',
						'<strong>Practice:</strong> Choose one kingdom act today (encourage, give, reconcile, pray). <em>Father, I seek You first. Order my loves and quiet my fear. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'The Gospel (Romans Road)',
			'topic'     => 'gospel',
			'shareable' => true,
			'excerpt'   => 'A clear path through the gospel from Romans—sin, the cross, faith, and new life in Christ. Shareable for someone exploring faith.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'All have sinned',
					'verse_ref' => 'Romans 3:23',
					'body'      => hwbl_plan_day_body(
						'The gospel begins with honesty: we are not fine on our own. Paul levels every status and résumé before God’s glory.',
						'“All have sinned and fall short of the glory of God.” Sin is missing the mark of God’s holy beauty—not merely breaking rules, but failing to love God as He deserves.',
						'This is leveling grace: no one boasts, and no one is beyond need. Where have you excused what God calls short?',
						'<strong>Practice:</strong> Confess specifically, without comparing yourself to others. <em>Holy God, I have fallen short. I need Your mercy in Christ. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'The wage and the gift',
					'verse_ref' => 'Romans 6:23',
					'body'      => hwbl_plan_day_body(
						'Sin pays wages; God gives gifts. Those are different economies.',
						'“The wages of sin is death, but the free gift of God is eternal life in Christ Jesus our Lord.” Death is earned; life is received.',
						'Are you still trying to earn what Christ already purchased? The gift frees you from both despair and pride.',
						'<strong>Practice:</strong> Thank God aloud for one gift you cannot earn—life in Christ. <em>Father, thank You for the free gift of eternal life in Jesus. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Christ died for us',
					'verse_ref' => 'Romans 5:8',
					'body'      => hwbl_plan_day_body(
						'Human love often waits until we are lovable. God’s love moved toward us at our worst.',
						'“While we were still sinners, Christ died for us.” The cross is not God’s response to our improvement—it is His initiative of grace.',
						'Let this dismantle shame and self-salvation. You are loved at cost, not after cleanup.',
						'<strong>Practice:</strong> Sit with the phrase “while I was still a sinner.” <em>Jesus, thank You for dying for me when I had nothing to offer. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Confess and believe',
					'verse_ref' => 'Romans 10:9-10',
					'body'      => hwbl_plan_day_body(
						'The gospel is not vague spirituality. It is confession of Jesus as Lord and trust that God raised Him from the dead.',
						'Heart and mouth belong together: believing unto righteousness, confessing unto salvation. Faith receives what Christ has done.',
						'If you have never trusted Christ, today can be that day. If you have, renew the confession with clarity.',
						'<strong>Practice:</strong> Speak Romans 10:9 as your own. <em>Jesus, You are Lord. I believe God raised You from the dead. Save and keep me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'No condemnation',
					'verse_ref' => 'Romans 8:1',
					'body'      => hwbl_plan_day_body(
						'Accusation is loud after grace. Paul answers with a courtroom verdict for those in Christ.',
						'“There is therefore now no condemnation for those who are in Christ Jesus.” United to Christ, the guilty sentence is lifted.',
						'When shame replays old sins, answer with location: in Christ. Not in your performance—in Him.',
						'<strong>Practice:</strong> When accusation comes, say: “No condemnation in Christ.” <em>Spirit, help me live free from condemnation and walk in newness of life. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Faith Foundations',
			'topic'     => 'foundations',
			'shareable' => false,
			'excerpt'   => 'Five days of core Christian foundations—who God is, who Jesus is, Scripture, prayer, and the church.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Who is God?',
					'verse_ref' => 'Genesis 1:1',
					'body'      => hwbl_plan_day_body(
						'Every life is built on some vision of God—or a practical atheism that lives as if He is irrelevant. Scripture begins with God as Creator.',
						'“In the beginning, God created the heavens and the earth.” God is before all things, the source of life, beauty, and order—not a force we invent.',
						'If God made you, your life has meaning under His care. Worship starts with wonder.',
						'<strong>Practice:</strong> Thank God for three things He made that you noticed today. <em>Creator God, You are first and worthy. Help me live before Your face. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Who is Jesus?',
					'verse_ref' => 'John 1:14',
					'body'      => hwbl_plan_day_body(
						'Christianity is not mainly a philosophy—it is a Person. John says the Word became flesh.',
						'Jesus, the eternal Word, “dwelt among us… full of grace and truth.” God did not shout from a distance; He came near in Christ.',
						'Grace without truth is soft; truth without grace is harsh. Jesus holds both. How do you need Him near today?',
						'<strong>Practice:</strong> Read John 1:1–14 slowly. <em>Jesus, Word made flesh, show me Your glory in grace and truth. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'What is the Bible?',
					'verse_ref' => '2 Timothy 3:16-17',
					'body'      => hwbl_plan_day_body(
						'We need a trustworthy word in a noisy age. Paul tells Timothy what Scripture is and what it does.',
						'All Scripture is God-breathed and useful—for teaching, reproof, correction, and training—so that we may be equipped for every good work.',
						'Open the Bible expecting God to speak for your formation, not only for information.',
						'<strong>Practice:</strong> Read one chapter and write one takeaway. <em>Lord, breathe Your Word into my life and equip me for good work. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'What is prayer?',
					'verse_ref' => 'Philippians 4:6',
					'body'      => hwbl_plan_day_body(
						'Prayer is not a performance for God—it is conversation with the Father through the Son by the Spirit.',
						'Paul links prayer to anxiety’s undoing: by prayer and petition with thanksgiving, make requests known to God.',
						'You do not need fancy words. You need honesty, dependence, and trust.',
						'<strong>Practice:</strong> Pray for five minutes without a list—just presence. <em>Father, I am here. Hear me and form me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Why the church?',
					'verse_ref' => 'Hebrews 10:24-25',
					'body'      => hwbl_plan_day_body(
						'Faith is personal but never private. God forms a people who stir one another toward love and good works.',
						'Do not neglect meeting together—encourage one another, especially as the Day draws near. Isolation starves obedience; fellowship feeds it.',
						'Who could you encourage this week? Whose presence strengthens your walk?',
						'<strong>Practice:</strong> Reach out to one believer today. <em>Lord, plant me among Your people to give and receive encouragement. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Walking Through Grief (7 days)',
			'topic'     => 'grief',
			'shareable' => false,
			'excerpt'   => 'A gentle seven-day walk for those who are grieving—honest lament, nearness of God, and hope that holds.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'God is near the brokenhearted',
					'verse_ref' => 'Psalm 34:18',
					'body'      => hwbl_plan_day_body(
						'Grief can make God feel far. The psalm says the opposite of what pain suggests.',
						'“The Lord is near to the brokenhearted and saves the crushed in spirit.” Nearness is promise, not always feeling.',
						'You do not have to tidy your sorrow before approaching God. Brokenheartedness is a place He draws close.',
						'<strong>Practice:</strong> Tell God one honest sentence about your loss. <em>Lord, I am crushed—come near and save. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Jesus wept',
					'verse_ref' => 'John 11:35',
					'body'      => hwbl_plan_day_body(
						'Some cultures treat tears as weakness. Jesus treated tears as love.',
						'At Lazarus’s tomb, “Jesus wept.” The One who would raise the dead still entered the sorrow of friends.',
						'Your tears are not a failure of faith. They can be faith’s honesty before a Savior who cries with us.',
						'<strong>Practice:</strong> Allow yourself to feel without apology for ten minutes with God. <em>Jesus, You know tears. Sit with me in mine. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Comfort received and shared',
					'verse_ref' => '2 Corinthians 1:3-4',
					'body'      => hwbl_plan_day_body(
						'God’s comfort is not a dead end—it becomes a ministry.',
						'The Father of mercies comforts us in affliction so we can comfort others with the comfort we received.',
						'You may not be ready to help anyone yet. Still, receive comfort today; sharing may come later.',
						'<strong>Practice:</strong> Name one comfort God has given—even small. <em>Father of mercies, comfort me, and one day use my story for another. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Hope that holds',
					'verse_ref' => '1 Thessalonians 4:13-14',
					'body'      => hwbl_plan_day_body(
						'Christian hope does not erase grief; it frames it.',
						'We do not grieve as those without hope, because Jesus died and rose, and those who sleep in Him will rise.',
						'Hope is not denial. It is confidence that death does not have the last word in Christ.',
						'<strong>Practice:</strong> Thank God for the resurrection once, slowly. <em>Risen Lord, hold my hope when my heart is heavy. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Cast the burden again',
					'verse_ref' => '1 Peter 5:7',
					'body'      => hwbl_plan_day_body(
						'Grief returns in waves. Casting cares is not a one-time event.',
						'Peter’s call to cast anxiety on God because He cares is needed again and again in mourning.',
						'What fresh weight rose today? Hand it over once more.',
						'<strong>Practice:</strong> Cast today’s wave of sorrow in prayer. <em>Caring Father, I cast this grief on You again. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Mercies for this morning',
					'verse_ref' => 'Lamentations 3:22-23',
					'body'      => hwbl_plan_day_body(
						'In a book of lament, hope breaks through like dawn.',
						'The Lord’s steadfast love never ceases; His mercies are new every morning. Great is His faithfulness—daily, not merely someday.',
						'You do not need strength for every future grief-day—only mercy for this one.',
						'<strong>Practice:</strong> Ask for today’s mercy by name. <em>Faithful God, give me new mercy for this morning’s sorrow. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Strength enough for today',
					'verse_ref' => 'Psalm 73:26',
					'body'      => hwbl_plan_day_body(
						'Flesh and heart can fail. Faith names God as portion anyway.',
						'“My flesh and my heart may fail, but God is the strength of my heart and my portion forever.”',
						'When energy is gone, God Himself remains your share and strength.',
						'<strong>Practice:</strong> Rest without guilt for a short time today. <em>God, be the strength of my heart when I have none. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Prayer That Breathes',
			'topic'     => 'prayer',
			'shareable' => false,
			'excerpt'   => 'Seven days to deepen prayer—honest talk with God, listening Scripture, persistence, and a quieter heart.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Come as you are',
					'verse_ref' => 'Psalm 62:8',
					'body'      => hwbl_plan_day_body(
						'Many of us edit ourselves before we pray. The psalms teach unedited trust.',
						'“Trust in Him at all times… pour out your heart before Him.” God is a refuge for poured-out hearts, not polished speeches.',
						'What have you been holding back from God—anger, fear, desire, doubt?',
						'<strong>Practice:</strong> Pour out one unedited paragraph to God. <em>Refuge God, here is my heart without filters. Receive me. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Our Father',
					'verse_ref' => 'Matthew 6:9',
					'body'      => hwbl_plan_day_body(
						'Jesus does not begin prayer with technique—He begins with relationship.',
						'“Our Father in heaven, hallowed be Your name.” Intimacy (“Father”) and reverence (“hallowed”) belong together.',
						'If “Father” is hard because of earthly wounds, ask Jesus to teach you the Father’s heart.',
						'<strong>Practice:</strong> Pray the Lord’s Prayer slowly, pausing after “Father.” <em>Father in heaven, teach me to know You as holy and near. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Ask, seek, knock',
					'verse_ref' => 'Matthew 7:7-8',
					'body'      => hwbl_plan_day_body(
						'Some stop praying because answers seem delayed. Jesus commands persistence.',
						'Ask, seek, knock—continuous verbs. The Father gives good gifts; persistence is faith’s posture, not magic.',
						'Where have you quit asking? Resume with humble boldness.',
						'<strong>Practice:</strong> Bring one request three times today. <em>Father, I ask, seek, and knock—form my desires as You answer. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Pray the Word back',
					'verse_ref' => 'Psalm 119:18',
					'body'      => hwbl_plan_day_body(
						'Prayer and Scripture are meant to breathe together. We speak; God has spoken.',
						'“Open my eyes, that I may behold wondrous things out of Your law.” Prayer prepares us to see.',
						'Reading without prayer becomes study alone; prayer without Scripture can drift. Join them.',
						'<strong>Practice:</strong> Read a short psalm and turn one line into prayer. <em>Lord, open my eyes to wonder in Your Word. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Watch and pray',
					'verse_ref' => 'Colossians 4:2',
					'body'      => hwbl_plan_day_body(
						'Prayer is not only crisis language—it is a watchman’s habit.',
						'“Continue steadfastly in prayer, being watchful in it with thanksgiving.” Steadfastness and alert gratitude keep prayer alive.',
						'Set a small daily watch—morning or night—rather than waiting for emergencies.',
						'<strong>Practice:</strong> Choose a fixed 5-minute prayer slot for a week. <em>Lord, make me steadfast and watchful with thanksgiving. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'When you do not know what to say',
					'verse_ref' => 'Romans 8:26',
					'body'      => hwbl_plan_day_body(
						'Sometimes grief or confusion empties our vocabulary. The Spirit does not abandon those moments.',
						'The Spirit helps us in our weakness and intercedes with groanings too deep for words.',
						'Silence before God can still be prayer when the Spirit carries what you cannot say.',
						'<strong>Practice:</strong> Sit in quiet for three minutes and trust the Spirit’s help. <em>Holy Spirit, intercede where my words fail. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Pray for others',
					'verse_ref' => 'Ephesians 6:18',
					'body'      => hwbl_plan_day_body(
						'Mature prayer turns outward. Intercession is love with its eyes open.',
						'Pray at all times in the Spirit… with all prayer… making supplication for all the saints.',
						'Who needs your prayers more than your opinions today?',
						'<strong>Practice:</strong> Pray by name for three people. <em>Lord, teach me to love others by praying for them. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Who You Are in Christ',
			'topic'     => 'identity',
			'shareable' => false,
			'excerpt'   => 'Seven days on identity—chosen, adopted, forgiven, and secure in Christ when labels and performance shout otherwise.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'A new creation',
					'verse_ref' => '2 Corinthians 5:17',
					'body'      => hwbl_plan_day_body(
						'Culture offers identity as self-invention. The gospel offers new creation.',
						'If anyone is in Christ, he is a new creation—the old has passed away; the new has come.',
						'Your deepest self is not your worst chapter. Union with Christ remakes you.',
						'<strong>Practice:</strong> Replace one false label with “new creation in Christ.” <em>Jesus, I receive new creation identity in You. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Chosen and adopted',
					'verse_ref' => 'Ephesians 1:4-5',
					'body'      => hwbl_plan_day_body(
						'Rejection wounds teach us we are optional. Paul sings a different song.',
						'God chose us in Christ and predestined us for adoption as sons through Jesus—according to the purpose of His will.',
						'Adoption means belonging by legal love, not temporary mood. You are wanted.',
						'<strong>Practice:</strong> Thank God that you are adopted, not auditioning. <em>Father, thank You for choosing and adopting me in Christ. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Forgiven and free',
					'verse_ref' => 'Ephesians 1:7',
					'body'      => hwbl_plan_day_body(
						'Guilt can become an identity. Redemption gives a better name.',
						'In Christ we have redemption through His blood, the forgiveness of our trespasses, according to the riches of His grace.',
						'Forgiveness is not cheap; it cost blood. Therefore it is secure.',
						'<strong>Practice:</strong> Confess one sin and receive forgiveness as fact. <em>Lord, I receive redemption and forgiveness by Your rich grace. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'God’s workmanship',
					'verse_ref' => 'Ephesians 2:10',
					'body'      => hwbl_plan_day_body(
						'You are not a cosmic accident or a productivity machine.',
						'We are His workmanship, created in Christ Jesus for good works prepared beforehand.',
						'Identity fuels mission: you work from belovedness, not for it.',
						'<strong>Practice:</strong> Do one good work as worship, not self-worth. <em>Creator God, I am Your workmanship—use me today. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Children of God',
					'verse_ref' => '1 John 3:1',
					'body'      => hwbl_plan_day_body(
						'See what kind of love the Father has given—that we should be called children of God.',
						'John wants amazement: “And so we are.” Child-status is present reality for believers.',
						'Live today as a loved child, not an orphan scrambling for approval.',
						'<strong>Practice:</strong> Begin prayer with “Father…” and linger. <em>Father, Your love makes me Your child. Let me live like it. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Hidden with Christ',
					'verse_ref' => 'Colossians 3:3',
					'body'      => hwbl_plan_day_body(
						'Public opinion is unstable. Your life is secured elsewhere.',
						'You have died, and your life is hidden with Christ in God—safe, unseen by the world’s final verdict.',
						'When criticism or praise swells, return to hiddenness in Christ.',
						'<strong>Practice:</strong> Refuse one identity based only on others’ opinions. <em>Christ, my life is hidden with You—be my security. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Ambassadors for Christ',
					'verse_ref' => '2 Corinthians 5:20',
					'body'      => hwbl_plan_day_body(
						'Identity becomes vocation. We represent the King.',
						'We are ambassadors for Christ—God making His appeal through us: be reconciled to God.',
						'You carry a message bigger than self-expression: reconciliation.',
						'<strong>Practice:</strong> Share one kindness or gospel word as an ambassador. <em>Lord, speak Your appeal through my life today. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Forgiven to Forgive',
			'topic'     => 'forgiveness',
			'shareable' => false,
			'excerpt'   => 'Seven days on receiving God’s forgiveness and extending it—truth-telling, release, and reconciled hearts.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Blessed are the forgiven',
					'verse_ref' => 'Psalm 32:1-2',
					'body'      => hwbl_plan_day_body(
						'Unconfessed sin weighs the bones. David knew the misery—and the mercy.',
						'Blessed is the one whose transgression is forgiven, whose sin is covered, against whom the Lord counts no iniquity.',
						'Forgiveness is blessing, not mere relief. God covers and does not count against you in Christ.',
						'<strong>Practice:</strong> Confess without minimization. <em>Lord, cover my sin and teach me the joy of the forgiven. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'As far as east from west',
					'verse_ref' => 'Psalm 103:12',
					'body'      => hwbl_plan_day_body(
						'We keep retrieving what God has removed.',
						'As far as the east is from the west, so far does He remove our transgressions from us—immeasurable distance.',
						'Stop rehearsing canceled debts. Agree with God’s removal.',
						'<strong>Practice:</strong> When an old guilt returns, say “removed.” <em>Merciful God, I receive the distance You put between me and my sin. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Forgive as you have been forgiven',
					'verse_ref' => 'Ephesians 4:32',
					'body'      => hwbl_plan_day_body(
						'Horizontal forgiveness flows from vertical grace.',
						'Be kind… forgiving one another, as God in Christ forgave you. The measure is God’s forgiveness in Christ.',
						'Forgiveness is not pretending harm was small. It is releasing revenge to God because you have been released.',
						'<strong>Practice:</strong> Pray blessing for someone who hurt you—start small if needed. <em>God who forgave me in Christ, help me forgive. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Seventy times seven',
					'verse_ref' => 'Matthew 18:21-22',
					'body'      => hwbl_plan_day_body(
						'Peter wanted a limit. Jesus gave a lifestyle of mercy.',
						'Not seven times, but seventy-seven times—forgiveness without a tight quota, matching the kingdom’s generosity.',
						'Repeated wounds are hard. Still, discipleship refuses a scorekeeping heart.',
						'<strong>Practice:</strong> Ask God for fresh mercy for a repeat offense. <em>Jesus, expand my mercy beyond my counting. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Truth and reconciliation',
					'verse_ref' => 'Matthew 18:15',
					'body'      => hwbl_plan_day_body(
						'Forgiveness and honesty are friends. Jesus teaches going to your brother.',
						'If your brother sins against you, go and tell him his fault between you and him alone. Love seeks restoration, not gossip.',
						'Where do you need a careful conversation instead of silent resentment?',
						'<strong>Practice:</strong> Plan one truthful, gentle step toward peace. <em>Lord, give me courage and gentleness to seek reconciliation. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Leave vengeance to God',
					'verse_ref' => 'Romans 12:19',
					'body'      => hwbl_plan_day_body(
						'Bitterness feels like control. It is actually captivity.',
						'Never avenge yourselves… “Vengeance is Mine, I will repay, says the Lord.” Trust God’s justice.',
						'Forgiveness hands the gavel back to God. You are free to do good.',
						'<strong>Practice:</strong> Verbally release one debt to God’s justice. <em>Lord, vengeance is Yours. I lay down my claim. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Be reconciled to God',
					'verse_ref' => '2 Corinthians 5:18-19',
					'body'      => hwbl_plan_day_body(
						'Our horizontal peace rests on God’s reconciling work.',
						'God reconciled us to Himself through Christ and gave us the ministry of reconciliation—not counting trespasses against us.',
						'Live as someone already reconciled, then become a reconciler.',
						'<strong>Practice:</strong> Thank God for reconciliation, then encourage one strained relationship with a kind act. <em>God of reconciliation, make me an agent of Your peace. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Faith at Work / Everyday Calling',
			'topic'     => 'purpose',
			'shareable' => false,
			'excerpt'   => 'Seven days on vocation—work, study, and daily tasks as worship under Christ’s lordship.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Work as worship',
					'verse_ref' => 'Colossians 3:23-24',
					'body'      => hwbl_plan_day_body(
						'Monday can feel disconnected from Sunday. Paul stitches them together.',
						'Whatever you do, work heartily as for the Lord… knowing you will receive the inheritance from the Lord as your reward. You are serving Christ.',
						'Your desk, tools, classroom, or kitchen can become an altar of faithfulness.',
						'<strong>Practice:</strong> Begin one task saying, “This is for You, Lord.” <em>Christ, I work for You today—receive my labor. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Created for good works',
					'verse_ref' => 'Ephesians 2:10',
					'body'      => hwbl_plan_day_body(
						'Purpose anxiety asks, “What should I do with my life?” Scripture answers with prepared works.',
						'We are His workmanship, created in Christ Jesus for good works God prepared beforehand that we should walk in them.',
						'Look for today’s prepared good—not only a five-year plan.',
						'<strong>Practice:</strong> Do the next faithful thing in front of you. <em>Lord, help me walk in the good works You prepared. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Salt and light at work',
					'verse_ref' => 'Matthew 5:16',
					'body'      => hwbl_plan_day_body(
						'Witness is not only a program—it is visible goodness.',
						'Let your light shine before others, so they may see your good works and give glory to your Father in heaven.',
						'Excellence, honesty, and kindness preach in workplaces allergic to sermons.',
						'<strong>Practice:</strong> Choose one act of integrity or encouragement at work/school. <em>Father, let my light point to Your glory, not mine. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Diligence without idols',
					'verse_ref' => 'Proverbs 16:3',
					'body'      => hwbl_plan_day_body(
						'Ambition can become a master. Wisdom commits work to the Lord.',
						'Commit your work to the Lord, and your plans will be established—not as a formula for success, but as surrendered planning.',
						'Hold goals openly before God; refuse to worship outcomes.',
						'<strong>Practice:</strong> Dedicate a current project to God in prayer. <em>Lord, I commit this work to You—establish what pleases You. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Honest scales',
					'verse_ref' => 'Proverbs 11:1',
					'body'      => hwbl_plan_day_body(
						'Kingdom people tell the truth with numbers, emails, and invoices.',
						'A false balance is an abomination to the Lord, but a just weight is His delight. God cares about fair dealing.',
						'Where are you tempted to shade the truth for advantage?',
						'<strong>Practice:</strong> Correct one small dishonest habit. <em>Lord of justice, make my dealings a delight to You. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Rest as faith',
					'verse_ref' => 'Exodus 20:8-10',
					'body'      => hwbl_plan_day_body(
						'Overwork can be unbelief wearing a badge. Sabbath teaches trust.',
						'Remember the Sabbath day… six days you shall labor… but the seventh is a Sabbath to the Lord. Rest is holy, not lazy.',
						'Stopping says God runs the world while you sleep.',
						'<strong>Practice:</strong> Schedule a block of non-productive rest this week. <em>Lord of the Sabbath, teach me to rest as trust. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Whatever you do—glory',
					'verse_ref' => '1 Corinthians 10:31',
					'body'      => hwbl_plan_day_body(
						'Calling is wider than career title. It is glory-aimed living.',
						'Whether you eat or drink, or whatever you do, do all to the glory of God.',
						'Ordinary moments become sacred when aimed at God’s honor.',
						'<strong>Practice:</strong> Offer a meal or commute as glory to God. <em>God, let whatever I do today honor You. Amen.</em>'
					),
				),
			),
		),
		array(
			'title'     => 'Following Jesus Daily',
			'topic'     => 'discipleship',
			'shareable' => false,
			'excerpt'   => 'Seven days of ordinary discipleship—deny self, take up the cross, abide, obey, and love as Jesus loved.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Come, follow Me',
					'verse_ref' => 'Mark 1:17',
					'body'      => hwbl_plan_day_body(
						'Discipleship begins with a call, not a curriculum.',
						'Jesus said, “Follow Me, and I will make you become fishers of men.” Following precedes usefulness; He makes us.',
						'Where is Jesus inviting fresh obedience today?',
						'<strong>Practice:</strong> Say yes to one clear next step. <em>Jesus, I follow You—make me what You will. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Deny yourself',
					'verse_ref' => 'Luke 9:23',
					'body'      => hwbl_plan_day_body(
						'Self is a loud god. Jesus calls a better allegiance.',
						'If anyone would come after Me, let him deny himself and take up his cross daily and follow Me.',
						'Daily cross-bearing is small deaths to pride, comfort, and control for Jesus’ sake.',
						'<strong>Practice:</strong> Deny one selfish preference today for love’s sake. <em>Lord, I take up my cross and follow. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Abide in the Vine',
					'verse_ref' => 'John 15:5',
					'body'      => hwbl_plan_day_body(
						'Fruitfulness is not frantic effort—it is connection.',
						'I am the vine; you are the branches… apart from Me you can do nothing. Abiding is dependent union.',
						'Where have you been striving without abiding?',
						'<strong>Practice:</strong> Spend ten undistracted minutes with Jesus. <em>True Vine, I abide—bear Your fruit through me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Obey My words',
					'verse_ref' => 'John 14:15',
					'body'      => hwbl_plan_day_body(
						'Love talks—and love obeys.',
						'If you love Me, you will keep My commandments. Obedience is love’s language, not legalism’s ladder.',
						'Which command of Jesus have you delayed?',
						'<strong>Practice:</strong> Obey one known command today. <em>Jesus, I love You—help me keep Your word. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Love one another',
					'verse_ref' => 'John 13:34-35',
					'body'      => hwbl_plan_day_body(
						'The world recognizes disciples by love, not slogans.',
						'A new commandment… love one another: just as I have loved you… By this all people will know you are My disciples.',
						'Jesus-shaped love is costly and concrete.',
						'<strong>Practice:</strong> Serve someone inconveniently. <em>Lord, love through me as You have loved me. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Make disciples',
					'verse_ref' => 'Matthew 28:19-20',
					'body'      => hwbl_plan_day_body(
						'Following Jesus includes helping others follow Him.',
						'Go… make disciples of all nations, baptizing… teaching them to observe all that I commanded you. And He is with you always.',
						'You do not go alone. Presence fuels mission.',
						'<strong>Practice:</strong> Encourage one person’s faith with Scripture or prayer. <em>Risen Lord, use me to make disciples—You are with me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Finish by faith',
					'verse_ref' => 'Hebrews 12:1-2',
					'body'      => hwbl_plan_day_body(
						'Discipleship is a race of endurance, not a sprint of emotion.',
						'Lay aside every weight and sin… run with endurance… looking to Jesus, the founder and perfecter of our faith.',
						'Fix your eyes: Jesus started and finishes your faith.',
						'<strong>Practice:</strong> Name one weight to lay aside this week. <em>Jesus, I look to You—perfect my faith as I run. Amen.</em>'
					),
				),
			),
		),
	);
}
