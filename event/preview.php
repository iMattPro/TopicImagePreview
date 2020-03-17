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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use vse\topicimagepreview\factory;

/**
 * Topic Image Preview Event listener.
 */
class preview implements EventSubscriberInterface
{
	/** @var config */
	protected $config;

	/** @var factory */
	private $factory;

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents()
	{
		return [
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
	 * @param config  $config
	 * @param factory $factory
	 */
	public function __construct(config $config, factory $factory)
	{
		$this->config = $config;
		$this->factory = $factory;
	}

	/**
	 * Update viewforum row
	 *
	 * @param \phpbb\event\data $event The event object
	 */
	public function viewforum_row($event)
	{
		$this->factory->update_row_data($event);
	}

	/**
	 * Update viewforum template
	 *
	 * @param \phpbb\event\data $event The event object
	 */
	public function viewforum_tpl($event)
	{
		$this->factory->update_tpl_data($event);
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
			$this->factory->update_row_data($event);
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
			$this->factory->update_tpl_data($event);
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
			$this->factory->update_row_data($event);
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
			$this->factory->update_tpl_data($event);
		}
	}
}
