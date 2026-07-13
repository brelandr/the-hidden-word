#!/usr/bin/env python3
"""Generate lessons-392-500.json with NIV text and enrichment."""

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TEXTS_PATH = ROOT / "data/curriculum-parts/_texts-392-500.json"
OUT_PATH = ROOT / "data/curriculum-parts/lessons-392-500.json"

REFS = {
    392: (24, 31, 3), 393: (24, 32, 17), 394: (25, 1, 3), 395: (25, 2, 19),
    396: (25, 5, 19), 397: (26, 11, 19), 398: (26, 18, 32), 399: (26, 34, 26),
    400: (26, 47, 14), 401: (27, 1, 8), 402: (27, 9, 9), 403: (27, 12, 3),
    404: (28, 2, 6), 405: (28, 10, 12), 406: (28, 14, 1), 407: (29, 2, 13),
    408: (29, 3, 10), 409: (30, 5, 24), 410: (31, 1, 4),
    411: (32, 1, 17), 412: (32, 4, 2), 413: (33, 5, 15), 414: (34, 1, 7),
    415: (35, 3, 17), 416: (36, 2, 3), 417: (37, 2, 4), 418: (38, 1, 3),
    419: (39, 1, 14), 420: (40, 3, 2), 421: (40, 5, 4), 422: (40, 7, 12),
    423: (40, 9, 37), 424: (40, 12, 30), 425: (40, 13, 40), 426: (40, 14, 27),
    427: (40, 17, 20), 428: (40, 20, 28), 429: (40, 25, 40), 430: (40, 27, 46),
    431: (41, 1, 15), 432: (41, 5, 36), 433: (41, 9, 24), 434: (41, 11, 24),
    435: (41, 13, 31), 436: (41, 14, 36), 437: (41, 15, 34), 438: (42, 1, 47),
    439: (42, 4, 18), 440: (42, 5, 32), 441: (42, 7, 47), 442: (42, 11, 28),
    443: (42, 14, 27), 444: (42, 16, 13), 445: (42, 17, 32), 446: (42, 19, 45),
    447: (42, 22, 42), 448: (43, 2, 5), 449: (43, 5, 24), 450: (43, 8, 31),
    451: (43, 9, 4), 452: (43, 12, 32), 453: (43, 13, 35), 454: (43, 17, 17),
    455: (43, 18, 37), 456: (43, 21, 15), 457: (44, 3, 19), 458: (44, 6, 4),
    459: (44, 8, 28), 460: (44, 11, 26), 461: (44, 13, 38), 462: (44, 17, 28),
    463: (45, 2, 4), 464: (45, 7, 18), 465: (45, 11, 33), 466: (45, 12, 12),
    467: (45, 13, 10), 468: (45, 14, 13), 469: (45, 16, 20),
    470: (46, 3, 16), 471: (46, 6, 19), 472: (46, 9, 24), 473: (46, 11, 1),
    474: (46, 13, 12), 475: (46, 15, 33), 476: (47, 1, 3), 477: (47, 3, 18),
    478: (47, 4, 16), 479: (47, 8, 9), 480: (47, 13, 4), 481: (48, 1, 3),
    482: (48, 3, 28), 483: (48, 4, 4), 484: (48, 5, 22), 485: (49, 5, 22),
    486: (49, 6, 12), 487: (50, 2, 8), 488: (50, 3, 14), 489: (50, 4, 19),
    490: (51, 1, 16), 491: (51, 2, 9), 492: (51, 3, 12), 493: (51, 4, 6),
    494: (52, 1, 10), 495: (52, 3, 13), 496: (52, 4, 3), 497: (52, 5, 9),
    498: (53, 3, 16), 499: (53, 2, 17), 500: (54, 3, 16),
}

