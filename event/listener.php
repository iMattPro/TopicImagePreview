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
class listener implements EventSubscriberInterface
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
	public static function getSubscribedEvents()
	{
		return [
			// ACP events
			'core.acp_board_config_edit_add'		=> 'update_acp_data',
			// Viewforum events
			'core.viewforum_modify_topics_data'		=> 'update_row_data',
			'core.viewforum_modify_topicrow'		=> 'update_tpl_data',
			// Search events
			'core.search_modify_rowset'				=> 'update_row_data',
			'core.search_modify_tpl_ary'			=> 'update_tpl_data',
			// Precise Similar Topics events
			'vse.similartopics.modify_rowset'		=> 'update_row_data',
			'vse.similartopics.modify_topicrow'		=> 'update_tpl_data',
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
	 * Run an SQL query to find the posts with images in a group topics
	 * and the post's text to each topic's rowset.
	 *
	 * @param array $topic_list An array of topic ids
	 * @param array $rowset     The rowset of topic data
	 *
	 * @return array The updated rowset of topic data
	 */
	protected function query_images(array $topic_list, array $rowset)
	{
		$sql_array = [];
		foreach ($topic_list as $topic_id)
		{
			$stmt = '(SELECT topic_id, post_text 
				FROM ' . POSTS_TABLE . '
				WHERE topic_id = ' . (int) $topic_id . '
					AND post_text ' . $this->db->sql_like_expression('<r>' . $this->db->get_any_char() . '<IMG ' . $this->db->get_any_char()) . '
				ORDER BY post_time ' . ($this->config->offsetGet('vse_tip_new') ? 'DESC' : 'ASC') . '
				LIMIT 1)';

			// SQLite3 doesn't like ORDER BY with UNION ALL, so treat $stmt as derived table
			if ($this->db->get_sql_layer() === 'sqlite3')
			{
				$stmt = "SELECT * FROM $stmt AS d";
			}

			$sql_array[] = $stmt;
		}
		$sql = implode(' UNION ALL ', $sql_array);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rowset[$row['topic_id']]['vse_tip_text'] = $row['post_text'];
		}
		$this->db->sql_freeresult($result);

		return $rowset;
	}

	/**
	 * Add image previews to the template data.
	 *
	 * @param \phpbb\event\data $event The event object
	 *
	 * @return void
	 */
	public function update_tpl_data($event)
	{
		// Check if we have any post text or images
		$row = $event['row'];
		if (empty($row['vse_tip_text']) || !preg_match('/^<[r][ >]/', $row['vse_tip_text']) || strpos($row['vse_tip_text'], '<IMG ') === false)
		{
			return;
		}

		// Extract the images
		$images = [];
		$dom = new \DOMDocument;
		$dom->loadXML($row['vse_tip_text']);
		$xpath = new \DOMXPath($dom);
		foreach ($xpath->query('//IMG[not(ancestor::IMG)]/@src') as $image)
		{
			$images[] = $image->textContent;
		}

		// Create a string of images
		$img_string = implode(' ', array_map(function ($image) {
			return "<img src='{$image}' style='max-width:{$this->config['vse_tip_dim']}px; max-height:{$this->config['vse_tip_dim']}px;' />";
		}, array_slice($images, 0, (int) $this->config['vse_tip_num'], true)));

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

			$my_config_vars = [
				'legend_vse_tip'	=> 'ACP_TIP_TITLE',
				'vse_tip_new'		=> ['lang' => 'ACP_TIP_DISPLAY_AGE', 'validate' => 'bool', 'type' => 'custom', 'function' => [$this, 'select_vse_tip_new'], 'explain' => true],
				'vse_tip_num'		=> ['lang' => 'ACP_TIP_DISPLAY_NUM', 'validate' => 'int:0:99', 'type' => 'number:0:99', 'explain' => true],
				'vse_tip_dim'		=> ['lang' => 'ACP_TIP_DISPLAY_DIM', 'validate' => 'int:0:999', 'type' => 'number:0:999', 'explain' => true, 'append' => ' ' . $this->language->lang('PIXEL')],
			];

			$display_vars['vars'] = phpbb_insert_config_array($display_vars['vars'], $my_config_vars, ['before' => 'legend3']);
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
		$radio_ary = [1 => 'ACP_TIP_NEWEST_POST', 0 => 'ACP_TIP_OLDEST_POST'];

		return h_radio('config[vse_tip_new]', $radio_ary, $value, $key);
	}
}
