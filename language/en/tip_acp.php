<?php
/**
 *
 * Topic Image Preview. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017, 2020, Matt Friedman
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
	'ACP_TIP_DISPLAY_AGE_EXPLAIN'	=> 'Select Newest post to display the most recent images posted. Otherwise, select Oldest post to display the oldest images posted.',
	'ACP_TIP_NEWEST_POST'			=> 'Newest post',
	'ACP_TIP_OLDEST_POST'			=> 'Oldest post',
	'ACP_TIP_DISPLAY_NUM'			=> 'Number of images to display',
	'ACP_TIP_DISPLAY_NUM_EXPLAIN'	=> 'Maximum number of images to display in previews. Set to 0 to disable previews.',
	'ACP_TIP_DISPLAY_DIM'			=> 'Maximum image size per preview',
	'ACP_TIP_DISPLAY_DIM_EXPLAIN'	=> 'Maximum width/height of each image in the preview.',
	'ACP_TIP_DISPLAY_SRT'			=> 'Display in search results topics',
	'ACP_TIP_DISPLAY_PST'			=> 'Display in similar topics',
]);
