<?php
/**
 *
 * Topic Image Preview. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Matt Friedman
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace vse\TopicImagePreview\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Topic Image Preview Event listener.
 */
class main_listener implements EventSubscriberInterface
{
	/**
	 * @var \phpbb\config\config
	 */
	protected $config;

	/**
	 * @var \phpbb\language\language
	 */
	protected $language;

	/**
	 * {@inheritdoc}
	 */
	static public function getSubscribedEvents()
	{
		return [
			// ACP settings
			'core.acp_board_config_edit_add'		=> 'acp_config',

			// viewforum.php events
			'core.viewforum_get_topic_data'			=> 'modify_sql',
			'core.viewforum_get_shadowtopic_data'	=> 'modify_sql',
			'core.viewforum_modify_topicrow'		=> 'display_preview',

			// search.php events
			'core.search_get_topic_data'			=> 'modify_sql',
			'core.search_modify_tpl_ary'			=> 'display_preview',

			// Custom events for integration with Precise Similar Topics
			'vse.similartopics.get_topic_data'		=> 'modify_sql',
			'vse.similartopics.modify_topicrow'		=> 'display_preview',

			// Custom events for integration with Recent Topics
			'paybas.recenttopics.sql_pull_topics_data'	=> 'modify_sql',
			'paybas.recenttopics.modify_tpl_ary'		=> 'display_preview',

			// Custom events for integration with Top Five
			'rmcgirr83.topfive.sql_pull_topics_data'	=> 'modify_sql',
			'rmcgirr83.topfive.modify_tpl_ary'			=> 'display_preview',
		];
	}

	/**
	 * main_listener constructor.
	 *
	 * @param \phpbb\config\config     $config
	 * @param \phpbb\language\language $language
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\language\language $language)
	{
		$this->config = $config;
		$this->language = $language;
	}

	/**
	 * Modify SQL queries to get the first or last post's text
	 *
	 * @param \phpbb\event\data $event The event object
	 *
	 * @return void
	 */
	public function modify_sql($event)
	{
		$select = ', p.post_text';
		$join = [
			'FROM'	=> [POSTS_TABLE => 'p'],
			'ON'	=> 'p.post_id = t.' . ($this->config->offsetGet('vse_tip_new') ? 'topic_last_post_id' : 'topic_first_post_id')
		];

		// Update an sql array
		if ($event->offsetExists('sql_array'))
		{
			$sql_array = $event['sql_array'];
			$sql_array['SELECT'] .= $select;
			$sql_array['LEFT_JOIN'][] = $join;
			$event['sql_array'] = $sql_array;
			return;
		}

		// Update an sql select stmt
		if ($event->offsetExists('sql_select'))
		{
			$event['sql_select'] .= $select;
		}

		// Update an sql join stmt
		if ($event->offsetExists('sql_from'))
		{
			$event['sql_from'] .= ' LEFT JOIN ' . key($join['FROM']) . ' ' . current($join['FROM']) . ' ON (' . $join['ON'] . ')';
		}
	}

	/**
	 * Parse and display image previews
	 *
	 * @param \phpbb\event\data $event The event object
	 *
	 * @return void
	 */
	public function display_preview($event)
	{
		// Check if we have any post text
		$row = $event['row'];
		if (empty($row['post_text']))
		{
			return;
		}

		// Check if we have any images
		$post_text = $row['post_text'];
		if (!preg_match('/^<[r][ >]/', $post_text) || strpos($post_text, '<IMG ') === false)
		{
			return;
		}

		// Grab the images
		$images = [];
		$dom = new \DOMDocument;
		$dom->loadXML($post_text);
		$xpath = new \DOMXPath($dom);
		foreach ($xpath->query('//IMG[not(ancestor::IMG)]/@src') as $image)
		{
			$images[] = $image->textContent;
		}

		// Create a string of images
		$img_string = implode(' ', array_map(function($image) {
			return '<img src=\'' . $image . '\' style=\'max-width:200px; max-height:200px;\' />';
		}, array_slice($images, 0, (int) $this->config->offsetGet('vse_tip_num'), true)));

		// Send the image string to the template
		$block = $event->offsetExists('topic_row') ? 'topic_row' : 'tpl_ary';
		$event[$block] = array_merge($event[$block], ['TOPIC_IMAGES' => $img_string]);
	}

	/**
	 * Add ACP config options to Post settings
	 *
	 * @param \phpbb\event\data $event The event object
	 *
	 * @return void
	 */
	public function acp_config($event)
	{
		$display_vars = $event['display_vars'];
		if ($event['mode'] === 'post' && array_key_exists('legend3', $display_vars['vars']))
		{
			$this->language->add_lang('vse_tip_acp', 'vse/TopicImagePreview');

			$my_config_vars = array(
				'legend_vse_tip'	=> 'ACP_TIP_TITLE',
				'vse_tip_num'		=> array('lang' => 'ACP_TIP_DISPLAY_NUM', 'validate' => 'int:0:99', 'type' => 'number:0:99', 'explain' => true),
				'vse_tip_new'		=> array('lang' => 'ACP_TIP_DISPLAY_AGE', 'validate' => 'bool', 'type' => 'custom', 'function' => array($this, 'select_vse_tip_new'), 'explain' => true),
			);

			$display_vars['vars'] = phpbb_insert_config_array($display_vars['vars'], $my_config_vars, array('before' => 'legend3'));
			$event['display_vars'] = $display_vars;
		}
	}

	/**
	 * Create custom radio buttons
	 *
	 * @param mixed  $value
	 * @param string $key
	 *
	 * @return string
	 */
	public function select_vse_tip_new($value, $key = '')
	{
		$radio_ary = array(0 => 'FIRST_POST', 1 => 'LAST_POST');

		return h_radio('config[vse_tip_new]', $radio_ary, $value, $key);
	}
}
