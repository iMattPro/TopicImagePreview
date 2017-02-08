<?php
/**
 *
 * Topic Image Preview. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, Matt Friedman
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

$lang = array_merge($lang, [
	'ACP_TIP_TITLE'					=> 'Topic Image Preview',
	'ACP_TIP_DISPLAY_AGE'			=> 'Display images from',
	'ACP_TIP_DISPLAY_AGE_EXPLAIN'	=> 'Preview images from the first post, or the most recent images in the last post.',
	'ACP_TIP_DISPLAY_NUM'			=> 'Number of images to display',
	'ACP_TIP_DISPLAY_NUM_EXPLAIN'	=> 'Maximum number of images to display in previews. Set to 0 to disable previews.',

	'FIRST_POST'	=> 'First post',
]);
