<?php
/**
 *
 * Topic Image Preview. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace vse\TopicImagePreview\tests\dbal;

class event_test extends \phpbb_database_test_case
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\language\language */
	protected $language;

	static protected function setup_extensions()
	{
		return array('vse/TopicImagePreview');
	}

	public function getDataSet()
	{
		return $this->createXMLDataSet(__DIR__ . '/fixtures/posts.xml');
	}

	public function setUp()
	{
		global $phpbb_root_path, $phpEx;

		parent::setUp();

		$this->config = new \phpbb\config\config([
			'vse_tip_new' => 1,
			'vse_tip_num' => 3,
			'vse_tip_dim' => 200,
		]);

		$this->db = $this->new_dbal();

		$this->language = new \phpbb\language\language(
			new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx)
		);
	}

	public function getEventListener()
	{
		return new \vse\TopicImagePreview\event\listener(
			$this->config,
			$this->db,
			$this->language
		);
	}

	public function test_construct()
	{
		$this->assertInstanceOf('\Symfony\Component\EventDispatcher\EventSubscriberInterface', $this->getEventListener());
	}

	public function test_getSubscribedEvents()
	{
		$this->assertEquals(array(
			'core.acp_board_config_edit_add',
			'core.viewforum_modify_topics_data',
			'core.viewforum_modify_topicrow',
			'core.search_modify_rowset',
			'core.search_modify_tpl_ary',
			'vse.similartopics.modify_rowset',
			'vse.similartopics.modify_topicrow',
		), array_keys(\vse\TopicImagePreview\event\listener::getSubscribedEvents()));
	}

	public function preview_events_test_data()
	{
		$post = array(
			2 => '<r><IMG src="http://localhost/img1.gif"><s>[img]</s><URL url="http://localhost/img1.gif">http://localhost/img1.gif</URL><e>[/img]</e></IMG></r>',
			4 => '<r><IMG src="http://localhost/img2.gif"><s>[img]</s><URL url="http://localhost/img2.gif">http://localhost/img2.gif</URL><e>[/img]</e></IMG></r>',
			5 => '<r><IMG src="http://localhost/img3.gif"><s>[img]</s><URL url="http://localhost/img3.gif">http://localhost/img3.gif</URL><e>[/img]</e></IMG><IMG src="http://localhost/img4.gif"><s>[img]</s><URL url="http://localhost/img4.gif">http://localhost/img4.gif</URL><e>[/img]</e></IMG></r>',
		);

		$image = array(
			1 => "<img src='http://localhost/img1.gif' style='max-width:200px; max-height:200px;' />",
			2 => "<img src='http://localhost/img2.gif' style='max-width:200px; max-height:200px;' />",
			3 => "<img src='http://localhost/img3.gif' style='max-width:200px; max-height:200px;' />",
			4 => "<img src='http://localhost/img4.gif' style='max-width:200px; max-height:200px;' />",
		);

		return array(

			array(
				// Check all topics, topic 1 has an image, topic 2 has 2 images
				array('vse_tip_new' => 1, 'vse_tip_num' => 3),
				null,
				array(
					1 => array(),
					2 => array(),
					3 => array(),
				),
				array(
					1 => $post[2],
					2 => $post[5],
					3 => null,
				),
				array(
					1 => $image[1],
					2 => "$image[3] $image[4]",
					3 => null,
				),
			),
			array(
				// Check 1 topic, which contains 1 posted image
				array('vse_tip_new' => 1, 'vse_tip_num' => 3),
				null,
				array(
					1 => array(),
				),
				array(
					1 => $post[2],
				),
				array(
					1 => $image[1],
				),
			),
			array(
				// Check 2 topics, which has 2 posts with images, get up to 3 images from the newest post
				array('vse_tip_new' => 1, 'vse_tip_num' => 3),
				array(2, 3),
				array(
					2 => array(),
					3 => array(),
				),
				array(
					2 => $post[5],
					3 => null,
				),
				array(
					2 => "$image[3] $image[4]",
					3 => null,
				),
			),
			array(
				// Check 2 topics, which has 2 posts with images, get only show 1 image from the newest post
				array('vse_tip_new' => 1, 'vse_tip_num' => 1),
				array(2, 3),
				array(
					2 => array(),
					3 => array(),
				),
				array(
					2 => $post[5],
					3 => null,
				),
				array(
					2 => "$image[3]",
					3 => null,
				),
			),
			array(
				// Check 2 topics, which has 2 posts with images, but only show 1 image from the oldest post
				array('vse_tip_new' => 0, 'vse_tip_num' => 1),
				array(2, 3),
				array(
					2 => array(),
					3 => array(),
				),
				array(
					2 => $post[4],
					3 => null,
				),
				array(
					2 => "$image[2]",
					3 => null,
				),
			),
		);
	}

	/**
	 * @dataProvider preview_events_test_data
	 */
	public function test_preview_events($configs, $topic_list, $rowset, $expected_row, $expected_img)
	{
		foreach ($configs as $key => $config)
		{
			$this->config[$key] = $config;
		}

		$listener = $this->getEventListener();

		// Test the update_row_data event
		$event_data = ['rowset', 'topic_list'];
		$event = new \phpbb\event\data(compact($event_data));

		$listener->update_row_data($event);

		$event_data = $event->get_data_filtered($event_data);
		$rowset = $event_data['rowset'];

		foreach ($rowset as $topic_id => $topic_data)
		{
			$this->assertEquals($expected_row[$topic_id], $topic_data['post_text']);

			// Test the update_tpl_data event
			$row = $topic_data;
			$topic_row = array();

			$event_data = ['row', 'topic_row'];
			$event = new \phpbb\event\data(compact($event_data));

			$listener->update_tpl_data($event);

			$event_data = $event->get_data_filtered($event_data);
			$topic_row = $event_data['topic_row'];

			$this->assertEquals($expected_img[$topic_id], $topic_row['TOPIC_IMAGES']);
		}
	}

	/**
	 * Data set for test_update_acp_data
	 *
	 * @return array Array of test data
	 */
	public function update_acp_data_data()
	{
		return array(
			array( // expected config and mode
				   'post',
				   array('vars' => array('legend3' => array())),
				   array('legend_vse_tip', 'vse_tip_new', 'vse_tip_num', 'vse_tip_dim', 'legend3'),
			),
			array( // unexpected mode
				   'foobar',
				   array('vars' => array('legend3' => array())),
				   array('legend3'),
			),
			array( // unexpected config
				   'post',
				   array('vars' => array('foobar' => array())),
				   array('foobar'),
			),
			array( // unexpected config and mode
				   'foobar',
				   array('vars' => array('foobar' => array())),
				   array('foobar'),
			),
		);
	}

	/**
	 * Test the update_acp_data event
	 *
	 * @dataProvider update_acp_data_data
	 */
	public function test_add_googleanalytics_configs($mode, $display_vars, $expected_keys)
	{
		require_once __DIR__ . '/../../../../../includes/functions_acp.php';

		$listener = $this->getEventListener();

		$event_data = array('display_vars', 'mode');
		$event = new \phpbb\event\data(compact($event_data));

		$listener->update_acp_data($event);

		$event_data_after = $event->get_data_filtered($event_data);
		foreach ($event_data as $expected)
		{
			$this->assertArrayHasKey($expected, $event_data_after);
		}
		extract($event_data_after);

		$keys = array_keys($display_vars['vars']);

		$this->assertEquals($expected_keys, $keys);
	}
}
