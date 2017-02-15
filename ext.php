<?php
/**
 *
 * Topic Image Preview. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Matt Friedman
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace vse\TopicImagePreview;

class ext extends \phpbb\extension\base
{
	/**
	 * {@inheritdoc}
	 */
	public function is_enableable()
	{
		$db   = $this->container->get('dbal.conn');
		$lang = $this->container->get('language');
		$lang->add_lang('tip_acp', 'vse/TopicImagePreview');

		if (phpbb_version_compare(PHPBB_VERSION, '3.2.0', '<'))
		{
			trigger_error($lang->lang('ACP_TIP_INVALID_BOARD'), E_USER_WARNING);
		}

		if (strpos($db->get_sql_layer(), 'postgres') !== false)
		{
			trigger_error($lang->lang('ACP_TIP_INVALID_DBAL'), E_USER_WARNING);
		}

		return true;
	}
}
