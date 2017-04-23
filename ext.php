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
		$lang = $this->container->get('language');
		$lang->add_lang('tip_install', 'vse/TopicImagePreview');

		// Require phpBB 3.2.0 or newer.
		return phpbb_version_compare(PHPBB_VERSION, '3.2.0', '>=');
	}
}
