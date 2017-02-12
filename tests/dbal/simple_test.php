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

class simple_test extends \phpbb_database_test_case
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
				// Check 1 topic, which contains 1 posted image
				array('vse_tip_new' => 1, 'vse_tip_num' => 3),
				null,
				array(
					1 => array(),
				),
				array(
					1 => $post[2],
				),
				$image[1],
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
				"$image[3] $image[4]",
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
				"$image[3]",
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
				"$image[2]",
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
			$this->assertEquals($expected_row[$topic_id], $topic_data['vse_tip_text']);

			if ($expected_row[$topic_id] === null)
			{
				continue;
			}

			// Test the update_tpl_data event
			$row = $topic_data;
			$topic_row = array();

			$event_data = ['row', 'topic_row'];
			$event = new \phpbb\event\data(compact($event_data));

			$listener->update_tpl_data($event);

			$event_data = $event->get_data_filtered($event_data);
			$topic_row = $event_data['topic_row'];

			$this->assertEquals($expected_img, $topic_row['TOPIC_IMAGES']);
		}
	}
}
