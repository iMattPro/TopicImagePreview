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
	'TIP_INSTALL_ERROR'				=> 'phpBB 3.2.0 or newer is required.',
]);

// Overwrite core error message keys with a more specific message.
// Translators can ignore this.
$lang = array_merge($lang, [
	'EXTENSION_NOT_ENABLEABLE'		=> $lang['EXTENSION_NOT_ENABLEABLE'] . '<br />' . $lang['TIP_INSTALL_ERROR'],
	'CLI_EXTENSION_ENABLE_FAILURE'	=> $lang['CLI_EXTENSION_ENABLE_FAILURE'] . '. ' . $lang['TIP_INSTALL_ERROR'],
]);
