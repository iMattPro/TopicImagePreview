<?php
/**
 *
 * Topic Image Preview. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, 2020, Matt Friedman
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
			// Global events
			'core.permissions'						=> 'add_permission',
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
	 * Add administrative permissions to manage forums
	 *
	 * @param \phpbb\event\data $event The event object
	 * @return void
	 */
	public function add_permission($event)
	{
		$event->update_subarray('permissions', 'f_vse_tip', [
			'lang' => 'ACL_F_VSE_TIP',
			'cat'  => 'actions',
		]);
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
		if ($event['mode'] !== 'post' || !array_key_exists('legend3', $event['display_vars']['vars']))
		{
			return;
		}

		$this->language->add_lang('tip_acp', 'vse/topicimagepreview');

		// check for phpBB4 since h_radio() was replaced with phpbb_build_radio()
		$is_phpbb4 = strpos(PHPBB_VERSION, '4') === 0;

		$my_config_vars = [
			'legend_vse_tip' => 'ACP_TIP_TITLE',
			'vse_tip_new'  => [
				'lang'     => 'ACP_TIP_DISPLAY_AGE',
				'validate' => 'bool',
				'type'     => $is_phpbb4 ? 'radio' : 'custom',
				'function' => $is_phpbb4 ? 'phpbb_build_radio' : 'h_radio',
				'params'   => $is_phpbb4
					? ['{CONFIG_VALUE}', '{KEY}', [1 => 'ACP_TIP_NEWEST_POST', 0 => 'ACP_TIP_OLDEST_POST']]
					: ['config[vse_tip_new]', [1 => 'ACP_TIP_NEWEST_POST', 0 => 'ACP_TIP_OLDEST_POST'], '{CONFIG_VALUE}', '{KEY}'],
				'explain'  => true
			],
			'vse_tip_num' => [
				'lang'     => 'ACP_TIP_DISPLAY_NUM',
				'validate' => 'int:0:99',
				'type'     => 'number:0:99',
				'explain'  => true
			],
			'vse_tip_dim' => [
				'lang'     => 'ACP_TIP_DISPLAY_DIM',
				'validate' => 'int:0:999',
				'type'     => 'number:0:999',
				'explain'  => true,
				'append'   => ' ' . $this->language->lang('PIXEL')
			],
			'vse_tip_srt' => [
				'lang'     => 'ACP_TIP_DISPLAY_SRT',
				'validate' => 'bool',
				'type'     => 'radio:yes_no',
				'explain'  => false
			],
		];

		// Add an option to display in Precise Similar Topics if it is installed
		if ($this->config->offsetExists('similar_topics'))
		{
			$my_config_vars['vse_tip_pst'] = [
				'lang' => 'ACP_TIP_DISPLAY_PST',
				'validate' => 'bool',
				'type' => 'radio:yes_no',
				'explain' => false
			];
		}

		$event->update_subarray(
			'display_vars',
			'vars',
			phpbb_insert_config_array($event['display_vars']['vars'], $my_config_vars, ['before' => 'legend3'])
		);
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
