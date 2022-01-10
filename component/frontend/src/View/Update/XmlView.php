<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\View\Update;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\TaskBasedEvents;
use Akeeba\Component\ARS\Site\Model\EnvironmentsModel;
use Akeeba\Component\Compatibility\Administrator\Extension\CompatibiltyComponent;
use Akeeba\Component\Compatibility\Site\Model\CompatibilityModel;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

class XmlView extends HtmlView
{
	use Common;
	use TaskBasedEvents;

	public $items = [];

	public $published = false;

	public $updates_name = '';

	public $updates_desc = '';

	public $category = 0;

	public $envs = [];

	public $showChecksums = false;

	public $filteredItemIDs = null;

	public function onBeforeDisplay()
	{
		$task = $this->getModel()->getState('task', 'all');

		if (!in_array($task, ['all', 'category', 'stream', 'jed']))
		{
			$this->doTask = 'all';
		}

		$this->document->setMimeEncoding('text/xml');

		@ob_start();
	}

	public function onAfterDisplay()
	{
		$document  = @ob_get_clean();
		$params    = Factory::getApplication()->getParams('com_ars');
		$minifyXML = $params->get('minify_xml', 1) == 1;

		$dom                     = new \DOMDocument('1.0', 'UTF-8');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput       = !$minifyXML;

		$dom->loadXML($document);
		unset($document);

		/**
		 * Insert a vanity comment in the non-minified output.
		 *
		 * I only ever use this when debugging the update stream generation. The production site always has minification
		 * turned on. Since we're charged per byte transferred for updates it makes sense :)
		 */
		if (!$minifyXML)
		{
			$rootNode = $dom->firstChild;

			$dom->removeChild($rootNode);

			$comment = $dom->createComment(sprintf('Generated by Akeeba Release System on %s', (new Date())->format('Y-m-d H:i:s T')));

			$dom->insertBefore($comment);
			$dom->insertBefore($rootNode);
		}

		echo $dom->saveXML();
	}

	protected function onBeforeAll(): void
	{
		$this->commonSetup();

		$params = Factory::getApplication()->getParams('com_ars');

		$this->updates_name = $params->get('updates_name', '');
		$this->updates_desc = $params->get('updates_desc', '');

		$this->setLayout('all');
	}

	protected function onBeforeCategory(): void
	{
		$this->commonSetup();

		$this->setLayout('category');
	}

	protected function onBeforeStream(): void
	{
		$this->commonSetup();

		/** @var EnvironmentsModel $envModel */
		$envModel = $this->getModel('Environments');
		$params   = Factory::getApplication()->getParams('com_ars');

		$this->envs          = $envModel->getEnvironmentXMLTitles();
		$this->showChecksums = $params->get('show_checksums', 0) == 1;

		$this->setLayout('stream');

		/**
		 * Use Version Compatibility information to cut down the number of displayed versions?
		 */
		if ($params->get('use_compatibility', 1) == 1)
		{
			$this->applyVersionCompatibilityUpdateStreamFilter();
		}
	}

	protected function applyVersionCompatibilityUpdateStreamFilter(): void
	{
		if (!ComponentHelper::isEnabled('com_compatibility'))
		{
			return;
		}

		if (empty($this->category))
		{
			return;
		}

		try
		{
			/** @var SiteApplication $app */
			$app = Factory::getApplication();
			/** @var CompatibiltyComponent $compatComponent */
			$compatComponent = $app->bootComponent('com_compatibility');

			if (!class_exists('Akeeba\Component\Compatibility\Administrator\Extension\CompatibiltyComponent'))
			{
				return;
			}

			if (!($compatComponent instanceof CompatibiltyComponent))
			{
				return;
			}
		}
		catch (\Exception $e)
		{
			return;
		}

		$alias = $this->getModel()->getCategoryAliasForUpdateId($this->category);

		/** @var CompatibilityModel $compatModel */
		$compatModel = $compatComponent->getMVCFactory()->createModel('Compatibility');
		$displayData = $compatModel->getDisplayData();

		$displayData = array_filter($displayData, function ($extensionData) use ($alias) {
			return $extensionData['slug'] == $alias;
		});

		if (empty($displayData))
		{
			return;
		}

		$extensionData         = array_pop($displayData);
		$this->filteredItemIDs = [];

		foreach ($extensionData['matrix'] as $jVersion => $perPHPVersion)
		{
			foreach ($perPHPVersion as $phpVersion => $versionInfo)
			{
				if (empty($versionInfo))
				{
					continue;
				}

				$id = $versionInfo['id'] ?? null;

				if (empty($id))
				{
					continue;
				}

				$this->filteredItemIDs[] = $id;
			}
		}

		$this->filteredItemIDs = array_unique($this->filteredItemIDs);
	}

	private function platformVersionCompactor(array $versions): string
	{
		$byMajor     = [];
		$retVersions = [];

		foreach ($versions as $v)
		{
			$parts = explode('.', $v, 3);

			// If the last version part is a star we can toss it – it's the default behavior in Joomla.
			if ((count($parts) == 3) && ($parts[2] == '*'))
			{
				array_pop($parts);
			}

			// Three part version. This will be a separate entry. I can't compact oddball versions like that.
			if (count($parts) == 3)
			{
				$retVersions[] = $v;

				continue;
			}

			// Someone is stupid enough to only specify a major version. Let me fix that for you.
			if (count($parts) == 1)
			{
				$parts[] = '*';
			}

			[$major, $minor] = $parts;

			// Did someone specify ".*"?! OK, we will tell Joomla to install no matter the version. You're insane...
			if (empty($major) && ($minor == '*'))
			{
				$byMajor = ['*' => '*'];

				break;
			}

			$byMajor[$major] = $byMajor[$major] ?? [];

			// Has someone already specified "all versions" for this major version?
			if (in_array('*', $byMajor[$major]))
			{
				continue;
			}

			// Someone specified "all versions" for this major version. OK, then.
			if ($minor == '*')
			{
				$byMajor[$major] = ['*'];

				continue;
			}

			// Add a minor version to this major
			$byMajor[$major][] = $minor;
		}

		// Special case: all major and minor versions (overrides everything else)
		if (($byMajor['*'] ?? []) == ['*'])
		{
			return '.*';
		}

		// Add version RegEx by major version
		foreach ($byMajor as $major => $minorVersions)
		{
			// Special case: no minor version (how the heck...?)
			if (!count($minorVersions))
			{
				continue;
			}

			// Special case: all minor versions for this major version
			if ($minorVersions == ['*'])
			{
				$retVersions[] = $major;

				continue;
			}

			// Special case: just one minor version
			if (count($minorVersions) == 1)
			{
				$retVersions[] = sprintf('%s\.%s', $major, array_shift($minorVersions));

				continue;
			}

			$retVersions[] = sprintf('%s\.(%s)', $major, implode('|', $minorVersions));
		}

		// Special case: only one version regEx supported
		if (count($retVersions) == 1)
		{
			return array_pop($retVersions);
		}

		return '(' . implode('|', array_map(function ($regex) {
				return sprintf('(%s)', $regex);
			}, $retVersions)) . ')';
	}
}