<?php
/**
 * Phase 10 plan definitions: upgrade thin samples + character/parenting plans.
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
 * Phase 10 plan payloads for upsert.
 *
 * @return array<int, array<string, mixed>>
 */
function hwbl_plan_seed_phase10_definitions() {
	return array(
		array(
			'title'     => 'Marriage in Christ (7 days)',
			'topic'     => 'marriage',
			'shareable' => false,
			'excerpt'   => 'Seven days for couples growing in Christ—covenant love, mutual honor, shared prayer, and everyday faithfulness.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'One flesh, one Lord',
					'verse_ref' => 'Genesis 2:24',
					'body'      => hwbl_plan_day_body(
						'Marriage can drift into roommate mode—shared calendar, separate hearts. Scripture begins with leaving, cleaving, and becoming one.',
						'“Therefore a man shall leave his father and his mother and hold fast to his wife, and they shall become one flesh.” Oneness is God’s design: loyalty, intimacy, and a new primary household under Him.',
						'Where have you been “leaving” poorly—or holding fast weakly?',
						'<strong>Practice:</strong> Tell your spouse one way you want to hold fast this week. <em>Lord, form our oneness under Your design. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Love that acts',
					'verse_ref' => '1 Corinthians 13:4-7',
					'body'      => hwbl_plan_day_body(
						'Feelings rise and fall; covenant love chooses behavior. Paul describes love in verbs.',
						'Love is patient and kind… it does not insist on its own way… it bears, believes, hopes, and endures. This is Christ’s character applied at home.',
						'Which verb of love is hardest for you right now—patience, kindness, or not insisting on your way?',
						'<strong>Practice:</strong> Do one kind act today with no speech attached. <em>Jesus, love through me when I feel empty. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Submit to one another',
					'verse_ref' => 'Ephesians 5:21',
					'body'      => hwbl_plan_day_body(
						'Power struggles poison marriages. Paul frames the household with mutual submission before specific callings.',
						'“Submitting to one another out of reverence for Christ.” Reverence for Jesus—not fear of a spouse—shapes how we yield, serve, and lead without domination.',
						'Where can you yield a preference this week out of reverence for Christ?',
						'<strong>Practice:</strong> Ask your spouse, “How can I serve you this week?” and do the first answer. <em>Christ, teach us mutual submission in reverence for You. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Speak life',
					'verse_ref' => 'Ephesians 4:29',
					'body'      => hwbl_plan_day_body(
						'Home conversations can become scorekeeping. Scripture sets a filter for speech.',
						'“Let no corrupting talk come out of your mouths, but only such as is good for building up… that it may give grace to those who hear.” Grace-giving words rebuild trust.',
						'What corrupting pattern—sarcasm, contempt, silent treatment—needs replacing?',
						'<strong>Practice:</strong> Speak one specific building-up sentence to your spouse today. <em>Lord, put grace on my tongue at home. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Pray together',
					'verse_ref' => 'Matthew 18:19-20',
					'body'      => hwbl_plan_day_body(
						'Many couples share logistics but not prayer. Jesus promises His presence where two agree.',
						'“If two of you agree on earth about anything they ask… For where two or three are gathered in my name, there am I among them.” Shared prayer invites Christ into the center of the marriage.',
						'What keeps you from praying together—awkwardness, busyness, or unresolved hurt?',
						'<strong>Practice:</strong> Pray aloud together for two minutes tonight—simple and honest. <em>Jesus, be among us as we ask together. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Forgive as you were forgiven',
					'verse_ref' => 'Colossians 3:13',
					'body'      => hwbl_plan_day_body(
						'Small offenses stack until the house feels heavy. Paul roots forgiveness in the Lord’s forgiveness of us.',
						'“Bearing with one another and, if one has a complaint… forgiving each other; as the Lord has forgiven you, so you also must forgive.” Bearing and forgiving are ongoing marriage work.',
						'What complaint are you nursing that needs confession or release?',
						'<strong>Practice:</strong> Confess one fault without “but,” or release one kept score. <em>Lord, as You forgave me, help me forgive here. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Build the house',
					'verse_ref' => 'Psalm 127:1',
					'body'      => hwbl_plan_day_body(
						'Effort without God exhausts a marriage. The psalmist relocates the builder.',
						'“Unless the Lord builds the house, those who build it labor in vain.” Invite God to build—through Word, prayer, repentance, and shared mission—not only through your strategies.',
						'Where have you been building in vain—alone, anxious, or proud?',
						'<strong>Practice:</strong> Dedicate your marriage to the Lord aloud and ask Him to build. <em>Lord, build our house; we do not want vain labor. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'New Believer Foundations (7 days)',
			'topic'     => 'new-believer',
			'shareable' => false,
			'excerpt'   => 'Seven first steps for new followers of Jesus—gospel clarity, Scripture, prayer, church, baptismal identity, obedience, and hope.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Saved by grace',
					'verse_ref' => 'Ephesians 2:8-9',
					'body'      => hwbl_plan_day_body(
						'New faith often comes with pressure to “perform” for God. Paul anchors salvation in gift, not grit.',
						'“For by grace you have been saved through faith. And this is not your own doing; it is the gift of God, not a result of works.” You belong because Christ saved you—not because you cleaned yourself up first.',
						'Are you resting in grace, or still trying to earn a welcome?',
						'<strong>Practice:</strong> Thank God out loud that salvation is His gift. <em>Father, I receive Your grace through faith in Jesus. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Christ at the center',
					'verse_ref' => 'Colossians 1:13-14',
					'body'      => hwbl_plan_day_body(
						'Following Jesus is more than adding religion. It is a transfer of kingdoms.',
						'God “has delivered us from the domain of darkness and transferred us to the kingdom of his beloved Son, in whom we have redemption, the forgiveness of sins.” Your new address is Christ’s kingdom.',
						'What old “darkness” habit still feels like home?',
						'<strong>Practice:</strong> Name one old pattern and ask Jesus to rule that area today. <em>Beloved Son, I belong to Your kingdom—lead me. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Feed on the Word',
					'verse_ref' => '1 Peter 2:2',
					'body'      => hwbl_plan_day_body(
						'Newborns need milk. New believers need Scripture the same way.',
						'“Like newborn infants, long for the pure spiritual milk, that by it you may grow up into salvation.” Growth is normal; starvation is not. Start simple and steady.',
						'When will you open the Bible tomorrow—attach it to a daily habit?',
						'<strong>Practice:</strong> Read John 1 slowly (or ten verses) and underline one phrase. <em>Lord, give me longing for Your Word. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Talk with God',
					'verse_ref' => 'Philippians 4:6',
					'body'      => hwbl_plan_day_body(
						'Prayer can feel formal when you are new. Paul invites honest asking with thanksgiving.',
						'“Do not be anxious about anything, but in everything by prayer and supplication with thanksgiving let your requests be made known to God.” Prayer is conversation with a Father who listens.',
						'What have you been carrying alone that you could tell God today?',
						'<strong>Practice:</strong> Pray for two minutes: thanks, then one request, then silence. <em>Father, I make my requests known to You. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Belong to the body',
					'verse_ref' => 'Hebrews 10:24-25',
					'body'      => hwbl_plan_day_body(
						'Solo Christianity withers. God places believers in a people.',
						'“Let us consider how to stir up one another to love and good works, not neglecting to meet together.” Church is not a performance venue—it is a family that stirs faith.',
						'What step toward a local church gathering will you take this week?',
						'<strong>Practice:</strong> Attend a service or small group, or message a church about visiting. <em>Lord, plant me among Your people. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Obey what you know',
					'verse_ref' => 'John 14:15',
					'body'      => hwbl_plan_day_body(
						'Information without obedience stalls growth. Jesus links love and obedience.',
						'“If you love me, you will keep my commandments.” Start with the clear next step you already know—honesty, reconciliation, baptism conversation, leaving a sin.',
						'What clear command of Jesus have you delayed?',
						'<strong>Practice:</strong> Take one obedient step today, however small. <em>Jesus, I love You—help me keep Your word. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Hold the hope',
					'verse_ref' => '1 Peter 1:3',
					'body'      => hwbl_plan_day_body(
						'New life does not erase hard days. Peter blesses God for living hope.',
						'“He has caused us to be born again to a living hope through the resurrection of Jesus Christ from the dead.” Your future is tied to a risen Lord, not to your perfect week.',
						'Where do you need living hope more than a quick fix?',
						'<strong>Practice:</strong> Thank Jesus for resurrection hope in one hard area. <em>Risen Lord, anchor me in living hope. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Advent Walk (5 days)',
			'topic'     => 'advent',
			'shareable' => false,
			'excerpt'   => 'A five-day Advent walk: hope, peace, joy, love, and the Incarnation—waiting for Christ with Scripture and practice.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Hope',
					'verse_ref' => 'Isaiah 9:2',
					'body'      => hwbl_plan_day_body(
						'Advent begins in the dark—longing for light we cannot manufacture.',
						'“The people who walked in darkness have seen a great light.” Hope is not denial of night; it is confidence that God’s light has dawned in Christ.',
						'Where are you walking in darkness and needing light?',
						'<strong>Practice:</strong> Light a candle (or lamp) and pray Isaiah 9:2. <em>Lord, shine Your great light on our darkness. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Peace',
					'verse_ref' => 'Isaiah 9:6',
					'body'      => hwbl_plan_day_body(
						'Seasonal busyness rarely feels peaceful. Isaiah names the child as Prince of Peace.',
						'“For to us a child is born… and his name shall be called… Prince of Peace.” Peace is a Person before it is a mood—Christ ruling hearts and reconciling us to God.',
						'What conflict or unrest needs the Prince of Peace today?',
						'<strong>Practice:</strong> Pause for one quiet minute and name Him “Prince of Peace.” <em>Jesus, rule my unrest with Your peace. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Joy',
					'verse_ref' => 'Luke 2:10-11',
					'body'      => hwbl_plan_day_body(
						'Joy can feel forced in December. The angel announces good news of great joy for all people.',
						'“Fear not, for behold, I bring you good news of great joy… For unto you is born this day… a Savior, who is Christ the Lord.” Joy rests on a birth—a Savior given.',
						'Have you been chasing festive feeling more than Savior joy?',
						'<strong>Practice:</strong> Tell someone one sentence of gospel joy today. <em>Christ the Lord, renew great joy in me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Love',
					'verse_ref' => '1 John 4:9-10',
					'body'      => hwbl_plan_day_body(
						'Love gets reduced to gifts and sentiment. John defines love by God’s sending.',
						'“In this the love of God was made manifest… He loved us and sent his Son to be the propitiation for our sins.” Advent love is cruciform before it is cozy.',
						'Whom can you love with a costly, concrete act this week?',
						'<strong>Practice:</strong> Do one hidden act of love for someone who cannot repay you. <em>Father, teach me Your sending love. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'The Word became flesh',
					'verse_ref' => 'John 1:14',
					'body'      => hwbl_plan_day_body(
						'Christmas can stay sentimental. John presses the mystery: God with us in flesh.',
						'“And the Word became flesh and dwelt among us, and we have seen his glory…” The eternal Son entered our neighborhood. Worship fits better than mere nostalgia.',
						'How will you respond to the Word-made-flesh—worship, obedience, witness?',
						'<strong>Practice:</strong> Read John 1:14 aloud twice and sit in silent thanks. <em>Word made flesh, I worship You. Dwell with me. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Lent Walk (5 days)',
			'topic'     => 'lent',
			'shareable' => false,
			'excerpt'   => 'A five-day Lent walk of repentance, humility, fasting of the heart, the cross, and resurrection hope.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Return to Me',
					'verse_ref' => 'Joel 2:12-13',
					'body'      => hwbl_plan_day_body(
						'Lent invites honesty without despair. Joel calls for return with the whole heart.',
						'“Return to me with all your heart… Rend your hearts and not your garments.” God wants inward turning more than religious display—and He is gracious and merciful.',
						'What outer religion have you used to avoid inner return?',
						'<strong>Practice:</strong> Confess one specific sin without excuse. <em>Merciful God, I return with my whole heart. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Humble yourselves',
					'verse_ref' => '1 Peter 5:6',
					'body'      => hwbl_plan_day_body(
						'Pride resists Lent. Peter ties humility to God’s timing.',
						'“Humble yourselves, therefore, under the mighty hand of God so that at the proper time he may exalt you.” Humility is trust under God’s hand—not self-hatred.',
						'Where is God asking you to stop self-exalting?',
						'<strong>Practice:</strong> Yield one status-seeking habit today (posting, arguing, comparing). <em>Mighty God, I humble myself under Your hand. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Hunger for God',
					'verse_ref' => 'Matthew 4:4',
					'body'      => hwbl_plan_day_body(
						'Fasting empties a hand so it can open to God. Jesus answers temptation with Scripture.',
						'“Man shall not live by bread alone, but by every word that comes from the mouth of God.” Whether you fast food or another comfort, the point is deeper hunger for His Word.',
						'What lesser hunger has been ruling you?',
						'<strong>Practice:</strong> Skip one comfort and read a psalm in its place. <em>Lord, feed me by Your word more than bread. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Fix your eyes on the cross',
					'verse_ref' => 'Isaiah 53:5',
					'body'      => hwbl_plan_day_body(
						'Self-improvement is not the center of Lent—the cross is. Isaiah describes the Servant wounded for us.',
						'“He was pierced for our transgressions… and with his wounds we are healed.” Our repentance runs to a finished sacrifice, not to self-repair alone.',
						'Have you been staring at your failure more than His wounds?',
						'<strong>Practice:</strong> Thank Jesus specifically for taking your sin at the cross. <em>Suffering Servant, by Your wounds heal me. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Toward resurrection',
					'verse_ref' => 'Romans 6:4',
					'body'      => hwbl_plan_day_body(
						'Lent is not endless gloom. Paul joins burial with newness of life.',
						'“We were buried… with him by baptism into death, in order that… we too might walk in newness of life.” Repentance aims at resurrection life now and forever.',
						'What “newness of life” step will you walk this week?',
						'<strong>Practice:</strong> Choose one resurrection habit—worship, reconciliation, or generosity. <em>Risen Lord, help me walk in newness of life. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Kids Bible Stories (7 days)',
			'topic'     => 'kids',
			'shareable' => false,
			'excerpt'   => 'Seven short Bible stories for kids (about ages 6–10)—creation, Noah, Abraham, Moses, David, Jesus’ love, and the empty tomb—with simple practice.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'God made everything',
					'verse_ref' => 'Genesis 1:1',
					'body'      => hwbl_plan_day_body(
						'Look outside—trees, sky, animals, you. The Bible says God made it all.',
						'“In the beginning, God created the heavens and the earth.” God is the Maker. Nothing is an accident to Him.',
						'What is one thing God made that you are glad about today?',
						'<strong>Practice:</strong> Draw or name three things God made and thank Him. <em>God, thank You for making the world—and me. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'God keeps His promise',
					'verse_ref' => 'Genesis 9:13',
					'body'      => hwbl_plan_day_body(
						'Noah’s story shows a big storm—and a big promise after.',
						'God set the rainbow as a sign of His covenant. He keeps His promises even when people forget.',
						'When have you seen a rainbow—or needed to remember God keeps His word?',
						'<strong>Practice:</strong> Tell God one promise from the Bible you want to remember. <em>Lord, thank You that You keep Your promises. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'God calls people',
					'verse_ref' => 'Genesis 12:1-2',
					'body'      => hwbl_plan_day_body(
						'Abraham left home because God called him. Following God sometimes means a new step.',
						'God said, “Go… and I will bless you.” God leads His people and blesses them to be a blessing.',
						'What is one brave step of trust you could take with God’s help?',
						'<strong>Practice:</strong> Ask God to help you obey in one small way today. <em>God, help me follow You like Abraham. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'God rescues',
					'verse_ref' => 'Exodus 14:13',
					'body'      => hwbl_plan_day_body(
						'Israel was stuck between the sea and an army. God made a way.',
						'Moses said, “Fear not, stand firm, and see the salvation of the Lord.” God is a Rescuer—biggest of all through Jesus.',
						'What scary thing do you want to trust God with?',
						'<strong>Practice:</strong> Say “God can rescue” and tell Him your worry. <em>Lord, help me not fear—You save. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'God helps the brave',
					'verse_ref' => '1 Samuel 17:45',
					'body'      => hwbl_plan_day_body(
						'David was small next to Goliath—but God was bigger.',
						'David said he came “in the name of the Lord of hosts.” Courage means trusting God’s strength, not only our own.',
						'Where do you need God’s help to be brave?',
						'<strong>Practice:</strong> Pray for courage before something hard today. <em>Lord of hosts, help me be brave in Your name. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Jesus loves children',
					'verse_ref' => 'Mark 10:14',
					'body'      => hwbl_plan_day_body(
						'Some people tried to keep kids away from Jesus. Jesus said no.',
						'“Let the children come to me… for to such belongs the kingdom of God.” Jesus welcomes kids. You matter to Him.',
						'How does it feel to know Jesus wants you near?',
						'<strong>Practice:</strong> Tell Jesus “I come to You” in your own words. <em>Jesus, thank You for welcoming me. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Jesus is alive',
					'verse_ref' => 'Matthew 28:6',
					'body'      => hwbl_plan_day_body(
						'The saddest day became the happiest when the tomb was empty.',
						'“He is not here, for he has risen.” Jesus beat death. That is why we have hope forever.',
						'How can you celebrate that Jesus is alive today?',
						'<strong>Practice:</strong> Sing or say “Jesus is alive!” and thank Him. <em>Risen Jesus, I am glad You are alive. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Parenting with Grace (7 days)',
			'topic'     => 'parenting',
			'shareable' => false,
			'excerpt'   => 'Seven days for parents and caregivers—discipleship at home, patience, discipline with love, prayer, and dependence on God.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Children are a heritage',
					'verse_ref' => 'Psalm 127:3',
					'body'      => hwbl_plan_day_body(
						'Parenting can feel like endless tasks. Scripture calls children a heritage—gift, not interruption.',
						'“Behold, children are a heritage from the Lord.” Exhaustion is real; so is sacred trust. God gives children into our care for His glory and their good.',
						'Have you been treating your child more as a project than a heritage?',
						'<strong>Practice:</strong> Thank God by name for each child in your care. <em>Lord, help me receive these children as Your heritage. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Teach along the way',
					'verse_ref' => 'Deuteronomy 6:6-7',
					'body'      => hwbl_plan_day_body(
						'Discipleship at home is rarely a lecture. Moses describes Word-soaked ordinary life.',
						'“These words that I command you… you shall teach them diligently to your children… when you sit… walk… lie down… rise.” Formation happens in the rhythms of the day.',
						'Where can you weave one short Scripture moment into today’s routine?',
						'<strong>Practice:</strong> Read one verse at a meal or bedtime and talk for one minute. <em>Lord, help me teach diligently along the way. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Do not provoke',
					'verse_ref' => 'Ephesians 6:4',
					'body'      => hwbl_plan_day_body(
						'Harsh parenting can crush. Paul warns fathers—and all who lead children—against provocation.',
						'“Fathers, do not provoke your children to anger, but bring them up in the discipline and instruction of the Lord.” Authority is for nurture under the Lord, not for venting.',
						'What tone or habit might be provoking rather than instructing?',
						'<strong>Practice:</strong> Apologize to your child for one harsh moment if needed. <em>Lord, make my discipline gentle and true. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Patience under pressure',
					'verse_ref' => 'Colossians 3:12',
					'body'      => hwbl_plan_day_body(
						'Meltdowns—theirs and ours—test love. Paul dresses believers in compassion and patience.',
						'“Put on… compassionate hearts, kindness, humility, meekness, and patience.” We parent out of what we wear in Christ, not out of leftover stress.',
						'Which “garment” do you most need to put on before the next hard moment?',
						'<strong>Practice:</strong> Pause, breathe, and pray Colossians 3:12 before reacting once today. <em>Christ, clothe me with patience for this child. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Train with hope',
					'verse_ref' => 'Proverbs 22:6',
					'body'      => hwbl_plan_day_body(
						'Results are slow. Proverbs calls for training in the way—trusting God with the long arc.',
						'“Train up a child in the way he should go…” Training is intentional formation toward wisdom, not guaranteeing perfect outcomes. We sow; God gives growth.',
						'What wise “way” are you actively training—honesty, prayer, kindness?',
						'<strong>Practice:</strong> Practice one habit together (prayer, chore, kindness) without quitting early. <em>Lord, help me train with hope, not panic. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Pray without ceasing for them',
					'verse_ref' => '1 Samuel 1:27-28',
					'body'      => hwbl_plan_day_body(
						'Hannah asked for a child and then lent him to the Lord. Parenting is prayerful stewardship.',
						'“For this child I prayed… Therefore I have lent him to the Lord.” Our children are His before they are ours. Intercession is core parenting work.',
						'What specific request will you bring for your child this week?',
						'<strong>Practice:</strong> Pray two minutes for your child’s faith, friendships, and future. <em>Lord, this child is Yours—guard and guide them. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Depend on the true Father',
					'verse_ref' => 'Matthew 7:11',
					'body'      => hwbl_plan_day_body(
						'Good parents still fail. Jesus points us to the Father who gives good gifts.',
						'“If you then, who are evil, know how to give good gifts to your children, how much more will your Father… give good things…” We parent from received grace, not perfection.',
						'Where do you need the Father’s “how much more” today?',
						'<strong>Practice:</strong> Ask the Father for wisdom for one parenting decision. <em>Heavenly Father, give good gifts—and wisdom—for this home. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'When Anger Burns (7 days)',
			'topic'     => 'anger',
			'shareable' => false,
			'excerpt'   => 'Seven days on righteous and unrighteous anger—slowing down, telling truth, releasing revenge, and practicing peacemaking in Christ.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Be angry and do not sin',
					'verse_ref' => 'Ephesians 4:26-27',
					'body'      => hwbl_plan_day_body(
						'Anger is not always wrong—but it is dangerous. Paul allows anger without giving it the driver’s seat.',
						'“Be angry and do not sin; do not let the sun go down on your anger, and give no opportunity to the devil.” Unresolved anger becomes a foothold.',
						'What anger have you been nursing past sundown?',
						'<strong>Practice:</strong> Name the anger to God before bed and ask for clean next steps. <em>Lord, teach me to be angry without sin. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Slow to anger',
					'verse_ref' => 'James 1:19-20',
					'body'      => hwbl_plan_day_body(
						'Quick anger feels strong; James calls it weak for producing righteousness.',
						'“Let every person be quick to hear, slow to speak, slow to anger; for the anger of man does not produce the righteousness of God.” Speed is usually the enemy.',
						'What trigger makes you fast to speak and slow to hear?',
						'<strong>Practice:</strong> Count to ten and ask one clarifying question in your next conflict. <em>Lord, make me slow to anger and quick to hear. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'The Lord is slow to anger',
					'verse_ref' => 'Exodus 34:6',
					'body'      => hwbl_plan_day_body(
						'Our temper often mirrors a false picture of God. God reveals Himself as slow to anger.',
						'“The Lord, the Lord, a God merciful and gracious, slow to anger, and abounding in steadfast love…” His patience is not weakness; it is holy love.',
						'How would imitating God’s slowness change your home or workplace?',
						'<strong>Practice:</strong> When provoked, whisper “slow to anger” before responding. <em>Merciful God, form Your patience in me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Put away wrath',
					'verse_ref' => 'Colossians 3:8',
					'body'      => hwbl_plan_day_body(
						'Some anger styles go underground—bitterness, sarcasm, cold silence. Paul says put them away.',
						'“But now you must put them all away: anger, wrath, malice, slander…” In Christ we undress old rage patterns and put on the new self.',
						'Which form of anger do you hide as “just my personality”?',
						'<strong>Practice:</strong> Confess one angry pattern and ask a trusted person to check in this week. <em>Lord, I put away wrath; clothe me anew. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Leave vengeance to God',
					'verse_ref' => 'Romans 12:19',
					'body'      => hwbl_plan_day_body(
						'Anger often wants payback. Paul relocates justice to God’s hands.',
						'“Never avenge yourselves, but leave it to the wrath of God… ‘Vengeance is mine, I will repay, says the Lord.’” You can seek wise boundaries without becoming the avenger.',
						'What revenge fantasy or scorekeeping do you need to surrender?',
						'<strong>Practice:</strong> Pray Romans 12:19 over the person who hurt you. <em>Lord, vengeance is Yours—I release this to You. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'A soft answer',
					'verse_ref' => 'Proverbs 15:1',
					'body'      => hwbl_plan_day_body(
						'Volume escalates conflict. Wisdom offers a different first move.',
						'“A soft answer turns away wrath, but a harsh word stirs up anger.” Soft is not dishonest—it is strength under control.',
						'Where do your harsh words most often stir the fire?',
						'<strong>Practice:</strong> Answer one tense moment today with a softer first sentence. <em>Lord, put a soft answer on my lips. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Peacemakers',
					'verse_ref' => 'Matthew 5:9',
					'body'      => hwbl_plan_day_body(
						'Jesus blesses those who make peace—not those who win arguments.',
						'“Blessed are the peacemakers, for they shall be called sons of God.” Peacemaking pursues reconciliation where possible and refuses to fuel the blaze.',
						'What peacemaking step is available to you this week?',
						'<strong>Practice:</strong> Initiate one reconciling word or meeting if safe. <em>Father, make me a peacemaker like Your Son. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Hope That Holds (7 days)',
			'topic'     => 'hope',
			'shareable' => false,
			'excerpt'   => 'Seven days of living hope—anchored in Christ’s resurrection, steady in suffering, and aimed at glory.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Living hope',
					'verse_ref' => '1 Peter 1:3',
					'body'      => hwbl_plan_day_body(
						'Wishful thinking fades. Peter blesses God for a hope that lives because Jesus lives.',
						'“He has caused us to be born again to a living hope through the resurrection of Jesus Christ from the dead.” Christian hope is as alive as the empty tomb.',
						'Where has your “hope” been only optimism?',
						'<strong>Practice:</strong> Thank God that hope is living because Christ is risen. <em>Father, birth living hope in me today. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Hope against hope',
					'verse_ref' => 'Romans 4:18',
					'body'      => hwbl_plan_day_body(
						'Abraham faced impossible odds. Paul says he hoped against hope.',
						'“In hope he believed against hope…” Faith holds God’s promise when circumstances argue otherwise.',
						'What promise of God do you need to believe against visible odds?',
						'<strong>Practice:</strong> Write one God-promise and read it aloud twice. <em>Lord, help me hope against hope in Your word. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Suffering produces hope',
					'verse_ref' => 'Romans 5:3-5',
					'body'      => hwbl_plan_day_body(
						'We want hope without hardship. Paul charts a surprising path.',
						'“Suffering produces endurance… character… hope, and hope does not put us to shame, because God’s love has been poured into our hearts…” Hope grows in the furnace under the Spirit’s love.',
						'What suffering might God be using to deepen hope—not destroy it?',
						'<strong>Practice:</strong> Ask the Spirit to pour God’s love into a painful place. <em>Holy Spirit, form hope that does not shame me. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Anchor of the soul',
					'verse_ref' => 'Hebrews 6:19',
					'body'      => hwbl_plan_day_body(
						'Storms yank us around. Hebrews calls hope an anchor.',
						'“We have this as a sure and steadfast anchor of the soul…” The anchor holds because it hooks into God’s sworn promise, not into our mood.',
						'What is tossing your soul that needs anchoring?',
						'<strong>Practice:</strong> Picture casting an anchor into Christ’s promise and rest one minute. <em>God of promise, be the anchor of my soul. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Hope in God',
					'verse_ref' => 'Psalm 42:11',
					'body'      => hwbl_plan_day_body(
						'The psalmist talks to his own downcast soul. Hope is sometimes a conversation.',
						'“Why are you cast down, O my soul?… Hope in God; for I shall again praise him…” Preach hope to yourself when feelings lag.',
						'What downcast story needs a “hope in God” reply?',
						'<strong>Practice:</strong> Say Psalm 42:11 aloud to your own soul. <em>My soul, hope in God—I will praise Him again. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Christ in you, the hope of glory',
					'verse_ref' => 'Colossians 1:27',
					'body'      => hwbl_plan_day_body(
						'Hope is not only future scenery—it is Christ present in you.',
						'“Christ in you, the hope of glory.” The indwelling Christ guarantees glory ahead and supplies strength now.',
						'Have you been seeking hope outside of Christ-in-you?',
						'<strong>Practice:</strong> Thank Jesus that He lives in you by His Spirit. <em>Christ in me, be my hope of glory today. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Overflow with hope',
					'verse_ref' => 'Romans 15:13',
					'body'      => hwbl_plan_day_body(
						'Hope is meant to spill. Paul prays for overflowing hope by the Spirit.',
						'“May the God of hope fill you with all joy and peace in believing, so that by the power of the Holy Spirit you may abound in hope.” Abounding hope becomes witness.',
						'Who near you needs a share of your hope this week?',
						'<strong>Practice:</strong> Encourage one person with a hope-filled word or text. <em>God of hope, fill me and make me overflow. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'A Grateful Heart (7 days)',
			'topic'     => 'gratitude',
			'shareable' => false,
			'excerpt'   => 'Seven days of thanksgiving—seeing God’s gifts, resisting grumbling, and practicing gratitude in every season.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Give thanks in all circumstances',
					'verse_ref' => '1 Thessalonians 5:18',
					'body'      => hwbl_plan_day_body(
						'Gratitude can feel fake when life hurts. Paul calls it God’s will—not denial of pain.',
						'“Give thanks in all circumstances; for this is the will of God in Christ Jesus for you.” In all circumstances—not for all evils as if they were good—but thanksgiving that God is present and working.',
						'What circumstance feels hardest to thank God in?',
						'<strong>Practice:</strong> Thank God for one gift inside a hard situation. <em>Father, I give thanks in this circumstance in Christ. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'Every good gift',
					'verse_ref' => 'James 1:17',
					'body'      => hwbl_plan_day_body(
						'We treat gifts as entitlements. James relocates the source.',
						'“Every good gift and every perfect gift is from above, coming down from the Father of lights…” Gratitude begins by naming the Giver.',
						'Which good gift have you stopped tracing back to the Father?',
						'<strong>Practice:</strong> List five gifts and write “from the Father” beside each. <em>Father of lights, every good gift is from You—thank You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Do all in the name of Jesus',
					'verse_ref' => 'Colossians 3:17',
					'body'      => hwbl_plan_day_body(
						'Ordinary tasks become worship when wrapped in thanks.',
						'“Whatever you do, in word or deed, do everything in the name of the Lord Jesus, giving thanks to God the Father through him.” Gratitude sanctifies the mundane.',
						'What ordinary task can you do today with thanks?',
						'<strong>Practice:</strong> Pray a one-sentence thanks before a routine chore or meeting. <em>Lord Jesus, I do this in Your name with thanks. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Replace grumbling',
					'verse_ref' => 'Philippians 2:14-15',
					'body'      => hwbl_plan_day_body(
						'Grumbling darkens a witness. Paul calls believers to shine without complaining.',
						'“Do all things without grumbling or disputing, that you may be blameless… in the midst of a crooked generation, among whom you shine as lights…” Thanksgiving is spiritual resistance.',
						'What grumble loop do you repeat most?',
						'<strong>Practice:</strong> Catch one complaint and turn it into a thanks or a request to God. <em>Lord, replace my grumbling with light. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Enter with thanksgiving',
					'verse_ref' => 'Psalm 100:4',
					'body'      => hwbl_plan_day_body(
						'Worship sometimes starts cold. The psalm gives an entryway.',
						'“Enter his gates with thanksgiving, and his courts with praise!” Thanksgiving is how we come in—even before feelings catch up.',
						'Have you been waiting to feel thankful before you give thanks?',
						'<strong>Practice:</strong> Begin prayer today with three thank-yous before any request. <em>Lord, I enter Your gates with thanksgiving. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'Thanks for people',
					'verse_ref' => 'Philippians 1:3',
					'body'      => hwbl_plan_day_body(
						'We thank God for things more easily than for people. Paul thanks God for the Philippians.',
						'“I thank my God in all my remembrance of you.” Gratitude for people softens relationships and honors God’s work in them.',
						'Whom have you forgotten to thank God for?',
						'<strong>Practice:</strong> Text or tell one person you thank God for them. <em>Father, I thank You for this person You gave. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Overflowing thanksgiving',
					'verse_ref' => '2 Corinthians 4:15',
					'body'      => hwbl_plan_day_body(
						'Grace aims at more than private comfort—it multiplies thanksgiving.',
						'“As grace extends to more and more people it may increase thanksgiving, to the glory of God.” Grateful hearts glorify God publicly.',
						'How could your thanks become someone else’s encouragement?',
						'<strong>Practice:</strong> Share a short testimony of thanks in conversation or church. <em>God, increase thanksgiving to Your glory. Amen.</em>'
					),
				),
			),
		),

		array(
			'title'     => 'Courage Over Fear (7 days)',
			'topic'     => 'fear',
			'shareable' => false,
			'excerpt'   => 'Seven days for fearful hearts—God’s presence, perfect love, courage to obey, and peace that guards.',
			'days'      => array(
				array(
					'day'       => 1,
					'title'     => 'Do not fear, for I am with you',
					'verse_ref' => 'Isaiah 41:10',
					'body'      => hwbl_plan_day_body(
						'Fear shrinks the future. God answers fear with presence and strength.',
						'“Fear not, for I am with you; be not dismayed, for I am your God; I will strengthen you…” The command rests on the promise: I am with you.',
						'What fear feels loudest when you forget God is with you?',
						'<strong>Practice:</strong> Read Isaiah 41:10 aloud with your name in it. <em>God, You are with me—strengthen me. Amen.</em>'
					),
				),
				array(
					'day'       => 2,
					'title'     => 'When I am afraid',
					'verse_ref' => 'Psalm 56:3-4',
					'body'      => hwbl_plan_day_body(
						'David does not pretend fear never comes. He shows what to do when it does.',
						'“When I am afraid, I put my trust in you… In God I trust; I shall not be afraid.” Trust is a placement of fear into God’s hands.',
						'Where do you go first when afraid—scrolling, control, or God?',
						'<strong>Practice:</strong> When fear rises, say “I put my trust in You” before the next action. <em>God, when I am afraid, I trust You. Amen.</em>'
					),
				),
				array(
					'day'       => 3,
					'title'     => 'Perfect love casts out fear',
					'verse_ref' => '1 John 4:18',
					'body'      => hwbl_plan_day_body(
						'Some fear is dread of punishment or rejection. John points to perfect love.',
						'“There is no fear in love, but perfect love casts out fear.” God’s love in Christ secures us; fear of condemnation loses its grip.',
						'Are you fearing God’s face—or trusting His love in Christ?',
						'<strong>Practice:</strong> Thank God that there is no condemnation for those in Christ (Romans 8:1). <em>Perfect Love, cast out my fear. Amen.</em>'
					),
				),
				array(
					'day'       => 4,
					'title'     => 'Be strong and courageous',
					'verse_ref' => 'Joshua 1:9',
					'body'      => hwbl_plan_day_body(
						'Courage is not the absence of butterflies—it is obedience with God beside you.',
						'“Have I not commanded you? Be strong and courageous… for the Lord your God is with you wherever you go.” Courage follows calling and presence.',
						'What obedient step have you delayed because of fear?',
						'<strong>Practice:</strong> Take one small courageous step you have been avoiding. <em>Lord, I will be strong—You are with me wherever I go. Amen.</em>'
					),
				),
				array(
					'day'       => 5,
					'title'     => 'Peace, not as the world gives',
					'verse_ref' => 'John 14:27',
					'body'      => hwbl_plan_day_body(
						'The world offers peace through control. Jesus gives peace as a gift.',
						'“Peace I leave with you; my peace I give to you… Let not your hearts be troubled, neither let them be afraid.” His peace is personal and present.',
						'Where are you seeking world-peace instead of Jesus’ peace?',
						'<strong>Practice:</strong> Sit two minutes receiving His peace without fixing anything. <em>Jesus, give me Your peace; quiet my heart. Amen.</em>'
					),
				),
				array(
					'day'       => 6,
					'title'     => 'God has not given a spirit of fear',
					'verse_ref' => '2 Timothy 1:7',
					'body'      => hwbl_plan_day_body(
						'Fear can feel like your personality. Paul renames what God gives.',
						'“God gave us a spirit not of fear but of power and love and self-control.” Power, love, and a sound mind push back on panic’s narrative.',
						'Which gift—power, love, or self-control—do you need to ask for today?',
						'<strong>Practice:</strong> Pray 2 Timothy 1:7 and ask for that triad by name. <em>God, give power, love, and self-control—not fear. Amen.</em>'
					),
				),
				array(
					'day'       => 7,
					'title'     => 'Even though I walk',
					'verse_ref' => 'Psalm 23:4',
					'body'      => hwbl_plan_day_body(
						'Some valleys remain. The Shepherd promise is presence in the dark.',
						'“Even though I walk through the valley of the shadow of death, I will fear no evil, for you are with me.” Courage walks because the Shepherd walks.',
						'Can you face today’s valley with “You are with me” as enough?',
						'<strong>Practice:</strong> Whisper “You are with me” on a walk or during a hard task. <em>Shepherd, I will fear no evil—You are with me. Amen.</em>'
					),
				),
			),
		),
	);
}
