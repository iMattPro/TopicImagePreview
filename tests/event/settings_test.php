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

class settings_test extends base
{
	public function getEventListener()
	{
		return new \vse\topicimagepreview\event\settings(
			$this->config,
			$this->language,
			$this->request,
			$this->template,
			$this->user
		);
	}

	public function test_construct()
	{
		$this->assertInstanceOf('\Symfony\Component\EventDispatcher\EventSubscriberInterface', $this->getEventListener());
	}

	public function test_getSubscribedEvents()
	{
		$this->assertEquals([
			'core.acp_board_config_edit_add',
			'core.ucp_prefs_view_data',
			'core.ucp_prefs_view_update_data',
		], array_keys(\vse\topicimagepreview\event\settings::getSubscribedEvents()));
	}

	public function update_acp_data_data()
	{
		return [
			[ // expected config and mode
			  'post',
			  ['vars' => ['legend3' => []]],
			  ['legend_vse_tip', 'vse_tip_new', 'vse_tip_num', 'vse_tip_dim', 'legend3'],
			],
			[ // unexpected mode
			  'foobar',
			  ['vars' => ['legend3' => []]],
			  ['legend3'],
			],
			[ // unexpected config
			  'post',
			  ['vars' => ['foobar' => []]],
			  ['foobar'],
			],
			[ // unexpected config and mode
			  'foobar',
			  ['vars' => ['foobar' => []]],
			  ['foobar'],
			],
		];
	}

	/**
	 * @dataProvider update_acp_data_data
	 */
	public function test_update_acp_data($mode, $display_vars, $expected_keys)
	{
		require_once __DIR__ . '/../../../../../includes/functions_acp.php';

		$listener = $this->getEventListener();

		$event_data = ['display_vars', 'mode'];
		$event = new \phpbb\event\data(compact($event_data));

		$listener->update_acp_data($event);

		$event_data_after = $event->get_data_filtered($event_data);
		foreach ($event_data as $expected)
		{
			$this->assertArrayHasKey($expected, $event_data_after);
		}
		extract($event_data_after, EXTR_OVERWRITE);

		$keys = array_keys($display_vars['vars']);

		$this->assertEquals($expected_keys, $keys);
	}

	public function update_ucp_data_data()
	{
		return [
			[
				[],
				[],
				['user_vse_tip' => 0],
			],
			[
				['user_vse_tip' => 1],
				[],
				['user_vse_tip' => 1],
			],
			[
				[
					'user_options'	=> 0,
					'user_vse_tip'	=> 1,
				],
				[
					'user_options'				=> 0,
					'user_topic_sortby_type'	=> 0,
					'user_post_sortby_type'		=> 0,
					'user_topic_sortby_dir'		=> 0,
					'user_post_sortby_dir'		=> 0,
				],
				[
					'user_options'				=> 0,
					'user_topic_sortby_type'	=> 0,
					'user_post_sortby_type'		=> 0,
					'user_topic_sortby_dir'		=> 0,
					'user_post_sortby_dir'		=> 0,
					'user_vse_tip'				=> 1,
				],
			],
		];
	}

	/**
	 * @dataProvider update_ucp_data_data
	 */
	public function test_update_ucp_data($data, $sql_ary, $expected)
	{
		$listener = $this->getEventListener();

		$event_data = ['data', 'sql_ary'];
		$event = new \phpbb\event\data(compact($event_data));
		$listener->update_ucp_data($event);

		$event_data_after = $event->get_data_filtered($event_data);

		$this->assertEquals($expected, $event_data_after['sql_ary']);
	}

	public function handle_ucp_data_data()
	{
		return [
			[
				1,
				true,
				[],
				['user_vse_tip' => 1],
			],
			[
				1,
				false,
				[],
				['user_vse_tip' => 1],
			],
			[
				1,
				true,
				[
					'images'		=> 0,
					'flash'			=> 0,
					'smilies'		=> 0,
					'sigs'			=> 0,
					'avatars'		=> 0,
					'wordcensor'	=> 0,
				],
				[
					'images'		=> 0,
					'flash'			=> 0,
					'smilies'		=> 0,
					'sigs'			=> 0,
					'avatars'		=> 0,
					'wordcensor'	=> 0,
					'user_vse_tip'	=> 1,
				],
			],
			[
				1,
				false,
				[
					'images'		=> 0,
					'flash'			=> 0,
					'smilies'		=> 0,
					'sigs'			=> 0,
					'avatars'		=> 0,
					'wordcensor'	=> 0,
				],
				[
					'images'		=> 0,
					'flash'			=> 0,
					'smilies'		=> 0,
					'sigs'			=> 0,
					'avatars'		=> 0,
					'wordcensor'	=> 0,
					'user_vse_tip'	=> 1,
				],
			],
			[
				0,
				false,
				[
					'images'		=> 0,
					'flash'			=> 0,
					'smilies'		=> 0,
					'sigs'			=> 0,
					'avatars'		=> 0,
					'wordcensor'	=> 0,
				],
				[
					'images'		=> 0,
					'flash'			=> 0,
					'smilies'		=> 0,
					'sigs'			=> 0,
					'avatars'		=> 0,
					'wordcensor'	=> 0,
					'user_vse_tip'	=> 0,
				],
			],
			[
				0,
				true,
				[
					'images'		=> 0,
					'flash'			=> 0,
					'smilies'		=> 0,
					'sigs'			=> 0,
					'avatars'		=> 0,
					'wordcensor'	=> 0,
				],
				[
					'images'		=> 0,
					'flash'			=> 0,
					'smilies'		=> 0,
					'sigs'			=> 0,
					'avatars'		=> 0,
					'wordcensor'	=> 0,
					'user_vse_tip'	=> 0,
				],
			],
		];
	}

	/**
	 * @dataProvider handle_ucp_data_data
	 */
	public function test_handle_ucp_data($user_vse_tip, $submit, $data, $expected)
	{
		$listener = $this->getEventListener();

		$this->user->data['user_vse_tip'] = 0;
		$this->request->expects($this->once())
			->method('variable')
			->willReturn($user_vse_tip);

		if (!$submit)
		{
			$this->template->expects($this->once())
				->method('assign_vars')
				->with([
					'S_VSE_TIP_NUM'  => '3',
					'S_VSE_TIP_USER' => $user_vse_tip,
				]);
		}

		$event_data = ['submit', 'data'];
		$event = new \phpbb\event\data(compact($event_data));
		$listener->handle_ucp_data($event);

		$result = $event->get_data_filtered($event_data);

		$this->assertEquals($expected, $result['data']);
	}
}
