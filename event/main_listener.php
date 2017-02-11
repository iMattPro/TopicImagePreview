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
	 * @var \phpbb\db\driver\driver_interface
	 */
	protected $db;

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
			'core.acp_board_config_edit_add'		=> 'update_acp_data',

			'core.viewforum_modify_topics_data'		=> 'update_row_data',
			'core.viewforum_modify_topicrow'		=> 'update_tpl_data',

			'core.search_modify_rowset'				=> 'update_row_data',
			'core.search_modify_tpl_ary'			=> 'update_tpl_data',

			// ToDo: Custom events for integration with Precise Similar Topics
			//'vse.similartopics.get_topic_data'		=> 'update_row_data',
			//'vse.similartopics.modify_topicrow'		=> 'update_tpl_data',
		];
	}

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config              $config
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param \phpbb\language\language          $language
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\language\language $language)
	{
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
	}

	/**
	 * Add post text containing images to topic row data.
	 *
	 * @param \phpbb\event\data $event The event object
	 *
	 * @return void
	 */
	public function update_row_data($event)
	{
		// Use topic_list from event, otherwise create one based on the rowset
		$topic_list = $event->offsetExists('topic_list') ? $event['topic_list'] : array_keys($event['rowset']);

		if (count($topic_list))
		{
			$event['rowset'] = $this->query_images($topic_list, $event['rowset']);
		}
	}

	/**
	 * Run an SQL query to find the posts with images in a topic's rowset.
	 *
	 * @param array $topic_list An array of topic ids
	 * @param array $rowset     The rowset of topic data
	 *
	 * @return array The updated rowset of topic data
	 */
	protected function query_images(array $topic_list, array $rowset)
	{
		// Query the group of topics. Search for <IMG in all posts from these topics,
		// and get either the newest (MAX) or oldest (MIN) post text containing <IMG.
		$sql = 'SELECT topic_id, post_text
			FROM ' . POSTS_TABLE . ' p1 
			WHERE ' . $this->db->sql_in_set('p1.topic_id', $topic_list) . '
				AND p1.post_time = 
				(SELECT ' . ($this->config->offsetGet('vse_tip_new') ? 'MAX' : 'MIN') . '(p2.post_time) 
					FROM phpbb_posts p2 
					WHERE p2.topic_id = p1.topic_id
						AND p2.post_text ' . $this->db->sql_like_expression($this->db->get_any_char() . '<IMG ' . $this->db->get_any_char()) . ')
			GROUP BY topic_id';

		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rowset[$row['topic_id']]['vse_tip_text'] = $row['post_text'];
		}
		$this->db->sql_freeresult($result);

		return $rowset;
	}

	/**
	 * Extract and prepare image previews.
	 *
	 * @param \phpbb\event\data $event The event object
	 *
	 * @return void
	 */
	public function update_tpl_data($event)
	{
		// Check if we have any post text
		$row = $event['row'];
		if (empty($row['vse_tip_text']))
		{
			return;
		}

		// Check if we have any images
		$post_text = $row['vse_tip_text'];
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
	 * Add ACP config options to Post settings.
	 *
	 * @param \phpbb\event\data $event The event object
	 *
	 * @return void
	 */
	public function update_acp_data($event)
	{
		$display_vars = $event['display_vars'];
		if ($event['mode'] === 'post' && array_key_exists('legend3', $display_vars['vars']))
		{
			$this->language->add_lang('tip_acp', 'vse/TopicImagePreview');

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
	 * Create custom radio buttons.
	 *
	 * @param mixed  $value
	 * @param string $key
	 *
	 * @return string
	 */
	public function select_vse_tip_new($value, $key = '')
	{
		$radio_ary = array(1 => 'ACP_TIP_NEWEST_POST', 0 => 'ACP_TIP_OLDEST_POST');

		return h_radio('config[vse_tip_new]', $radio_ary, $value, $key);
	}
}
