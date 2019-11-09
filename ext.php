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
	 * The minimum phpBB version required by this extension
	 */
	const PHPBB_MIN_VERSION = '3.2.1';

	/**
	 * {@inheritdoc}
	 */
	public function is_enableable()
	{
		// Require minimum phpBB version.
		$is_enableable = phpbb_version_compare(PHPBB_VERSION, self::PHPBB_MIN_VERSION, '>=');

		// If not enableable, add our custom install error language keys
		if (!$is_enableable)
		{
			$lang = $this->container->get('language');
			$lang->add_lang('tip_install', 'vse/TopicImagePreview');
		}

		return $is_enableable;
	}
}
