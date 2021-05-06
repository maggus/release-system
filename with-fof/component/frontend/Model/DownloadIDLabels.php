<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Site\Model;

defined('_JEXEC') or die();

use Akeeba\ReleaseSystem\Admin\Model\DownloadIDLabels as AdminDownloadIDLabels;
use Akeeba\ReleaseSystem\Admin\Model\Mixin\ClearCacheAfterActions;

class DownloadIDLabels extends AdminDownloadIDLabels
{
	use ClearCacheAfterActions;
}