<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') or die;

use Joomla\CMS\Helper\CMSHelper;
use Joomla\CMS\Table\CoreContent;
use Joomla\CMS\UCM\UCMContent;

trait EnsureUcmTrait
{
	/**
	 * Makes sure items created with older versions of ARS have UCM records.
	 *
	 * UCM records are used for tagging. You cannot tag an existing record unless it also has a UCM record. For new
	 * reconds the corresponding UCM record is created automatically. For existing records predating version 7.4.0
	 * we need this method to create a UCM record.
	 *
	 * @since  7.4.0
	 */
	protected function ensureUcmRecord(): void
	{
		$ucm = new UCMContent($this, $this->typeAlias);

		$genericHelper = new CMSHelper();
		$data          = $genericHelper->getRowData($this);
		$ucmData       = $ucm->mapData($data);

		$primaryId = $ucm->getPrimaryKey(
			$ucmData['common']['core_type_id'],
			$ucmData['common']['core_content_item_id']
		);

		if (!empty($primaryId))
		{
			return;
		}

		$ucmContentTable = new CoreContent($this->getDbo());
		$ucmContentTable->save($ucmData['common']);
	}
}