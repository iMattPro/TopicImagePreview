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
		return $this->config->offsetExists('vse_tip_srt');
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
			// Add new configs
			['config.add', ['vse_tip_srt', 1]],
			['config.add', ['vse_tip_pst', 1]],

			// Add permission
			['permission.add', ['f_vse_tip', false, 'f_list_topics']],

			// Unset permissions that may have been set by copying from f_list_topics
			['permission.permission_unset', ['ROLE_FORUM_BOT', 'f_vse_tip']],
			['permission.permission_unset', ['ROLE_FORUM_ONQUEUE', 'f_vse_tip']],
		];
	}
}
