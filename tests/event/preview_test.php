<?php
/**
 *
 * Topic Image Preview. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace vse\topicimagepreview\tests\event;

class preview_test extends base
{
	public function getEventListener()
	{
		return new \vse\topicimagepreview\event\preview(
			$this->config,
			new \vse\topicimagepreview\event\helper(
				$this->auth,
				$this->config,
				$this->db,
				$this->user
			),
			$this->template
		);
	}

	public function test_construct()
	{
		self::assertInstanceOf('\Symfony\Component\EventDispatcher\EventSubscriberInterface', $this->getEventListener());
	}

	public function test_getSubscribedEvents()
	{
		self::assertEquals([
			'core.page_footer',
			'core.viewforum_modify_topics_data',
			'core.viewforum_modify_topicrow',
			'core.search_modify_rowset',
			'core.search_modify_tpl_ary',
			'vse.similartopics.modify_rowset',
			'vse.similartopics.modify_topicrow',
		], array_keys(\vse\topicimagepreview\event\preview::getSubscribedEvents()));
	}

	public function test_init_tpl_vars()
	{
		$this->template->expects(self::once())
			->method('assign_vars')
			->with([
				'S_TOPIC_IMAGE_PREVIEW'		=> false,
				'TOPIC_IMAGE_PREVIEW_DIM'	=> $this->config['vse_tip_dim'],
			]);

		$listener = $this->getEventListener();

		$listener->init_tpl_vars();
	}

	public function preview_factory_test_data()
	{

		$image = [
			1 => 'http://localhost/img1.gif',
			2 => 'http://localhost/img2.gif',
			3 => 'http://localhost/img3.gif',
			4 => 'http://localhost/img4.gif',
		];

		$post = [
			2 => '<r><IMG src="http://localhost/img1.gif"><s>[img]</s><URL url="http://localhost/img1.gif">http://localhost/img1.gif</URL><e>[/img]</e></IMG></r>',
			4 => '<r><IMG src="http://localhost/img2.gif"><s>[img]</s><URL url="http://localhost/img2.gif">http://localhost/img2.gif</URL><e>[/img]</e></IMG></r>',
			5 => '<r><IMG src="http://localhost/img3.gif"><s>[img]</s><URL url="http://localhost/img3.gif">http://localhost/img3.gif</URL><e>[/img]</e></IMG><IMG src="http://localhost/img4.gif"><s>[img]</s><URL url="http://localhost/img4.gif">http://localhost/img4.gif</URL><e>[/img]</e></IMG></r>',
		];

		return [
			[
				// Check all topics, user does not allow images so results should be null
				['vse_tip_new' => 1, 'vse_tip_num' => 3, 'user_vse_tip' => 0, 'f_vse_tip' => 1],
				null,
				[1 => [], 2 => [], 3 => []],
				[1 => null, 2 => null, 3 => null,],
				[1 => null, 2 => null, 3 => null],
			],
			[
				// Check all topics, forum does not allow images so results should be null
				['vse_tip_new' => 1, 'vse_tip_num' => 3, 'user_vse_tip' => 1, 'f_vse_tip' => 0],
				null,
				[1 => [], 2 => [], 3 => []],
				[1 => null, 2 => null, 3 => null],
				[1 => null, 2 => null, 3 => null],
			],
			[
				// Check all topics, topic 1 has an image, topic 2 has 2 images
				['vse_tip_new' => 1, 'vse_tip_num' => 3, 'user_vse_tip' => 1, 'f_vse_tip' => 1],
				null,
				[1 => [], 2 => [], 3 => []],
				[1 => $post[2], 2 => $post[5], 3 => null],
				[1 => [$image[1]], 2 => [$image[3], $image[4]], 3 => null],
			],
			[
				// Check 1 topic, which contains 1 posted image
				['vse_tip_new' => 1, 'vse_tip_num' => 3, 'user_vse_tip' => 1, 'f_vse_tip' => 1],
				null,
				[1 => []],
				[1 => $post[2]],
				[1 => [$image[1]]],
			],
			[
				// Check 2 topics, which has 2 posts with images, get up to 3 images from the newest post
				['vse_tip_new' => 1, 'vse_tip_num' => 3, 'user_vse_tip' => 1, 'f_vse_tip' => 1],
				[2, 3],
				[2 => [], 3 => []],
				[2 => $post[5], 3 => null],
				[2 => [$image[3], $image[4]], 3 => null],
			],
			[
				// Check 2 topics, which has 2 posts with images, get only show 1 image from the newest post
				['vse_tip_new' => 1, 'vse_tip_num' => 1, 'user_vse_tip' => 1, 'f_vse_tip' => 1],
				[2, 3],
				[2 => [], 3 => []],
				[2 => $post[5], 3 => null],
				[2 => [$image[3]], 3 => null],
			],
			[
				// Check 2 topics, which has 2 posts with images, but only show 1 image from the oldest post
				['vse_tip_new' => 0, 'vse_tip_num' => 1, 'user_vse_tip' => 1, 'f_vse_tip' => 1],
				[2, 3],
				[2 => [], 3 => []],
				[2 => $post[4], 3 => null],
				[2 => [$image[2]], 3 => null],
			],
		];
	}

	/**
	 * @dataProvider preview_factory_test_data
	 */
	public function test_preview_factory($configs, $topic_list, $rowset, $expected_row, $expected_img)
	{
		foreach ($configs as $key => $config)
		{
			if ($key === 'f_vse_tip')
			{
				$this->auth->expects($configs['user_vse_tip'] ? self::atLeastOnce() : self::never())
					->method('acl_get')
					->with($key)
					->willReturn($config);
				continue;
			}

			if ($key === 'user_vse_tip')
			{
				$this->user->data['user_vse_tip'] = $config;
				continue;
			}

			$this->config[$key] = $config;
		}

		// Add a forum_id to rowsets
		foreach ($rowset as $id => $row)
		{
			$rowset[$id]['forum_id'] = 2;
		}

		$listener = $this->getEventListener();

		// Test the update_row_data event
		$event_data = ['rowset', 'topic_list'];
		$event = new \phpbb\event\data(compact($event_data));

		$listener->viewforum_row($event);

		$event_data = $event->get_data_filtered($event_data);

		foreach ($event_data['rowset'] as $topic_id => $topic_data)
		{
			if ($expected_row[$topic_id] !== null)
			{
				self::assertEquals($expected_row[$topic_id], $topic_data['post_text']);
			}

			// Test the update_tpl_data event
			$row = $topic_data;
			$topic_row = [];

			$event_data = ['row', 'topic_row'];
			$event = new \phpbb\event\data(compact($event_data));

			$listener->viewforum_tpl($event);

			$event_data = $event->get_data_filtered($event_data);
			$topic_row = $event_data['topic_row'];

			if ($expected_img[$topic_id] !== null)
			{
				self::assertEquals($expected_img[$topic_id], $topic_row['TOPIC_IMAGES']);
			}
		}
	}

	public function preview_events_data()
	{
		return [
			['viewforum', [], [], true],
			['searchresults', ['show_results' => 'topics'], ['vse_tip_srt' => 1], true],
			['searchresults', ['show_results' => 'topics'], ['vse_tip_srt' => 0], false],
			['searchresults', ['show_results' => 'posts'], ['vse_tip_srt' => 1], false],
			['similartopics', [], ['vse_tip_pst' => 1], true],
			['similartopics', [], ['vse_tip_pst' => 0], false],
		];
	}

	/**
	 * @dataProvider preview_events_data
	 */
	public function test_preview_events($method, $data, $configs, $expected)
	{
		$method_row = "{$method}_row";
		$method_tpl = "{$method}_tpl";

		foreach ($configs as $key => $value)
		{
			$this->config[$key] = $value;
		}

		/** @var \PHPUnit\Framework\MockObject\MockObject|\vse\topicimagepreview\event\helper $helper */
		$helper = $this->createMock('\vse\topicimagepreview\event\helper');

		$helper->expects($expected ? self::once() : self::never())
			->method('update_row_data');

		$helper->expects($expected ? self::atLeastOnce() : self::never())
			->method('update_tpl_data');

		$event = new \phpbb\event\data($data);
		$listener = new \vse\topicimagepreview\event\preview($this->config, $helper, $this->template);
		self::assertNull($listener->$method_row($event));
		self::assertNull($listener->$method_tpl($event));
	}
}
