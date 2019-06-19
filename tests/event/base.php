<?php
/**
 *
 * Topic Image Preview. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace vse\TopicImagePreview\tests\event;

class base extends \phpbb_database_test_case
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\request\request|\PHPUnit_Framework_MockObject_MockObject */
	protected $request;

	/** @var \phpbb\template\template|\PHPUnit_Framework_MockObject_MockObject */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	protected static function setup_extensions()
	{
		return ['vse/TopicImagePreview'];
	}

	public function getDataSet()
	{
		return $this->createXMLDataSet(__DIR__ . '/fixtures/posts.xml');
	}

	public function setUp()
	{
		global $phpbb_root_path, $phpEx;

		parent::setUp();

		$this->config = new \phpbb\config\config([
			'vse_tip_new' => 1,
			'vse_tip_num' => 3,
			'vse_tip_dim' => 200,
		]);
		$this->db = $this->new_dbal();
		$this->language = new \phpbb\language\language(
			new \phpbb\language\language_file_loader($phpbb_root_path, $phpEx)
		);
		$this->request = $this->getMockBuilder('\phpbb\request\request')
			->disableOriginalConstructor()
			->getMock();
		$this->template = $this->getMockBuilder('\phpbb\template\template')
			->getMock();
		$this->user = new \phpbb\user($this->language, '\phpbb\datetime');
		$this->user->data['user_vse_tip'] = 1;
	}
}
