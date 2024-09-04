<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\CustomCategory;

use Joomla\CMS\Categories\CategoryInterface;
use Joomla\CMS\Categories\CategoryNode;

class ARSPseudoCategory implements CategoryInterface
{
	#[\ReturnTypeWillChange]
	public function get($id = 'root', $forceload = false): ?CategoryNode
	{
		return new CategoryNode([], $this);
	}
}