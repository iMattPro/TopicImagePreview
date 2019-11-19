<?php
/**
 *
 * Topic Image Preview. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Matt Friedman
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace vse\topicimagepreview\event;

use phpbb\config\config;
use phpbb\language\language;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Topic Image Preview Event listener.
 */
class settings implements EventSubscriberInterface
{
	/** @var config */
	protected $config;

	/** @var language */
	protected $language;

	/** @var request */
	protected $request;

	/** @var template */
	protected $template;

	/** @var user */
	protected $user;

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents()
	{
		return [
			// ACP events
			'core.acp_board_config_edit_add'		=> 'update_acp_data',
			// UCP events
			'core.ucp_prefs_view_data'				=> 'handle_ucp_data',
			'core.ucp_prefs_view_update_data'		=> 'update_ucp_data',
		];
	}

	/**
	 * Constructor
	 *
	 * @param config   $config
	 * @param language $language
	 * @param request  $request
	 * @param template $template
	 * @param user     $user
	 */
	public function __construct(config $config, language $language, request $request, template $template, user $user)
	{
		$this->config = $config;
		$this->language = $language;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
	}

	/**
	 * Add ACP config options to Post settings.
	 *
	 * @param \phpbb\event\data $event The event object
	 *
	 * @return void
	 */
	public function update_acp_data($event)
	{
		if ($event['mode'] === 'post' && array_key_exists('legend3', $event['display_vars']['vars']))
		{
			$this->language->add_lang('tip_acp', 'vse/topicimagepreview');

			$my_config_vars = [
				'legend_vse_tip'	=> 'ACP_TIP_TITLE',
				'vse_tip_new'		=> ['lang' => 'ACP_TIP_DISPLAY_AGE', 'validate' => 'bool', 'type' => 'custom', 'function' => [$this, 'select_vse_tip_new'], 'explain' => true],
				'vse_tip_num'		=> ['lang' => 'ACP_TIP_DISPLAY_NUM', 'validate' => 'int:0:99', 'type' => 'number:0:99', 'explain' => true],
				'vse_tip_dim'		=> ['lang' => 'ACP_TIP_DISPLAY_DIM', 'validate' => 'int:0:999', 'type' => 'number:0:999', 'explain' => true, 'append' => ' ' . $this->language->lang('PIXEL')],
			];

			$event->update_subarray('display_vars', 'vars', phpbb_insert_config_array($event['display_vars']['vars'], $my_config_vars, ['before' => 'legend3']));
		}
	}

	/**
	 * Create custom radio buttons.
	 *
	 * @param mixed  $value
	 * @param string $key
	 *
	 * @return string
	 */
	public function select_vse_tip_new($value, $key = '')
	{
		$radio_ary = [1 => 'ACP_TIP_NEWEST_POST', 0 => 'ACP_TIP_OLDEST_POST'];

		return h_radio('config[vse_tip_new]', $radio_ary, $value, $key);
	}

	/**
	 * Get user's Topic Image Preview option and display it in UCP Preferences page
	 *
	 * @param \phpbb\event\data $event The event object
	 *
	 * @return void
	 */
	public function handle_ucp_data($event)
	{
		// Request the user option vars and add them to the data array
		$event->update_subarray(
			'data',
			'user_vse_tip',
			$this->request->variable('user_vse_tip', (int) $this->user->data['user_vse_tip'])
		);

		// Output the data vars to the template (except on form submit)
		if (!$event['submit'])
		{
			$this->language->add_lang('tip_ucp', 'vse/topicimagepreview');
			$this->template->assign_vars([
				'S_VSE_TIP_NUM' => $this->config->offsetGet('vse_tip_num'),
				'S_VSE_TIP_USER' => $event['data']['user_vse_tip'],
			]);
		}
	}

	/**
	 * Add user's Topic Image Preview option state into UCP sql_array
	 *
	 * @param \phpbb\event\data $event The event object
	 *
	 * @return void
	 */
	public function update_ucp_data($event)
	{
		$event->update_subarray('sql_ary', 'user_vse_tip', $event['data']['user_vse_tip']);
	}
}
