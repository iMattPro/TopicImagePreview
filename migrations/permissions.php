<?php
/**
 *
 * Topic Image Preview. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, Matt Friedman
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace vse\topicimagepreview\migrations;

class permissions extends \phpbb\db\migration\migration
{
	/**
	 * @inheritdoc
	 */
	public function effectively_installed()
	{
		$sql = 'SELECT * FROM ' . $this->table_prefix . "acl_options
			WHERE auth_option = 'f_vse_tip'";
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row !== false;
	}

	/**
	 * @inheritdoc
	 */
	public static function depends_on()
	{
		return ['\vse\topicimagepreview\migrations\install_tip'];
	}

	/**
	 * @inheritdoc
	 */
	public function update_data()
	{
		return [
			// Add permission
			['permission.add', ['f_vse_tip', false, 'f_list_topics']],

			// Unset permissions that may have been set by copying from f_list_topics
			['permission.permission_unset', ['ROLE_FORUM_BOT', 'f_vse_tip']],
			['permission.permission_unset', ['ROLE_FORUM_ONQUEUE', 'f_vse_tip']],
		];
	}
}
