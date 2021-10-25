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

use phpbb\config\config;
use phpbb\template\template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Topic Image Preview Event listener.
 */
class preview implements EventSubscriberInterface
{
	/** @var config */
	protected $config;

	/** @var helper */
	protected $helper;

	/** @var template */
	protected $template;

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents()
	{
		return [
			'core.page_footer'						=> 'init_tpl_vars',
			// Viewforum events
			'core.viewforum_modify_topics_data'		=> 'viewforum_row',
			'core.viewforum_modify_topicrow'		=> 'viewforum_tpl',
			// Search events
			'core.search_modify_rowset'				=> 'searchresults_row',
			'core.search_modify_tpl_ary'			=> 'searchresults_tpl',
			// Precise Similar Topics events
			'vse.similartopics.modify_rowset'		=> 'similartopics_row',
			'vse.similartopics.modify_topicrow'		=> 'similartopics_tpl',
		];
	}

	/**
	 * Constructor
	 *
	 * @param config   $config
	 * @param helper   $helper
	 * @param template $template
	 */
	public function __construct(config $config, helper $helper, template $template)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
	}

	/**
	 * Set some template variables for T.I.P.
	 */
	public function init_tpl_vars()
	{
		$this->template->assign_vars([
			'S_TOPIC_IMAGE_PREVIEW'		=> $this->helper->is_preview(),
			'TOPIC_IMAGE_PREVIEW_DIM'	=> $this->config['vse_tip_dim'],
		]);
	}

	/**
	 * Update viewforum row
	 *
	 * @param \phpbb\event\data $event The event object
	 */
	public function viewforum_row($event)
	{
		$this->helper->update_row_data($event);
	}

	/**
	 * Update viewforum template
	 *
	 * @param \phpbb\event\data $event The event object
	 */
	public function viewforum_tpl($event)
	{
		$this->helper->update_tpl_data($event);
	}

	/**
	 * Update search results topics row
	 *
	 * @param \phpbb\event\data $event The event object
	 */
	public function searchresults_row($event)
	{
		if ($event['show_results'] === 'topics' && $this->config->offsetGet('vse_tip_srt'))
		{
			$this->helper->update_row_data($event);
		}
	}

	/**
	 * Update search results topics template
	 *
	 * @param \phpbb\event\data $event The event object
	 */
	public function searchresults_tpl($event)
	{
		if ($event['show_results'] === 'topics' && $this->config->offsetGet('vse_tip_srt'))
		{
			$this->helper->update_tpl_data($event);
		}
	}

	/**
	 * Update similar topics row
	 *
	 * @param \phpbb\event\data $event The event object
	 */
	public function similartopics_row($event)
	{
		if ($this->config->offsetGet('vse_tip_pst'))
		{
			$this->helper->update_row_data($event);
		}
	}

	/**
	 * Update similar topics template
	 *
	 * @param \phpbb\event\data $event The event object
	 */
	public function similartopics_tpl($event)
	{
		if ($this->config->offsetGet('vse_tip_pst'))
		{
			$this->helper->update_tpl_data($event);
		}
	}
}