ENRICHMENT = {
    392: {
        "historical_context": "<p>Jeremiah ministered during the final decades before Jerusalem's fall to Babylon in 586 BC. In chapter 31, God speaks through the prophet to exiles and survivors with promises of a new covenant and restored relationship.</p>",
        "preceding_narrative": "<p>Jeremiah 31 opens with hope for the remnant of Israel. After chapters of warning and lament, God recalls His ancient love for His people and promises to rebuild and replant the nation with compassion.</p>",
        "discussion_questions": ["What does 'everlasting love' mean when life feels temporary or painful?", "How has God drawn you with kindness rather than force?", "Why do you think God reminded Israel of the past before promising the future?"],
    },
    393: {
        "historical_context": "<p>While Jerusalem was under Babylonian siege, Jeremiah was commanded to buy a field at Anathoth as a sign that God would restore the land. His prayer in chapter 32 wrestles with God's power in the midst of national collapse.</p>",
        "preceding_narrative": "<p>Jeremiah obeyed God's strange command to purchase land during a siege. Overwhelmed by circumstances, he turned to God and acknowledged the Lord as Creator of heaven and earth before this declaration of divine omnipotence.</p>",
        "discussion_questions": ["What situation in your life feels 'too hard' for God?", "How does trusting God's power change the way you obey in uncertain times?", "Why did Jeremiah's act of buying land matter as much as his prayer?"],
    },
    394: {
        "historical_context": "<p>Lamentations was likely written after Babylon destroyed Jerusalem in 586 BC. The book gives voice to grief over exile, famine, and the loss of temple and city — the center of Israel's identity.</p>",
        "preceding_narrative": "<p>Chapter 1 personifies Jerusalem as a widow, abandoned and afflicted. The poet describes her isolation among the nations and the relentless pursuit of enemies who overtook Judah in her hour of distress.</p>",
        "discussion_questions": ["Why is it important for God's people to lament honestly?", "Where do you see people today living without a 'resting place'?", "How can faith survive when national or personal security collapses?"],
    },
    395: {
        "historical_context": "<p>Lamentations 2 describes God's judgment on Jerusalem through vivid images of famine and siege. Mothers and children suffer at street corners — a heartbreaking scene in a city once called the joy of the earth.</p>",
        "preceding_narrative": "<p>The chapter catalogs devastation: the Lord has swallowed up every dwelling without pity. The prophet calls Jerusalem to cry out in the night and pour out her heart before God for the lives of starving children.</p>",
        "discussion_questions": ["What does pouring out your heart 'like water' look like in prayer?", "How should believers respond when the innocent suffer?", "Why might God invite raw, nighttime cries rather than polished prayers?"],
    },
    396: {
        "historical_context": "<p>Lamentations 5 is a communal prayer after catastrophe. Though earlier chapters wrestle with judgment, the book ends by affirming God's eternal reign even when human institutions have failed.</p>",
        "preceding_narrative": "<p>The people list their humiliation: slaves rule over them, elders are dishonored, and joy has departed. After confessing their sins and asking God to remember, they anchor hope in the Lord's unending kingship.</p>",
        "discussion_questions": ["How does God's eternal reign comfort you when leaders fail?", "What does it mean to hope in God's throne across generations?", "Where do you need to confess community sin while still trusting God's rule?"],
    },
    397: {
        "historical_context": "<p>Ezekiel prophesied among exiles in Babylon after 597 BC. In chapter 11, he confronts leaders in Jerusalem who gave false security while the city headed toward destruction.</p>",
        "preceding_narrative": "<p>God told Ezekiel that leaders who said 'We will replace the fallen bricks with dressed stone' would fall by the sword. After judgment on corrupt counsel, God promises inward transformation for those who return to Him.</p>",
        "discussion_questions": ["What is the difference between outward religion and a 'heart of flesh'?", "How does God create an 'undivided heart' in divided times?", "Where do you see leaders offering false security today?"],
    },
    398: {
        "historical_context": "<p>Ezekiel 18 addresses a proverb circulating among exiles: 'The parents eat sour grapes, and the children's teeth are set on edge.' The people blamed ancestors instead of taking responsibility for their own choices.</p>",
        "preceding_narrative": "<p>God rejects the idea that children automatically bear parents' guilt. He describes the righteous and wicked person each answering for their own way, then pleads with the house of Israel to repent and live.</p>",
        "discussion_questions": ["Why is personal repentance harder than blaming others?", "How does this chapter shape your view of God's justice and mercy?", "What would 'repent and live' look like in one area of your life this week?"],
    },
    399: {
        "historical_context": "<p>Ezekiel 34 rebukes Israel's shepherds — leaders who fed themselves rather than the flock. God promises to search for His sheep Himself and appoint David as shepherd, pointing to messianic hope.</p>",
        "preceding_narrative": "<p>After condemning negligent leaders and promising to be the true Shepherd, God pledges blessing around His holy hill. He will send showers in season, reversing famine and desolation under failed shepherds.</p>",
        "discussion_questions": ["What are 'showers of blessing' you have experienced from God?", "How should spiritual leaders care for people differently than the shepherds Ezekiel condemned?", "Where do you need to trust God's provision in a dry season?"],
    },
    400: {
        "historical_context": "<p>Ezekiel 47 closes with a vision of restored land boundaries after the river of life flows from the temple. The prophet describes Israel's inheritance divided among the tribes in the age to come.</p>",
        "preceding_narrative": "<p>The chapter moves from a river deepening as it flows east, bringing life wherever it goes, to specific allotments of land. God recalls His oath to the ancestors and promises the land as a lasting inheritance.</p>",
        "discussion_questions": ["How does God's faithfulness to ancient promises encourage you today?", "What does a 'life-giving river' from God's presence suggest spiritually?", "Why is land inheritance significant in biblical hope?"],
    },
    401: {
        "historical_context": "<p>Daniel and other Judean youths were taken to Babylon around 605 BC to serve in Nebuchadnezzar's court. They faced pressure to adopt Babylonian customs while remaining faithful to the God of Israel.</p>",
        "preceding_narrative": "<p>Chapter 1 introduces Daniel, Hananiah, Mishael, and Azariah as gifted young men chosen for royal service. The king assigned them food and wine from his table, setting up Daniel's courageous resolve.</p>",
        "discussion_questions": ["What 'royal food' tempts believers to compromise today?", "How can you prepare convictions before pressure arrives?", "Why might small acts of faithfulness in private prepare you for public witness?"],
    },
    402: {
        "historical_context": "<p>Daniel's prayer in chapter 9 comes during the exile, around 539 BC as the seventy-year captivity nears its end. Daniel confesses national sin while appealing to God's covenant mercy.</p>",
        "preceding_narrative": "<p>After reading Jeremiah's prophecy about seventy years, Daniel fasted and prayed with confession of rebellion. He acknowledged God's righteousness and the people's guilt before celebrating divine forgiveness.</p>",
        "discussion_questions": ["Why is confession important before asking God for help?", "How does God's mercy differ from excusing sin?", "What national or community sins should God's people confess today?"],
    },
    403: {
        "historical_context": "<p>Daniel 12 concludes apocalyptic visions given during Persian rule. The chapter describes a time of unprecedented distress followed by resurrection and final accountability before God.</p>",
        "preceding_narrative": "<p>An angel tells Daniel that many will be purified and made spotless, but the wicked will not understand. The chapter closes with assurance that the wise who lead others to righteousness will shine forever.</p>",
        "discussion_questions": ["What does it mean to 'lead many to righteousness' in ordinary life?", "How does eternal hope shape daily decisions?", "Who has helped you grow in wisdom, and how can you pass that on?"],
    },
    404: {
        "historical_context": "<p>Hosea prophesied to the northern kingdom of Israel before its fall to Assyria in 722 BC. God commanded Hosea to marry an unfaithful wife as a living picture of Israel's spiritual adultery.</p>",
        "preceding_narrative": "<p>Chapter 2 uses marriage imagery to describe Israel chasing other gods. God declares He will hedge her path with thorns and wall her in, blocking access to false lovers so she might return.</p>",
        "discussion_questions": ["How can God's discipline be an expression of love?", "What 'paths' might God block to protect you from harm?", "Why does God sometimes allow frustration before restoration?"],
    },
    405: {
        "historical_context": "<p>Hosea called Israel to return to the Lord during a period of prosperity that masked deep idolatry and injustice. Chapter 10 compares the nation to a luxuriant vine that bears fruit for itself rather than God.</p>",
        "preceding_narrative": "<p>Israel trusted military alliances and idols instead of the Lord. Hosea urged them to break up unplowed ground — hardened hearts — and sow righteousness, seeking God until He sends righteousness like rain.</p>",
        "discussion_questions": ["What 'unplowed ground' in your heart needs breaking up?", "How is seeking the Lord different from seeking blessings?", "What would sowing righteousness look like in your family or church?"],
    },
    406: {
        "historical_context": "<p>Hosea closes with a tender call to repentance after chapters of judgment. Israel's sins — idolatry, political betrayal, and broken covenant love — had brought the nation to the edge of ruin.</p>",
        "preceding_narrative": "<p>God lists reasons Israel should not trust Assyria or warhorses, then offers a path home. The chapter opens with a direct invitation to return, naming sin as the cause of their downfall.</p>",
        "discussion_questions": ["What makes returning to God difficult after repeated failure?", "How does naming sin honestly prepare the heart for repentance?", "Where do you need to hear God's invitation to return today?"],
    },
    407: {
        "historical_context": "<p>Joel prophesied to Judah, likely during a devastating locust plague that stripped the land bare. He called the nation to fasting and repentance, seeing the disaster as a wake-up call to return to God.</p>",
        "preceding_narrative": "<p>Joel summoned elders and all inhabitants to lament. He called for a sacred assembly and described rending hearts rather than merely tearing garments — outward ritual without inward change.</p>",
        "discussion_questions": ["What is the difference between outward remorse and a broken heart before God?", "How do Joel's words about God's compassion encourage honest repentance?", "When has hardship moved you to seek the Lord more deeply?"],
    },
    408: {
        "historical_context": "<p>Joel 3 describes the Lord's judgment on the nations in the Valley of Jehoshaphat. After promising to restore Judah, God summons the nations to war against His people and prepares His warriors.</p>",
        "preceding_narrative": "<p>God calls the nations to battle, urging weaklings to claim strength and farmers to beat plowshares into swords. The verse reverses Isaiah's later vision of peace, showing a season of holy war and judgment.</p>",
        "discussion_questions": ["How do Joel's images of judgment and restoration fit together?", "What does it mean for the weak to say 'I am strong' in God's cause?", "How should believers understand God's final justice toward oppressors?"],
    },
    409: {
        "historical_context": "<p>Amos was a shepherd from Tekoa called to prophesy against Israel during the reign of Jeroboam II (8th century BC). The northern kingdom enjoyed wealth while crushing the poor and perverting justice.</p>",
        "preceding_narrative": "<p>Chapter 5 opens with a funeral lament over Israel. God rejects their festivals and offerings because they trample the needy. He calls them to let justice roll like a river and righteousness like a never-failing stream.</p>",
        "discussion_questions": ["What does justice 'rolling like a river' look like in your community?", "How can worship and justice belong together?", "Where do you see religion without righteousness around you?"],
    },
    410: {
        "historical_context": "<p>Obadiah prophesied against Edom, the nation descended from Esau, which rejoiced when Jerusalem fell to Babylon in 586 BC. Edom's pride and betrayal of its brother nation Judah brought divine judgment.</p>",
        "preceding_narrative": "<p>Obadiah announces that though Edom dwells in rocky heights and trusts in its location, God will bring the nation down. No human pride — even eagle-high security — can outlast the Lord's decree.</p>",
        "discussion_questions": ["What forms of pride make people feel untouchable today?", "How should believers respond when others rejoice over the suffering of God's people?", "Why is trusting in location, wealth, or status spiritually dangerous?"],
    },
}

