<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Extension;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Administrator\CustomCategory\ARSPseudoCategory;
use Akeeba\Component\ARS\Administrator\CustomCategory\ARSReleaseNodeProvider;
use Akeeba\Component\ARS\Administrator\Service\Html\AkeebaReleaseSystem;
use Joomla\CMS\Categories\CategoryInterface;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Fields\FieldsServiceInterface;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Tag\TagServiceInterface;
use Joomla\CMS\Tag\TagServiceTrait;
use Psr\Container\ContainerInterface;

class ArsComponent extends MVCComponent implements
	BootableExtensionInterface, CategoryServiceInterface, RouterServiceInterface,
	TagServiceInterface, FieldsServiceInterface
{
	use HTMLRegistryAwareTrait;
	use RouterServiceTrait;
	use CategoryServiceTrait
	{
		CategoryServiceTrait::getTableNameForSection insteadof TagServiceTrait;
		CategoryServiceTrait::getStateColumnForSection insteadof TagServiceTrait;
	}
	use TagServiceTrait;

	public function boot(ContainerInterface $container)
	{
		$this->getRegistry()->register('ars', new AkeebaReleaseSystem());
	}

	/**
	 * Returns a CategoryInterface object. Used for custom fields management.
	 *
	 * The CategoryInterface object implements a single method, get(), which returns a CategoryNode. This is used to
	 * attach fields to a category.
	 *
	 * @param   array   $options
	 * @param   string  $section
	 *
	 * @return  CategoryInterface
	 * @since   7.4.0
	 */
	public function getCategory(array $options = [], $section = ''): CategoryInterface
	{
		return new ARSPseudoCategory($options);
	}

	/**
	 * Returns valid contexts.
	 *
	 * Used for custom fields.
	 *
	 * @return  array
	 *
	 * @since   7.4.0
	 */
	public function getContexts(): array
	{
		Factory::getApplication()->getLanguage()->load('com_ars', JPATH_ADMINISTRATOR);

		return [
			'com_ars.release'  => Text::_('COM_ARS_TITLE_RELEASES'),
			'com_ars.category' => Text::_('COM_ARS_TITLE_CATEGORIES'),
		];
	}

	/**
	 * If the section is valid it returns it, otherwise returns null.
	 *
	 * Used for custom fields.
	 *
	 * @param   string  $section  The section to get the mapping for
	 * @param   object  $item     The item
	 *
	 * @return  string|null  The new section
	 *
	 * @since   7.4.0
	 */
	public function validateSection($section, $item = null)
	{
		$validSections = array_map(
			fn(string $x) => explode('.', $x)[1],
			array_keys($this->getContexts())
		);

		return in_array(strtolower($section), $validSections) ? $section : null;
	}

	/** @inheritdoc */
	protected function getStateColumnForSection(string $section = null)
	{
		return 'published';
	}

	/** @inheritdoc */
	protected function getTableNameForSection(string $section = null)
	{
		return match (strtolower($section))
		{
			'release' => 'ats_releases',
			'category' => 'ats_categories',
		};
	}
}