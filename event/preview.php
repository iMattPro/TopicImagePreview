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

use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\user;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Topic Image Preview Event listener.
 */
class preview implements EventSubscriberInterface
{
	/** @var config */
	protected $config;

	/** @var driver_interface */
	protected $db;

	/** @var user */
	protected $user;

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents()
	{
		return [
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
	 * @param config           $config
	 * @param driver_interface $db
	 * @param user             $user
	 */
	public function __construct(config $config, driver_interface $db, user $user)
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
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
		if (empty($this->user->data['user_vse_tip']))
		{
			return;
		}

		// Use topic_list from event, otherwise create one based on the rowset
		$topic_list = $event->offsetExists('topic_list') ? $event['topic_list'] : array_keys($event['rowset']);

		if (count($topic_list))
		{
			$event['rowset'] = $this->query_images($topic_list, $event['rowset']);
		}
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
		if (empty($this->user->data['user_vse_tip']) || empty($event['row']['post_text']) || !preg_match('/^<[r][ >]/', $event['row']['post_text']) || strpos($event['row']['post_text'], '<IMG ') === false)
		{
			return;
		}

		// Send the image string to the template
		$block = $event->offsetExists('topic_row') ? 'topic_row' : 'tpl_ary';
		$event[$block] = array_merge($event[$block], ['TOPIC_IMAGES' => $this->extract_images($event['row']['post_text'])]);
	}

	/**
	 * Run an SQL query on a group of topics, and find the newest (or oldest)
	 * post with [IMG] images. Then update the topic's row set array to include
	 * the post's text in the cases where images were found.
	 *
	 * @param array $topic_list An array of topic ids
	 * @param array $rowset     The row set of topic data
	 *
	 * @return array The updated row set of topic data which now includes
	 *               the post_text of a post containing images.
	 */
	protected function query_images(array $topic_list, array $rowset)
	{
		$sql_array = [];
		foreach ($topic_list as $topic_id)
		{
			$stmt = '(SELECT topic_id, post_text 
				FROM ' . POSTS_TABLE . '
				WHERE topic_id = ' . (int) $topic_id . '
					AND post_visibility = ' . ITEM_APPROVED . '
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
			$rowset[$row['topic_id']]['post_text'] = $row['post_text'];
		}
		$this->db->sql_freeresult($result);

		return $rowset;
	}

	/**
	 * Extract images from a post and return them as HTML image tags.
	 *
	 * @param string $post Post text from the database.
	 *
	 * @return string An string of HTML IMG tags.
	 */
	protected function extract_images($post)
	{
		// Extract the images
		$images = [];
		$dom = new \DOMDocument;
		$dom->loadXML($post);
		$xpath = new \DOMXPath($dom);
		foreach ($xpath->query('//IMG[not(ancestor::IMG)]/@src') as $image)
		{
			$images[] = $image->textContent;
		}

		// Create a string of images
		return implode(' ', array_map(function ($image) {
			return "<img src='{$image}' alt='' style='max-width:{$this->config['vse_tip_dim']}px; max-height:{$this->config['vse_tip_dim']}px;' />";
		}, array_slice($images, 0, (int) $this->config['vse_tip_num'], true)));
	}
}