# Part 2: lessons 411-455
ENRICHMENT.update({
    411: {
        "historical_context": "<p>Jonah was a prophet sent to Nineveh, capital of Assyria — Israel's feared enemy. His story, set in the 8th century BC, explores God's mercy toward nations Israel despised.</p>",
        "preceding_narrative": "<p>Jonah fled by ship toward Tarshish rather than preach in Nineveh. God sent a violent storm, and sailors threw Jonah overboard at his request. Then the Lord provided a great fish to swallow him for three days and nights.</p>",
        "discussion_questions": ["Why do you think God used such an extraordinary rescue for a fleeing prophet?", "How does Jonah's time in the fish foreshadow Jesus' death and resurrection?", "When have you run from something God called you to do?"],
    },
    412: {
        "historical_context": "<p>After preaching in Nineveh and seeing the city repent, Jonah sat east of the city angry that God showed compassion. His complaint reveals how deeply he resented God's mercy toward enemies.</p>",
        "preceding_narrative": "<p>God provided a plant for Jonah's shade, then a worm and scorching wind. When Jonah grieved over the plant, God questioned his priorities. Jonah's prayer quotes God's own description of His gracious character — yet Jonah resents it.</p>",
        "discussion_questions": ["Why is it hard to celebrate mercy shown to people we dislike?", "How does Jonah's prayer expose our own double standards?", "Who in your life is hardest for you to want God to save?"],
    },
    413: {
        "historical_context": "<p>Micah prophesied to Judah during the reigns of Jotham, Ahaz, and Hezekiah (8th century BC). He condemned corrupt leaders and false prophets while pointing to a future ruler from Bethlehem.</p>",
        "preceding_narrative": "<p>Chapter 5 promises a ruler from Bethlehem who will shepherd God's flock in strength. The chapter closes with God's vow to take vengeance on nations that refused obedience, ending Micah's oracle of hope and judgment.</p>",
        "discussion_questions": ["How do Micah's promises of a shepherd-king and warnings of judgment fit together?", "What does obedience to God require beyond religious ritual?", "How should hope in Christ shape our view of God's justice?"],
    },
    414: {
        "historical_context": "<p>Nahum prophesied against Nineveh about a century after Jonah. Assyria had oppressed many nations, including Israel and Judah. Nahum announces that the Lord will not leave the guilty unpunished.</p>",
        "preceding_narrative": "<p>Chapter 1 opens with an acrostic poem celebrating God's power over nature and nations. In the midst of descriptions of judgment, Nahum declares that the Lord is good — a refuge for those who trust Him in trouble.</p>",
        "discussion_questions": ["How can God be both good and a judge of the wicked?", "When has God been your refuge in trouble?", "What does trusting God look like when powerful enemies seem unstoppable?"],
    },
    415: {
        "historical_context": "<p>Habakkuk wrestled with God about injustice in Judah, likely before Babylon's rise. Chapter 3 is a prayer set to music, recalling God's past acts of salvation at the Exodus and conquest.</p>",
        "preceding_narrative": "<p>Habakkuk trembles at God's coming judgment yet chooses joy in the Lord. He vows to rejoice even if fig trees, vines, olive crops, flocks, and herds all fail — a declaration of faith amid total loss.</p>",
        "discussion_questions": ["Can you rejoice in the Lord when outward blessings disappear?", "What does Habakkuk's list of losses teach about real faith?", "How do God's past acts give courage for an uncertain future?"],
    },
    416: {
        "historical_context": "<p>Zephaniah prophesied during Josiah's reforms in Judah (late 7th century BC). He warned of the coming 'day of the LORD' — a day of wrath against sin that would touch Judah and the nations.</p>",
        "preceding_narrative": "<p>Chapter 2 calls surrounding nations to seek the Lord before judgment arrives. Zephaniah urges the humble who obey God's commands to seek righteousness and humility, perhaps finding shelter on the day of wrath.</p>",
        "discussion_questions": ["What does humility look like when God's judgment is real?", "How can seeking righteousness be a form of protection?", "Why does Zephaniah address the 'humble of the land' specifically?"],
    },
    417: {
        "historical_context": "<p>Haggai prophesied in 520 BC after exiles returned from Babylon but stalled rebuilding the temple. Economic hardship and misplaced priorities had left God's house in ruins while people focused on their own homes.</p>",
        "preceding_narrative": "<p>Haggai confronted the people: 'Is it a time for you yourselves to be living in your paneled houses while this house remains a ruin?' God then called Zerubbabel, Joshua, and all the people to be strong and work, for He was with them.</p>",
        "discussion_questions": ["What work has God called you to that requires courage right now?", "How does knowing 'I am with you' change the way you face hard tasks?", "Where might misplaced priorities delay God's purposes today?"],
    },
    418: {
        "historical_context": "<p>Zechariah encouraged returned exiles alongside Haggai in 520 BC. His early visions addressed spiritual apathy and the need for repentance as the temple rebuilding resumed.</p>",
        "preceding_narrative": "<p>Zechariah's first vision showed horsemen reporting that the nations were at rest while Jerusalem lay unrestored. The angel asked how long God would withhold mercy, leading to this call for the people to return to the Lord.</p>",
        "discussion_questions": ["What does it mean for God to 'return to you' when you return to Him?", "Why is repentance the starting point for rebuilding?", "Where do you need to turn back to God before moving forward with plans?"],
    },
    419: {
        "historical_context": "<p>Malachi prophesied to post-exilic Judah, likely in the 5th century BC. The people offered defective sacrifices, tolerated injustice, and questioned whether serving God was worthwhile.</p>",
        "preceding_narrative": "<p>God confronted priests and people who brought blind, sick, and lame animals to the altar. Malachi declared that God deserves the best, not leftovers, and that His name must be revered among the nations.</p>",
        "discussion_questions": ["What does giving God 'blemished' offerings look like today?", "How does reverence for God's name shape worship and daily life?", "Why is integrity in small commitments a test of true devotion?"],
    },
    420: {
        "historical_context": "<p>John the Baptist preached in the Judean wilderness around AD 27–29, preparing the way for Jesus. Roman occupation, Herodian politics, and religious division shaped the world into which the Messiah came.</p>",
        "preceding_narrative": "<p>Matthew 3 introduces John as the voice crying in the wilderness. He wore camel's hair, ate locusts and honey, and baptized crowds in the Jordan as they confessed sin and anticipated God's coming kingdom.</p>",
        "discussion_questions": ["What does repentance mean before you can receive good news?", "How is God's kingdom 'near' in a way that demands response?", "Who in your life models bold, humble preparation for Christ?"],
    },
    421: {
        "historical_context": "<p>Jesus taught the Sermon on the Mount to disciples and crowds in Galilee. His beatitudes redefined blessing in a culture that often prized power, honor, and visible success.</p>",
        "preceding_narrative": "<p>After describing the poor in spirit, those who hunger for righteousness, the merciful, and the peacemakers, Jesus blesses those who mourn. He promises comfort rather than avoidance of grief.</p>",
        "discussion_questions": ["Why would mourning be called blessed rather than avoided?", "What kind of comfort does Jesus promise — and when?", "How does this beatitude challenge cultural pressure to appear strong?"],
    },
    422: {
        "historical_context": "<p>Jesus concluded a section of the Sermon on the Mount addressing how His followers relate to others and to the Law. First-century Judaism debated how Torah applied in daily relationships under Roman rule.</p>",
        "preceding_narrative": "<p>Jesus had taught about prayer, fasting, treasures in heaven, and not worrying. He then summarized ethical life: treat others as you would want to be treated — the heart of the Law and the Prophets.</p>",
        "discussion_questions": ["How would your relationships change if you applied this rule consistently?", "Why does Jesus call this the summary of Scripture?", "What situation this week is a chance to practice the Golden Rule?"],
    },
    423: {
        "historical_context": "<p>Jesus traveled through Galilean towns teaching, preaching, and healing. Large crowds followed Him, creating both opportunity and exhaustion as the needs outpaced available workers.</p>",
        "preceding_narrative": "<p>Jesus healed a paralyzed man, called Matthew the tax collector, and answered questions about fasting. He taught about new wine and old wineskins, then looked at the crowds with compassion before speaking of harvest and workers.</p>",
        "discussion_questions": ["Where do you see a plentiful harvest but few workers?", "How can you respond to Jesus' compassion for the crowds?", "What might God be calling you to do in His harvest field?"],
    },
    424: {
        "historical_context": "<p>Jesus faced growing opposition from Pharisees who accused Him of casting out demons by Beelzebul. Religious leaders increasingly treated His ministry as a threat rather than a sign of God's kingdom.</p>",
        "preceding_narrative": "<p>After healing a demon-oppressed man, Pharisees challenged Jesus' authority. He warned about blasphemy against the Spirit and taught that a tree is known by its fruit before declaring there is no neutral ground regarding Him.</p>",
        "discussion_questions": ["Why does Jesus say there is no middle position regarding Him?", "How do people's actions reveal whether they are gathering or scattering?", "What does loyalty to Christ require when neutrality seems easier?"],
    },
    425: {
        "historical_context": "<p>Jesus taught parables about the kingdom of heaven to crowds by the sea of Galilee. Parables both revealed truth to receptive hearers and concealed it from those who rejected Him.</p>",
        "preceding_narrative": "<p>Jesus explained the parable of the weeds among the wheat to His disciples. He described the enemy sowing weeds, both growing together until harvest, when the weeds would be gathered and burned — a picture of final judgment.</p>",
        "discussion_questions": ["How does the parable of weeds and wheat explain evil in the world?", "Why does God allow good and evil to coexist for a season?", "How should final judgment shape how you live now?"],
    },
    426: {
        "historical_context": "<p>After feeding the five thousand, Jesus sent disciples across the Sea of Galilee while He prayed alone. Storms on the lake were common and dangerous for fishermen accustomed to the water.</p>",
        "preceding_narrative": "<p>Disciples struggled against wind and waves in the fourth watch of the night. They were terrified when they saw Jesus walking on the water, thinking He was a ghost — until He spoke: 'Take courage! It is I. Don't be afraid.'</p>",
        "discussion_questions": ["What storms make it hard to recognize Jesus' presence?", "How does hearing 'It is I' change fear in crisis?", "When have you needed courage more than explanation?"],
    },
    427: {
        "historical_context": "<p>Jesus had just come down from the mountain where He was transfigured. A man brought his demon-possessed son whom the disciples could not heal, exposing the limits of faith without dependence on Christ.</p>",
        "preceding_narrative": "<p>Jesus rebuked the demon and healed the boy. When disciples asked why they failed, Jesus pointed to little faith. He said faith even as small as a mustard seed could move mountains — nothing would be impossible.</p>",
        "discussion_questions": ["What 'mountain' in your life feels immovable?", "How is mustard-seed faith different from self-confidence?", "Why did the disciples' failure lead to a lesson about trust rather than technique?"],
    },
    428: {
        "historical_context": "<p>Jesus was on the road to Jerusalem, teaching disciples about leadership and greatness in God's kingdom. Roman culture prized honor and status; disciples argued about who would be greatest.</p>",
        "preceding_narrative": "<p>Mother of James and John asked for privileged seats in the kingdom. Jesus taught that rulers lord it over others, but His followers must serve. The Son of Man came not to be served but to serve and give His life as a ransom.</p>",
        "discussion_questions": ["How does Jesus redefine greatness through service?", "What does His life as a 'ransom' mean for your salvation?", "Where can you choose serving over being served this week?"],
    },
    429: {
        "historical_context": "<p>Jesus taught about final judgment in Olivet Discourse material, using sheep and goats imagery familiar to a culture shaped by shepherding. The scene describes the Son of Man judging all nations.</p>",
        "preceding_narrative": "<p>Jesus separated people as a shepherd separates sheep from goats. The King identified with the hungry, stranger, sick, and imprisoned — saying care shown to the least of His brothers was care shown to Him.</p>",
        "discussion_questions": ["How does serving the vulnerable become serving Christ?", "Who are 'the least of these' in your community?", "How does this passage shape your view of final judgment?"],
    },
    430: {
        "historical_context": "<p>Jesus was crucified outside Jerusalem around AD 30 under Pontius Pilate. Darkness covered the land from noon to three as He bore the sins of the world on the cross.</p>",
        "preceding_narrative": "<p>After hours of mockery, beating, and crucifixion, Jesus cried out in Aramaic, quoting Psalm 22: 'My God, my God, why have you forsaken me?' The cry expresses dereliction as He took humanity's separation from God.</p>",
        "discussion_questions": ["What does Jesus' cry teach about the cost of our salvation?", "How does quoting Psalm 22 point to hope beyond forsakenness?", "Why is it important not to rush past the suffering of the cross?"],
    },
    431: {
        "historical_context": "<p>Mark's Gospel moves quickly, presenting Jesus' ministry in Galilee after John the Baptist's arrest. Roman rule and Jewish hopes for God's kingdom set the stage for Jesus' urgent message.</p>",
        "preceding_narrative": "<p>After His baptism and temptation, Jesus called His first disciples by the Sea of Galilee. He entered Capernaum, taught in the synagogue, and cast out an impure spirit before proclaiming the kingdom's arrival.</p>",
        "discussion_questions": ["Why does Mark present Jesus' message as urgent — 'the time has come'?", "What does repenting and believing the good news require today?", "How is God's kingdom 'near' in ways people miss?"],
    },
    432: {
        "historical_context": "<p>Jairus, a synagogue leader, fell at Jesus' feet begging for his dying daughter. In the same scene, a woman with chronic bleeding pushed through the crowd — two desperate people in a culture where ritual impurity carried stigma.</p>",
        "preceding_narrative": "<p>While Jesus was still speaking to the healed woman, men arrived saying Jairus' daughter had died. Jesus told the synagogue leader not to be afraid but to believe, then continued toward the house to raise her.</p>",
        "discussion_questions": ["What does 'don't be afraid; just believe' mean when circumstances look final?", "How does Jesus respond to both public leaders and hidden sufferers?", "When has fear competed with faith in your life?"],
    },
    433: {
        "historical_context": "<p>A man brought his son with a spirit that caused seizures and threw him into fire and water. Disciples had failed to heal the boy, and religious teachers argued with them as the boy suffered.</p>",
        "preceding_narrative": "<p>Jesus lamented the faithless generation and ordered the spirit to leave. The father cried out in desperation — believing yet asking for help with unbelief — and Jesus healed the boy, teaching disciples about prayer and faith.</p>",
        "discussion_questions": ["Have you ever prayed 'I believe; help my unbelief'?", "Why is honest doubt sometimes the beginning of deeper faith?", "What keeps faith from becoming mere confidence in outcomes?"],
    },
    434: {
        "historical_context": "<p>Jesus entered Jerusalem amid messianic expectations, cleansing the temple and debating leaders. His teaching in chapters 11–13 addresses prayer, authority, and the temple's coming destruction.</p>",
        "preceding_narrative": "<p>Jesus cursed a fig tree and taught on prayer, forgiveness, and the source of His authority. After parables against religious leaders, He spoke of future tribulation and urged disciples to pray with faith as they faced uncertainty.</p>",
        "discussion_questions": ["What does believing you have received in prayer look like practically?", "How can prayer be distorted into a formula rather than trust?", "What are you asking God for that requires childlike faith?"],
    },
    435: {
        "historical_context": "<p>Jesus taught on the Mount of Olives about the temple's destruction and the end of the age. Disciples faced a future without the temple — the center of Jewish worship and national identity.</p>",
        "preceding_narrative": "<p>Jesus described wars, earthquakes, persecution, and the abomination of desolation. He urged watchfulness because no one knows the day or hour, then assured disciples that heaven and earth will pass but His words endure forever.</p>",
        "discussion_questions": ["Why is the permanence of Jesus' words comforting in changing times?", "How does eschatological teaching call you to watchful obedience?", "What human institutions feel permanent but are not?"],
    },
    436: {
        "historical_context": "<p>After the Last Supper, Jesus went to Gethsemane on the Mount of Olives. Judas would soon arrive with an armed crowd; the crucifixion was hours away.</p>",
        "preceding_narrative": "<p>Jesus told disciples His soul was overwhelmed with sorrow to the point of death. He prayed alone, falling to the ground and asking Abba Father to take the cup — yet submitting fully to the Father's will.</p>",
        "discussion_questions": ["What does Jesus' struggle in Gethsemane teach about real humanity and real obedience?", "How is 'not what I will, but what you will' a model for prayer?", "When is surrender harder than suffering itself?"],
    },
    437: {
        "historical_context": "<p>Mark's account of the crucifixion emphasizes Jesus' abandonment and suffering. Darkness covered the land for three hours as Jesus hung on the cross outside Jerusalem.</p>",
        "preceding_narrative": "<p>Passersby mocked, chief priests jeered, and even those crucified with Him heaped insults. At three in the afternoon Jesus cried out in Aramaic, quoting Psalm 22 about being forsaken by God.</p>",
        "discussion_questions": ["Why does Mark highlight Jesus' forsakenness so starkly?", "How does the cross address our deepest fear of abandonment?", "What does responding to mockery with a psalm of lament reveal about Jesus?"],
    },
    438: {
        "historical_context": "<p>Mary's song, the Magnificat, comes after the angel Gabriel announced she would bear the Messiah. First-century Galilee was a modest region under Herod Antipas and Roman authority.</p>",
        "preceding_narrative": "<p>Mary traveled to visit Elizabeth, who was also miraculously pregnant. When Mary greeted her, Elizabeth's baby leaped and Elizabeth blessed Mary, who responded by praising God and rejoicing in her Savior.</p>",
        "discussion_questions": ["Why does Mary call God her Savior before Jesus is born?", "What does rejoicing in God look like amid uncertainty?", "How does Mary's song challenge our view of power and humility?"],
    },
    439: {
        "historical_context": "<p>Jesus read Scripture in the Nazareth synagogue at the start of His public ministry. He chose a passage from Isaiah associated with messianic hope for the poor, captive, blind, and oppressed.</p>",
        "preceding_narrative": "<p>After fasting forty days and being tempted in the wilderness, Jesus returned to Galilee in the Spirit's power. In Nazareth He stood to read Isaiah 61 and declared the Scripture fulfilled in their hearing that day.</p>",
        "discussion_questions": ["Who are the poor, captive, and oppressed in your community?", "What does it mean that this Scripture is 'fulfilled' in Jesus?", "How should the Spirit's anointing shape the church's mission?"],
    },
    440: {
        "historical_context": "<p>Jesus called Levi the tax collector and ate with sinners, provoking Pharisees and scribes. Table fellowship in the ancient world signaled acceptance and community.</p>",
        "preceding_narrative": "<p>After healing a paralyzed man and calling Levi, Jesus ate at Levi's house with tax collectors and others. Religious leaders grumbled that He ate with sinners, prompting Jesus to explain His mission.</p>",
        "discussion_questions": ["Why did Jesus prioritize sinners over the self-righteous?", "Who might be excluded from your table that Jesus would include?", "How does calling sinners to repentance differ from approving sin?"],
    },
    441: {
        "historical_context": "<p>A sinful woman anointed Jesus' feet at the home of Simon the Pharisee. In that culture, Pharisees avoided close contact with known sinners; her public act was bold and costly.</p>",
        "preceding_narrative": "<p>Simon questioned whether Jesus was a prophet since He allowed the woman to touch Him. Jesus told a parable about two debtors forgiven different amounts, then named her great love as evidence of great forgiveness.</p>",
        "discussion_questions": ["Why do those forgiven much often love much?", "How does recognizing your own forgiveness change how you love Jesus?", "What keeps people with little sense of sin from loving deeply?"],
    },
    442: {
        "historical_context": "<p>While Jesus was teaching, a woman in the crowd praised the mother who bore and nursed Him. Popular piety often honored family lineage; Jesus redirected attention to obedience to God's word.</p>",
        "preceding_narrative": "<p>Jesus had been casting out demons and confronting those who attributed His power to Beelzebul. When the woman shouted praise for His mother, Jesus redefined blessing around hearing and keeping God's word.</p>",
        "discussion_questions": ["What is more important than family connection to Jesus?", "How does obeying God's word bring blessing?", "Where might religious admiration replace actual obedience?"],
    },
    443: {
        "historical_context": "<p>Large crowds traveled with Jesus as He set His face toward Jerusalem. He taught that following Him would cost more than casual curiosity or social approval.</p>",
        "preceding_narrative": "<p>Jesus told parables about counting the cost of building a tower and going to war. He warned that those who cling to family, possessions, or comfort without carrying the cross cannot be His disciples.</p>",
        "discussion_questions": ["What does carrying your cross mean in ordinary life?", "Why does Jesus demand such total commitment?", "What comforts compete with discipleship for you?"],
    },
    444: {
        "historical_context": "<p>Jesus told parables about the kingdom because Pharisees loved money and sneered at Him. Wealth and religious status often reinforced each other among elite leaders.</p>",
        "preceding_narrative": "<p>Jesus told the parable of the shrewd manager and warned that no one can serve two masters. He declared you cannot serve both God and money, exposing divided loyalty among listeners.</p>",
        "discussion_questions": ["What signs reveal whether money is a master in your life?", "How can wealth be used faithfully without becoming an idol?", "Why does Jesus speak so directly about financial loyalty?"],
    },
    445: {
        "historical_context": "<p>Jesus taught about the coming of the Son of Man, comparing it to the days of Noah and Lot. His hearers knew those stories of sudden judgment amid ordinary life.</p>",
        "preceding_narrative": "<p>Jesus warned that life would seem normal — eating, drinking, buying, selling — until sudden destruction came. He urged disciples not to look back like Lot's wife, who was destroyed when she longed for what she left.</p>",
        "discussion_questions": ["What does 'remember Lot's wife' warn against spiritually?", "How can attachment to the past endanger faith?", "What would looking back look like for you if God is calling you forward?"],
    },
    446: {
        "historical_context": "<p>Jesus entered Jerusalem for Passover amid crowds shouting messianic hopes. The temple had become a place of commerce where merchants profited from required sacrifices and currency exchange.</p>",
        "preceding_narrative": "<p>After the triumphal entry and weeping over Jerusalem, Jesus entered the temple courts. He drove out those selling, saying His house should be a house of prayer but they had made it a den of robbers.</p>",
        "discussion_questions": ["What turns worship spaces into places of profit or self-interest?", "Why was Jesus' anger at the temple justified?", "How should zeal for God's house shape your church involvement?"],
    },
    447: {
        "historical_context": "<p>On the night of His betrayal, Jesus prayed on the Mount of Olives while disciples slept. Judas and armed guards were approaching; the cross was imminent.</p>",
        "preceding_narrative": "<p>At the Last Supper Jesus foretold betrayal and established the bread and cup. He then withdrew to pray in anguish, asking the Father to remove the cup of suffering while yielding completely to God's will.</p>",
        "discussion_questions": ["How does Jesus' prayer model honest struggle and submission?", "What 'cup' of difficulty are you asking God to remove?", "Why is 'yet not my will' the heart of faithful prayer?"],
    },
    448: {
        "historical_context": "<p>Jesus attended a wedding in Cana of Galilee with His mother and early disciples. Jewish weddings were major community events, and running out of wine brought shame on the host family.</p>",
        "preceding_narrative": "<p>When wine ran short, Mary told Jesus the problem. He responded that His hour had not yet come, yet Mary told the servants to do whatever He instructed — leading to His first miraculous sign.</p>",
        "discussion_questions": ["What does Mary's instruction to servants teach about responding to Jesus?", "How do ordinary needs become settings for God's glory?", "When have you needed simply to do what Jesus says?"],
    },
    449: {
        "historical_context": "<p>Jesus ministered in Jerusalem during a festival, healing a disabled man at the pool of Bethesda. Religious leaders increasingly challenged His authority and His claims about God.</p>",
        "preceding_narrative": "<p>Jesus healed the man on the Sabbath, provoking opposition. He then taught that the Son gives life and judges, and declared that hearing His word and believing the Father grants eternal life and escape from judgment.</p>",
        "discussion_questions": ["What does crossing from death to life mean spiritually?", "How is believing God's word about Jesus different from mere agreement?", "Why does Jesus link eternal life to hearing and believing now?"],
    },
    450: {
        "historical_context": "<p>Many Jews in Jerusalem believed in Jesus during the Feast of Tabernacles, but opposition from leaders was intensifying. Jesus addressed those whose faith needed deepening beyond initial enthusiasm.</p>",
        "preceding_narrative": "<p>Jesus taught that He is the light of the world and that truth sets free. To those who believed Him, He said holding to His teaching — continuing in it — marks genuine discipleship.</p>",
        "discussion_questions": ["How is continuing in Jesus' teaching different from a one-time decision?", "What does it mean to 'hold to' His teaching when culture disagrees?", "Where do you need perseverance, not just profession?"],
    },
    451: {
        "historical_context": "<p>Jesus healed a man born blind on the Sabbath, provoking a lengthy investigation by Pharisees. The healed man's testimony became a public challenge to religious leaders who refused to see.</p>",
        "preceding_narrative": "<p>After the man was expelled from the synagogue for defending Jesus, Jesus found him and revealed Himself as the Son of Man. He then taught that He must do the Father's works while daylight remained — the time for mission was limited.</p>",
        "discussion_questions": ["What 'work' has God given you to do while it is still day?", "How does urgency change the way you serve?", "Where do you see spiritual blindness refusing evidence of God's work?"],
    },
    452: {
        "historical_context": "<p>Jesus entered Jerusalem for Passover amid crowds waving palm branches. Greeks sought to see Him, signaling that His mission would reach beyond Israel to the nations.</p>",
        "preceding_narrative": "<p>Jesus spoke of a grain of wheat falling to the ground and dying to bear fruit. He troubled His soul over the coming hour, then declared that when He was lifted up from the earth He would draw all people to Himself.</p>",
        "discussion_questions": ["How does the cross 'draw' people rather than coerce them?", "What does Jesus being 'lifted up' mean for the world?", "How should the cross shape evangelism and humility?"],
    },
    453: {
        "historical_context": "<p>At the Last Supper, Jesus washed disciples' feet and foretold betrayal and denial. He gave a new command as He prepared them for life together after His departure.</p>",
        "preceding_narrative": "<p>Jesus loved His own to the end, washing feet and teaching servanthood. After Judas left, He gave disciples a new command: love one another as He loved them — the mark by which the world would recognize them.</p>",
        "discussion_questions": ["How does love among believers witness to the world?", "What does loving 'as I have loved you' require beyond sentiment?", "Where does your church community need this love most visibly?"],
    },
    454: {
        "historical_context": "<p>John 17 records Jesus' high priestly prayer before Gethsemane and the cross. He prayed for Himself, His disciples, and all who would believe through their message.</p>",
        "preceding_narrative": "<p>Jesus asked the Father to glorify Him so He might glorify the Father. He prayed for disciples remaining in the world, then asked the Father to sanctify them by the truth — declaring that God's word is truth.</p>",
        "discussion_questions": ["How does God's word sanctify believers in daily life?", "What does it mean that truth is not only propositional but personal in God?", "How can you pursue holiness through Scripture this week?"],
    },
    455: {
        "historical_context": "<p>Jesus stood trial before Pilate, the Roman governor of Judea. Jewish leaders sought a political charge that would justify execution under Roman law.</p>",
        "preceding_narrative": "<p>Pilate questioned whether Jesus was king of the Jews. Jesus answered that His kingdom is not of this world and that He came to testify to the truth — and everyone on the side of truth listens to Him.</p>",
        "discussion_questions": ["What does it mean that Jesus' kingdom is not from this world?", "How does Pilate's question about truth still resonate today?", "What does listening to Jesus reveal about being 'on the side of truth'?"],
    },
    456: {
        "historical_context": "<p>After the resurrection, Jesus appeared to disciples by the Sea of Galilee. Peter had denied Jesus three times and likely wondered whether he still had a place among the apostles.</p>",
        "preceding_narrative": "<p>Jesus provided a miraculous catch of fish and shared breakfast with disciples. He restored Peter with three questions about love, commissioning him to feed His lambs and sheep after each confession.</p>",
        "discussion_questions": ["How does Jesus restore leaders who have failed?", "What does feeding His lambs look like in your context?", "Why does Jesus tie service to love rather than guilt?"],
    },
    457: {
        "historical_context": "<p>Peter and John healed a lame man at the temple gate called Beautiful, drawing a crowd in Jerusalem. The early church proclaimed Christ boldly in the city where Jesus had been crucified weeks earlier.</p>",
        "preceding_narrative": "<p>After the healing, Peter preached to astonished onlookers, explaining that the God of Abraham had glorified Jesus. He called Israel to repent so sins might be wiped out and times of refreshing come from the Lord.</p>",
        "discussion_questions": ["What does repentance have to do with experiencing spiritual refreshment?", "How does Peter's sermon connect Jesus to Israel's story?", "Where do you need turning toward God to bring renewal?"],
    },
    458: {
        "historical_context": "<p>The early church grew rapidly in Jerusalem, creating practical needs among widows and tension over food distribution. Greek-speaking Jews felt their widows were overlooked compared to Hebrew-speaking widows.</p>",
        "preceding_narrative": "<p>Apostles asked the church to choose seven men full of Spirit and wisdom to oversee daily service. The Twelve said they would devote themselves to prayer and the ministry of the word while others handled tables.</p>",
        "discussion_questions": ["Why are prayer and the word ministry priorities for church leaders?", "How does serving tables and teaching work together in a healthy church?", "What practical needs in your church require Spirit-filled servants?"],
    },
    459: {
        "historical_context": "<p>Philip preached in Samaria, and an angel directed him toward Gaza. Ethiopia was a major kingdom south of Egypt; its official held high treasury office under the queen called Candace.</p>",
        "preceding_narrative": "<p>Philip met an Ethiopian eunuch returning from worship in Jerusalem. The Spirit told Philip to approach the chariot, where the man was reading Isaiah's prophecy about the suffering servant.</p>",
        "discussion_questions": ["How does God prepare seekers through Scripture before believers arrive?", "Who might be 'reading Isaiah' in your life waiting for explanation?", "What barriers did Philip cross to share the gospel?"],
    },
    460: {
        "historical_context": "<p>After persecution scattered believers from Jerusalem, the gospel spread beyond Judea. Antioch in Syria became a major center where Jewish and Gentile believers mingled in a diverse Roman city.</p>",
        "preceding_narrative": "<p>Barnabas brought Saul to Antioch after believers first preached to Greeks there. For a whole year they met with the church and taught great numbers, and disciples were first called Christians in Antioch.</p>",
        "discussion_questions": ["What does the name 'Christian' imply about public identity?", "How did persecution unexpectedly spread the gospel?", "What would it mean for your community to recognize you as Christ's follower?"],
    },
    461: {
        "historical_context": "<p>Paul and Barnabas preached in Pisidian Antioch on their first missionary journey around AD 46–48. They addressed both Jews and Gentile God-fearers in the synagogue.</p>",
        "preceding_narrative": "<p>Paul recounted Israel's history from Egypt to David, then proclaimed Jesus' death and resurrection. He declared forgiveness of sins through Jesus — a message unavailable through the law of Moses alone.</p>",
        "discussion_questions": ["Why is forgiveness through Jesus central to the gospel?", "How does Paul's sermon connect Old Testament history to Christ?", "Who needs to hear that forgiveness is proclaimed to them?"],
    },
    462: {
        "historical_context": "<p>Paul addressed the Areopagus in Athens, a city known for philosophy and idolatry. He quoted Greek poets while proclaiming the unknown God revealed in Christ and resurrection.</p>",
        "preceding_narrative": "<p>Paul observed Athens full of idols and reasoned in the synagogue and marketplace. Before the council, he declared God made the world and gives life, quoting poets who said 'In him we live and move and have our being.'</p>",
        "discussion_questions": ["How can believers engage culture without abandoning the gospel?", "What does it mean that we live and move in God?", "Where do you see people worshiping without knowing the true God?"],
    },
    463: {
        "historical_context": "<p>Paul wrote Romans around AD 57 to believers he had not yet met in the capital of the empire. He addressed both Jewish and Gentile Christians and the question of God's righteousness.</p>",
        "preceding_narrative": "<p>Romans 2 warns those who judge others while practicing the same sins. Paul asks whether they presume on God's kindness, explaining that His kindness is meant to lead people to repentance rather than complacency.</p>",
        "discussion_questions": ["How has God's kindness led you toward repentance?", "Why is judging others while sinning equally dangerous?", "Where might you be mistaking patience for approval?"],
    },
    464: {
        "historical_context": "<p>Romans 7 explores the believer's struggle with sin after coming to know God's law through faith. Paul wrote to a church navigating identity in Christ within a pagan imperial city.</p>",
        "preceding_narrative": "<p>Paul described the law as holy but sin seizing opportunity through the commandment. He confessed the war within: desiring good yet failing to do it, finding evil present when he wanted to obey.</p>",
        "discussion_questions": ["How does Paul's honesty about inner conflict encourage you?", "What is the difference between struggling with sin and excusing it?", "Why is admitting 'I cannot carry it out' a step toward grace?"],
    },
    465: {
        "historical_context": "<p>Romans 9–11 wrestles with Israel's place in God's plan after many Jews rejected Christ while Gentiles believed. Paul, a Jewish apostle to Gentiles, grieved over his kinsmen.</p>",
        "preceding_narrative": "<p>Paul explored election, mercy, and the mystery of Israel's partial hardening. After dense theological argument, he burst into doxology over the depth of God's wisdom and unsearchable judgments.</p>",
        "discussion_questions": ["Why is worship a fitting response to unanswered theological questions?", "How does God's wisdom exceed human systems of fairness?", "When has mystery led you to praise rather than frustration?"],
    },
    466: {
        "historical_context": "<p>Romans 12 begins the practical section of the letter, calling believers to live out mercy received in Christ. The church in Rome faced pressure from both society and internal diversity.</p>",
        "preceding_narrative": "<p>Paul urged presenting bodies as living sacrifices and being transformed by renewed minds. He described the body of Christ with varied gifts, then listed marks of genuine love including joy, patience, and faithful prayer.</p>",
        "discussion_questions": ["How do hope, patience, and prayer work together in hardship?", "Which of these three do you most need to grow in?", "What does joyful hope look like when circumstances are not joyful?"],
    },
    467: {
        "historical_context": "<p>Romans 13 addresses Christian life under governing authorities in the Roman Empire. Paul also taught love as the fulfillment of Torah in community relationships.</p>",
        "preceding_narrative": "<p>Paul commanded submission to authorities as instituted by God and payment of taxes and honor. He then summarized commandments about neighbors: love does no harm, therefore love fulfills the law.</p>",
        "discussion_questions": ["How does loving your neighbor fulfill God's moral law?", "What does 'do no harm' require in speech and action?", "Where is love a better guide than rule-keeping alone?"],
    },
    468: {
        "historical_context": "<p>Romans 14 addresses disputes among believers over food laws and sacred days — issues dividing Jewish and Gentile Christians. Paul urged acceptance without destroying one another.</p>",
        "preceding_narrative": "<p>Paul warned against judging brothers over disputable matters and reminded them all will stand before God's judgment seat. He urged them to stop passing judgment and avoid placing stumbling blocks before others.</p>",
        "discussion_questions": ["What 'disputable matters' divide believers today?", "How can conviction and freedom coexist without harming others?", "What stumbling blocks might you unknowingly place before others?"],
    },
    469: {
        "historical_context": "<p>Paul closed Romans with greetings, warnings, and doxology after his longest letter. He wrote amid plans to visit Rome and continue mission to Spain.</p>",
        "preceding_narrative": "<p>Paul commended Phoebe, greeted many believers, warned against those who cause divisions, and praised the God who strengthens the gospel. He closed with assurance that the God of peace would soon crush Satan under their feet.</p>",
        "discussion_questions": ["What does crushing Satan under your feet promise for daily spiritual battle?", "How does God's peace relate to victory over evil?", "Where do you need assurance that evil will not have the final word?"],
    },
    470: {
        "historical_context": "<p>Paul wrote 1 Corinthians to a gifted but divided church in a prosperous, immoral Greek city. Corinth's culture prized wisdom, status, and freedom — pressures that distorted church life.</p>",
        "preceding_narrative": "<p>Paul addressed quarrels over leaders and the folly of worldly wisdom. He reminded believers that God's Spirit dwells among them communally — they together are God's temple where His Spirit lives.</p>",
        "discussion_questions": ["What does it mean that the church community is God's temple?", "How should that truth affect how you treat other believers?", "Where does division damage God's dwelling place among His people?"],
    },
    471: {
        "historical_context": "<p>Corinth's culture treated the body as property for pleasure and social advancement. Paul countered with resurrection hope and the dignity of the body in God's design.</p>",
        "preceding_narrative": "<p>Paul confronted sexual immorality and lawsuits among believers. He argued that bodies joined to Christ must not be joined to prostitutes, declaring each believer's body a temple of the Holy Spirit received from God.</p>",
        "discussion_questions": ["How does 'you are not your own' reshape choices about your body?", "What does honoring the Spirit's temple mean practically?", "Where does culture treat the body as disposable or merely personal?"],
    },
    472: {
        "historical_context": "<p>Corinth hosted the Isthmian Games, athletic contests familiar to Paul's readers. He used racing imagery to teach disciplined Christian living in a city that celebrated public competition.</p>",
        "preceding_narrative": "<p>Paul defended his apostolic freedom and refusal to be a burden, then described becoming all things to all people to save some. He turned to athletes who train strictly for a perishable wreath as a model for spiritual discipline.</p>",
        "discussion_questions": ["What would running to win look like in your faith this month?", "How is spiritual discipline different from earning salvation?", "What distractions keep you from running with purpose?"],
    },
    473: {
        "historical_context": "<p>Paul addressed divisions over spiritual leaders and proper worship in Corinth. Some boasted in Apollos or Paul rather than Christ, corrupting the church's unity.</p>",
        "preceding_narrative": "<p>Paul urged imitation of him as he imitated Christ, recalling his humble founding of the church. He sent Timothy to remind them of his ways in Christ, calling them to follow his example as he followed Jesus.</p>",
        "discussion_questions": ["Who models Christ for you in a way you can imitate?", "What is healthy versus unhealthy imitation of Christian leaders?", "How does following Christ change the way you lead others?"],
    },
    474: {
        "historical_context": "<p>1 Corinthians 13 is the famous love chapter, placed between teaching on spiritual gifts. Corinthian believers prized spectacular gifts while neglecting love that builds the community.</p>",
        "preceding_narrative": "<p>Paul showed love's superiority to tongues, prophecy, knowledge, and sacrifice. He described love's character, then contrasted childhood seeing with mature face-to-face knowledge that awaits the eternal state.</p>",
        "discussion_questions": ["How does 'seeing through a glass darkly' describe our current knowledge?", "What will it mean to know fully when we see God face to face?", "How does eternal hope change how you handle partial understanding now?"],
    },
    475: {
        "historical_context": "<p>Paul defended the resurrection in 1 Corinthians 15, a chapter crucial in a culture skeptical of bodily resurrection. Some Corinthians denied resurrection while still claiming Christian faith.</p>",
        "preceding_narrative": "<p>Paul rehearsed eyewitness testimony to Christ's resurrection and explained its necessity for faith and hope. He warned against being misled by bad company, quoting a proverb about corruption of character through close associations.</p>",
        "discussion_questions": ["How do friendships shape your character over time?", "What does 'bad company' look like for believers seeking holiness?", "How can you pursue good influences without arrogance?"],
    },
    476: {
        "historical_context": "<p>Paul wrote 2 Corinthians from Macedonia after painful conflict with the church. He had confronted them sharply and now wrote with relief after Titus brought news of repentance.</p>",
        "preceding_narrative": "<p>Paul opened with praise to the God of all comfort who comforts believers in trouble so they can comfort others. He described sharing abundantly in Christ's sufferings along with comfort through Christ.</p>",
        "discussion_questions": ["How has God comforted you in a way you can share with others?", "Why does Paul link suffering and comfort rather than replacing one with the other?", "Who needs the comfort you have received?"],
    },
    477: {
        "historical_context": "<p>Paul contrasted the new covenant ministry with the glory of Moses' law. His opponents in Corinth questioned his apostolic credentials and favored flashy teachers.</p>",
        "preceding_narrative": "<p>Paul described the ministry of the Spirit as more glorious than the letter that kills. He taught that believers with unveiled faces behold the Lord's glory and are transformed into His image by the Spirit.</p>",
        "discussion_questions": ["What does being transformed into Christ's image look like weekly?", "How does beholding Christ's glory change behavior?", "Where do you need the Spirit's transforming work most?"],
    },
    478: {
        "historical_context": "<p>Paul defended the gospel ministry while suffering physically — likely including beatings, hardships, and the 'thorn' he later mentions. Corinthian culture admired strength and eloquence.</p>",
        "preceding_narrative": "<p>Paul described carrying the death of Jesus in his body so Christ's life might be revealed. Though outwardly wasting away, inwardly he was being renewed day by day by an eternal weight of glory.</p>",
        "discussion_questions": ["How can inner renewal coexist with outer difficulty?", "What does not losing heart mean when the body ages or suffers?", "How does eternal perspective change daily discouragement?"],
    },
    479: {
        "historical_context": "<p>Paul urged the Corinthians to excel in the grace of giving, using Macedonian churches as examples of generosity amid poverty. The collection supported believers in Jerusalem facing hardship.</p>",
        "preceding_narrative": "<p>Paul described Macedonian believers giving beyond their ability during severe trial. He then pointed to Christ's grace: though rich, He became poor so believers through His poverty might become rich.</p>",
        "discussion_questions": ["How does Christ's incarnation inspire generous living?", "What does becoming 'rich' through Christ's poverty mean spiritually?", "Where is God calling you to excel in giving?"],
    },
    480: {
        "historical_context": "<p>Paul closed 2 Corinthians with warnings, appeals, and final greetings after defending his ministry throughout the letter. He prepared to visit them a third time with accountability.</p>",
        "preceding_narrative": "<p>Paul called for self-examination and rejoiced in weakness when Christ's power rested on him. He ended noting that Christ was crucified in weakness yet lives by God's power — the pattern for believers who share His weakness and life.</p>",
        "discussion_questions": ["How is God's power perfected in human weakness?", "Why does Paul highlight the cross's weakness alongside resurrection power?", "Where might weakness be an opening for Christ's strength?"],
    },
    481: {
        "historical_context": "<p>Paul wrote Galatians to churches in southern Galatia facing teachers who required Gentiles to be circumcised and keep Jewish law for full acceptance. The letter defends justification by faith alone.</p>",
        "preceding_narrative": "<p>Paul expressed astonishment that Galatians were deserting the gospel so quickly. After defending his apostolic call, he opened the body of the letter with the standard greeting: grace and peace from God the Father and the Lord Jesus Christ.</p>",
        "discussion_questions": ["Why are grace and peace linked rather than separate blessings?", "How does losing the gospel quickly happen in subtle ways today?", "What 'other gospels' add requirements to faith in Christ?"],
    },
    482: {
        "historical_context": "<p>Galatians addresses whether Gentile believers must become ethnically Jewish to belong to God's people. First-century culture was deeply divided by ethnicity, status, and gender roles.</p>",
        "preceding_narrative": "<p>Paul argued that believers are children of promise, not slaves under law. He stated that in Christ there is neither Jew nor Gentile, slave nor free, male nor female — all are one in Christ Jesus.</p>",
        "discussion_questions": ["What divisions does Christ's unity challenge in your church or society?", "How is oneness in Christ different from uniformity?", "Who is easy to exclude that God calls fully included?"],
    },
    483: {
        "historical_context": "<p>Paul explained that the law served as a guardian until Christ came. God's promises to Abraham preceded and supersede the law given centuries later at Sinai.</p>",
        "preceding_narrative": "<p>Paul contrasted slavery under elemental forces with freedom as God's children and heirs. He declared that when the set time fully came, God sent His Son, born of a woman, born under law to redeem those under law.</p>",
        "discussion_questions": ["Why does God's timing — 'when the set time had fully come' — matter for salvation history?", "What does Christ being born under law accomplish for believers?", "How does the incarnation fulfill promises made long before?"],
    },
    484: {
        "historical_context": "<p>Galatians 5 contrasts freedom in Christ with fleshly living and legalism. Paul warned that requiring circumcision cut believers off from grace and obliged them to keep the whole law.</p>",
        "preceding_narrative": "<p>Paul urged walking by the Spirit rather than gratifying fleshly desires. He listed works of the flesh, then described the fruit of the Spirit — love, joy, peace, forbearance, kindness, goodness, and faithfulness.</p>",
        "discussion_questions": ["Which fruit of the Spirit do you most want to grow in?", "How is fruit different from manufactured behavior?", "What fleshly habits compete with the Spirit's work in you?"],
    },
    485: {
        "historical_context": "<p>Paul wrote Ephesians to believers in a major city devoted to Artemis worship and imperial pride. He described the church as Christ's body and called households to live out the gospel.</p>",
        "preceding_narrative": "<p>After teaching mutual submission in the Spirit, Paul addressed wives and husbands. He called wives to submit to husbands as to the Lord, grounding marriage in Christ's relationship with the church.</p>",
        "discussion_questions": ["How does Christ's love for the church define Christian marriage?", "What does mutual submission look like alongside specific roles?", "How can this passage be taught without reinforcing abuse or inequality?"],
    },
    486: {
        "historical_context": "<p>Ephesians 6 concludes household instruction with warfare imagery. Believers in Ephesus lived in a city where spiritual powers were visibly honored through Artemis cult and imperial religion.</p>",
        "preceding_narrative": "<p>Paul addressed children and fathers, then slaves and masters with gospel dignity. He urged believers to put on God's full armor because the struggle is not against flesh and blood but spiritual forces of evil.</p>",
        "discussion_questions": ["How does recognizing spiritual warfare change how you face conflict?", "What does putting on God's armor look like practically each day?", "Why is it dangerous to reduce every struggle to human opponents alone?"],
    },
    487: {
        "historical_context": "<p>Paul wrote Philippians from prison, likely in Rome around AD 60–62. The Philippian church supported him financially and faced pressure to boast in status rather than the cross.</p>",
        "preceding_narrative": "<p>Paul urged humility and unity, quoting an early hymn about Christ who, though in very nature God, did not grasp equality with God but humbled Himself to death on a cross — obedient even unto death.</p>",
        "discussion_questions": ["How does Christ's humility redefine greatness for you?", "What does obedience 'to death' teach about discipleship?", "Where is God calling you to humble yourself rather than assert rights?"],
    },
    488: {
        "historical_context": "<p>Paul had been a zealous Pharisee who counted pedigree, law-keeping, and zeal as gain. In Philippians 3 he revalues everything in light of knowing Christ.</p>",
        "preceding_narrative": "<p>Paul listed former credentials as loss compared to knowing Christ. He pressed on, not considering himself yet perfected, straining toward the heavenly goal for which God called him in Christ Jesus.</p>",
        "discussion_questions": ["What past achievements might you be tempted to trust in?", "How is pressing toward the goal different from living in the past?", "What does God call you forward toward in Christ?"],
    },
    489: {
        "historical_context": "<p>Philippians 4 closes a letter written from chains yet overflowing with joy and contentment. The church faced anxiety and needed peace amid opposition to the gospel.</p>",
        "preceding_narrative": "<p>Paul urged rejoicing, gentleness, prayer with thanksgiving, and thinking on what is true and noble. He thanked the Philippians for sharing in his trouble, then assured them God would meet all their needs from His riches in glory in Christ.</p>",
        "discussion_questions": ["How does God's provision relate to His riches rather than your circumstances?", "What needs are you trusting God to meet?", "How do prayer and thanksgiving prepare the heart for contentment?"],
    },
    490: {
        "historical_context": "<p>Paul wrote Colossians to a church influenced by teachings that diminished Christ and promoted secret knowledge and harsh asceticism. Colossae was a smaller city in Phrygia within the Roman world.</p>",
        "preceding_narrative": "<p>Paul thanked God for the Colossians' faith and prayed for knowledge of God's will. He declared Christ's supremacy, saying all things in heaven and on earth were created through Him and for Him.</p>",
        "discussion_questions": ["Why does creation 'through him and for him' matter for daily life?", "How does Christ's supremacy answer false teachings that minimize Him?", "What in creation most reminds you that all things belong to Christ?"],
    },
    491: {
        "historical_context": "<p>False teachers in Colossae implied Christ was insufficient and believers needed additional spiritual experiences. Paul countered with the fullness of deity dwelling in Christ bodily.</p>",
        "preceding_narrative": "<p>Paul warned against being taken captive by hollow philosophy and human tradition. He affirmed that in Christ all the fullness of Deity lives in bodily form, and believers are filled in Him who is head over every power.</p>",
        "discussion_questions": ["What philosophies today compete with the sufficiency of Christ?", "Why is Christ's bodily humanity essential to the gospel?", "How does being 'filled in him' change how you seek spiritual growth?"],
    },
    492: {
        "historical_context": "<p>Colossians 3 applies Christ's supremacy to everyday ethics — putting off old self and putting on new life. The church needed practical instruction rooted in resurrection identity.</p>",
        "preceding_narrative": "<p>Paul told believers to set hearts and minds on things above where Christ is seated. He listed sins to put to death, then urged God's chosen, holy, beloved people to clothe themselves with compassion, kindness, humility, gentleness, and patience.</p>",
        "discussion_questions": ["Which virtue in this list is hardest for you to 'put on'?", "How does being 'dearly loved' motivate holiness?", "What old clothing do you still need to take off?"],
    },
    493: {
        "historical_context": "<p>Paul closed Colossians with practical instructions for prayer and witness in a pluralistic culture. Believers needed wisdom in conversations with outsiders.</p>",
        "preceding_narrative": "<p>Paul urged devotion to prayer, watchfulness, and thanksgiving while asking for open doors for the gospel. He then told the Colossians to let speech always be gracious, seasoned with salt, to know how to answer everyone.</p>",
        "discussion_questions": ["What does gracious, salty speech sound like in difficult conversations?", "How can you answer others without compromise or cruelty?", "Who in your life needs a wise, gracious response from you?"],
    },
    494: {
        "historical_context": "<p>Paul wrote 1 Thessalonians to a young church he founded on his second missionary journey around AD 49–50. Thessalonica was a major Roman city where believers faced persecution after Paul's hurried departure.</p>",
        "preceding_narrative": "<p>Paul thanked God for their faith, love, and hope, recalling how they turned from idols to serve the living God. He noted they waited for God's Son from heaven, whom God raised from the dead — Jesus who rescues from coming wrath.</p>",
        "discussion_questions": ["How does waiting for Jesus shape daily obedience?", "What does rescue from wrath teach about salvation?", "How did turning from idols mark their conversion — and yours?"],
    },
    495: {
        "historical_context": "<p>Paul sent Timothy to strengthen the Thessalonian church amid affliction. He longed to see them and encouraged endurance in holiness as they awaited Christ's return.</p>",
        "preceding_narrative": "<p>Paul described Timothy's good report of their faith and love. He prayed night and day to see them again, asking God to strengthen their hearts blameless and holy before the Father when Jesus comes with all His holy ones.</p>",
        "discussion_questions": ["What does blameless holiness look like in a persecuted church?", "How does Christ's coming motivate present faithfulness?", "Who strengthens your heart when you face pressure?"],
    },
    496: {
        "historical_context": "<p>Thessalonian believers lived in a city full of pagan immorality. Paul taught them about sexual ethics, work, and life together as they awaited the Lord's return.</p>",
        "preceding_narrative": "<p>Paul urged them to live in a way that pleases God, recalling commands he gave by the Lord Jesus. He stated plainly that God's will is their sanctification — specifically avoiding sexual immorality.</p>",
        "discussion_questions": ["Why does Paul connect sanctification with sexual purity?", "How is knowing God's will comforting rather than burdensome?", "What cultural pressures make sanctification difficult today?"],
    },
    497: {
        "historical_context": "<p>1 Thessalonians 4–5 teaches about Christ's return and Christian readiness. Some believers feared they had missed the resurrection hope; others lived carelessly.</p>",
        "preceding_narrative": "<p>Paul comforted them about believers who died, assuring they would meet the Lord. He turned to times and seasons, urging sober readiness because God did not appoint them to wrath but to receive salvation through Jesus.</p>",
        "discussion_questions": ["How does salvation through Jesus replace fear of wrath?", "What does readiness for Christ's return look like practically?", "How can end-times hope produce peace rather than anxiety?"],
    },
    498: {
        "historical_context": "<p>2 Thessalonians addresses misunderstanding about the day of the Lord and disorderly behavior in the church. Some thought the Lord had already returned; others refused to work.</p>",
        "preceding_narrative": "<p>Paul corrected false teaching about the day of the Lord and urged disciplined living. He warned against idleness, then prayed that the Lord of peace Himself would give peace always in every way as the letter closed.</p>",
        "discussion_questions": ["Where do you need the Lord of peace to rule instead of chaos?", "How does false teaching about the end disturb peace?", "What practices help you receive God's peace in every circumstance?"],
    },
    499: {
        "historical_context": "<p>2 Thessalonians 2 warns against deception before the day of the Lord and encourages steadfastness amid persecution. Paul wrote to stabilize a church shaken by false reports.</p>",
        "preceding_narrative": "<p>Paul described rebellion and the man of lawlessness before Christ's coming. He thanked God who chose them for salvation through the Spirit, then prayed God would encourage their hearts and strengthen them in every good deed and word.</p>",
        "discussion_questions": ["How does God strengthen believers for both deeds and words?", "What good deed and word is God calling you toward?", "Why are encouragement and strength needed together in trials?"],
    },
    500: {
        "historical_context": "<p>Paul wrote 1 Timothy to guide church order and sound teaching in Ephesus. False teachers promoted myths, genealogies, and misuse of the law around AD 62–64.</p>",
        "preceding_narrative": "<p>Paul urged Timothy to stay in Ephesus and correct false teaching. He described qualifications for overseers and deacons, then quoted a widely recognized confession about the mystery of godliness centered on Christ's incarnation and glorification.</p>",
        "discussion_questions": ["Why is the incarnation central to 'the mystery of godliness'?", "How does Christ's vindication and proclamation among nations shape mission?", "What false teachings today distract from the core gospel mystery?"],
    },
})

def build():
    texts = json.loads(TEXTS_PATH.read_text())
    lessons = []
    missing = []
    for lesson in range(392, 501):
        bid, ch, v = REFS[lesson]
        entry = {
            "lesson": lesson,
            "book_id": bid,
            "chapter": ch,
            "verse_start": v,
            "verse_end": v,
            "text": texts.get(str(lesson), ""),
        }
        if lesson in ENRICHMENT:
            entry.update(ENRICHMENT[lesson])
        else:
            missing.append(lesson)
        lessons.append(entry)

    if missing:
        raise SystemExit(f"Missing enrichment for lessons: {missing}")
    if len(lessons) != 109:
        raise SystemExit(f"Expected 109 lessons, got {len(lessons)}")
    if any(not e["text"] for e in lessons):
        raise SystemExit("Some lessons missing NIV text")

    OUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUT_PATH.write_text(json.dumps(lessons, indent=2, ensure_ascii=False) + "\n")
    print(f"Wrote {len(lessons)} lessons to {OUT_PATH}")

if __name__ == "__main__":
    build()
