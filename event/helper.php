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

use phpbb\auth\auth;
use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\user;

/**
 * Topic Image Preview Factory.
 */
class helper
{
	/** @var auth */
	protected $auth;

	/** @var config */
	protected $config;

	/** @var driver_interface */
	protected $db;

	/** @var user */
	protected $user;

	/** @var bool */
	protected $preview = false;

	/**
	 * Constructor
	 *
	 * @param auth             $auth
	 * @param config           $config
	 * @param driver_interface $db
	 * @param user             $user
	 */
	public function __construct(auth $auth, config $config, driver_interface $db, user $user)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
	}

	/**
	 * Get the value of the in_preview
	 *
	 * @return bool True if topic image previews are generated.
	 */
	public function is_preview()
	{
		return $this->preview;
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
		if (!$this->user_allowed())
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
		if (empty($event['row']['post_text']) || !$this->user_allowed() || !$this->forum_allowed($event['row']['forum_id']))
		{
			return;
		}

		// Send the image string to the template
		$block = $event->offsetExists('topic_row') ? 'topic_row' : 'tpl_ary';
		$event[$block] = array_merge($event[$block], ['TOPIC_IMAGES' => $this->extract_images($event['row']['post_text'])]);

		$this->preview = true;
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
		$direction = $this->config->offsetGet('vse_tip_new') ? 'DESC' : 'ASC';
		$like_expression = $this->db->sql_like_expression('<r>' . $this->db->get_any_char() . '<IMG ' . $this->db->get_any_char());
		$sql_layer = $this->db->get_sql_layer();
		$is_sqlite3 = $sql_layer === 'sqlite3';
		$is_mssql = strpos($sql_layer, 'mssql') === 0;

		$valid_topics = array_filter($topic_list, function ($topic_id) use ($rowset) {
			return isset($rowset[$topic_id]) && $this->forum_allowed($rowset[$topic_id]['forum_id']);
		});

		if (empty($valid_topics)) {
			return $rowset;
		}

		if (!$is_sqlite3 && !$is_mssql) {
			// Optimized version using ROW_NUMBER() so it can scale better then lot of unions
			$topic_ids_sql = implode(',', array_map('intval', $valid_topics));

			$sql = "
				SELECT topic_id, post_text FROM (
					SELECT topic_id, post_text,
						   ROW_NUMBER() OVER (PARTITION BY topic_id ORDER BY post_time $direction) as rn
					FROM " . POSTS_TABLE . "
					WHERE topic_id IN ($topic_ids_sql)
					  AND post_text " . $like_expression . "
				) t
				WHERE rn = 1
			";
		} else {
			// Fallback for SQLite/MSSQL (UNION ALL method)
			$sql_array = [];

			foreach ($valid_topics as $topic_id) {
				$stmt = '(SELECT ' . ($is_mssql ? 'TOP 1 ' : '') . 'topic_id, post_text
					FROM ' . POSTS_TABLE . '
					WHERE topic_id = ' . (int) $topic_id . '
						AND post_text ' . $like_expression . '
					ORDER BY post_time ' . $direction . ($is_mssql ? '' : ' LIMIT 1') . ')';

				$sql_array[] = ($is_sqlite3 || $is_mssql) ? "SELECT * FROM $stmt AS d" : $stmt;
			}

			$sql = implode(' UNION ALL ', $sql_array);
		}

		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result)) {
			$rowset[$row['topic_id']]['post_text'] = $row['post_text'];
		}
		$this->db->sql_freeresult($result);

		return $rowset;
	}

	/**
	 * Extract images from a post and return them as an array of images.
	 *
	 * @param string $post Post text from the database.
	 *
	 * @return array An array of images.
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

		return array_slice($images, 0, (int) $this->config['vse_tip_num'], true);
	}

	/**
	 * Is the forum allowed to show topic image previews
	 *
	 * @param int $forum_id Forum identifier
	 * @return bool True if allowed, false if not
	 */
	protected function forum_allowed($forum_id)
	{
		return (bool) $this->auth->acl_get('f_vse_tip', $forum_id);
	}

	/**
	 * Does the user allow topic image previews?
	 *
	 * @return bool True if allowed, false if not
	 */
	protected function user_allowed()
	{
		return (bool) $this->user->data['user_vse_tip'];
	}
}
