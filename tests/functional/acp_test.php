<?php
/**
 *
 * Topic Image Preview. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2017
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace vse\TopicImagePreview\tests\functional;

/**
 * @group functional
 */
class acp_test extends \phpbb_functional_test_case
{
	static protected function setup_extensions()
	{
		return array('vse/TopicImagePreview');
	}

	public function test_acp_settings()
	{
		$this->login();
		$this->admin_login();

		$this->add_lang('acp/board');
		$this->add_lang_ext('vse/TopicImagePreview', 'tip_acp');

		$found = false;

		$crawler = self::request('GET', 'adm/index.php?i=acp_board&mode=post&sid=' . $this->sid);

		$nodes = $crawler->filter('#acp_board > fieldset > legend')->extract(array('_text'));
		foreach ($nodes as $key => $config_name)
		{
			if (strpos($config_name, $this->lang('POSTING')) !== 0)
			{
				continue;
			}

			$found = true;

			$this->assertContainsLang('ACP_TIP_TITLE', $nodes[$key + 1]);
			break;
		}

		if (!$found)
		{
			$this->fail('Topic Image Preview settings were not found in the expected ACP location.');
		}
	}
}
