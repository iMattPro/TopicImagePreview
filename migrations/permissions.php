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
		return array('\vse\topicimagepreview\migrations\install_tip');
	}

	/**
	 * @inheritdoc
	 */
	public function update_data()
	{
		return array(
			// Add permission
			array('permission.add', array('f_vse_tip', false)),

			// Set permission roles
			array('permission.permission_set', array('ROLE_FORUM_FULL', 'f_vse_tip')),
			array('permission.permission_set', array('ROLE_FORUM_LIMITED', 'f_vse_tip')),
			array('permission.permission_set', array('ROLE_FORUM_LIMITED_POLLS', 'f_vse_tip')),
			array('permission.permission_set', array('ROLE_FORUM_NEW_MEMBER', 'f_vse_tip')),
			array('permission.permission_set', array('ROLE_FORUM_POLLS', 'f_vse_tip')),
			array('permission.permission_set', array('ROLE_FORUM_READONLY', 'f_vse_tip')),
			array('permission.permission_set', array('ROLE_FORUM_STANDARD', 'f_vse_tip')),
		);
	}
}
