<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Mixin\TableAssertionTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableColumnAliasTrait;
use Akeeba\Component\ARS\Administrator\Mixin\TableCreateModifyTrait;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\Database\DatabaseDriver;
use Akeeba\Component\ARS\Administrator\Mixin\EnsureUcmTrait;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;

/**
 * ARS Releases table
 *
 * @property int    $id                Primary key
 * @property int    $category_id       FK to #__ars_categories
 * @property string $version           Release title a.k.a. version
 * @property string $alias             Release alias for URL generation
 * @property string $maturity          Release maturity: 'alpha','beta','rc','stable'
 * @property string $notes             Release notes, displayed in frontend
 * @property string $hits              Hits (times displayed)
 * @property string $created           Created date and time
 * @property int    $created_by        Created by this user
 * @property string $modified          Modified date and time
 * @property int    $modified_by       Modified by this user
 * @property int    $checked_out       Checked out by this user
 * @property string $checked_out_time  Checked out date and time
 * @property int    $ordering          Front-end ordering
 * @property int    $access            Joomla view access level
 * @property int    $show_unauth_links Should I show unauthorized links?
 * @property string $redirect_unauth   Where should I redirect unauthorised access to?
 * @property int    $published         Publish state
 * @property string $language          Language code, '*' for all languages.
 */
class ReleaseTable extends AbstractTable implements TaggableTableInterface
{
	use TaggableTableTrait;
	use TableCreateModifyTrait;
	use TableAssertionTrait;
	use TableColumnAliasTrait;
	use EnsureUcmTrait;

	/**
	 * Indicates that columns fully support the NULL value in the database
	 *
	 * @var    boolean
	 * @since  7.0.0
	 */
	protected $_supportNullValue = false;

	/**
	 * Used internally by Joomla! to manage tags.
	 *
	 * @var   null|array
	 * @since 7.4.0
	 */
	public ?array $newTags;

	/**
	 * The UCM type alias. Used for tags, content versioning etc. Leave blank to effectively disable these features.
	 *
	 * @var    string
	 * @since  7.4.0
	 */
	public $typeAlias = 'com_ars.release';

	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__ars_releases', ['id'], $db);

		$this->setColumnAlias('catid', 'category_id');
		$this->setColumnAlias('title', 'version');

		$this->created_by = Factory::getApplication()->getIdentity()->id;
		$this->created    = Factory::getDate()->toSql();
		$this->access     = 1;
	}

	/**
	 * Get the type alias for the tags mapping table
	 *
	 * The type alias generally is the internal component name with the content type. Ex.: com_content.article
	 *
	 * @return  string  The alias as described above
	 *
	 * @since   7.4.0
	 */
	public function getTypeAlias(): string
	{
		return $this->typeAlias;
	}

	/**
	 * Runs after loading a record from the database
	 *
	 * @param   bool   $result  Did the record load?
	 * @param   mixed  $keys    The keys used to load the record.
	 * @param   bool   $reset   Was I asked to reset the object before loading the record?
	 *
	 * @return  void
	 *
	 * @since   7.4.0
	 */
	protected function onAfterLoad(bool &$result, $keys, bool $reset): void
	{
		// Make sure existing records have a UCM record
		if (!$result || !empty($this->id))
		{
			$this->ensureUcmRecord();
		}
	}

	protected function onBeforeCheck()
	{
		$this->assertNotEmpty($this->category_id, 'COM_ARS_RELEASE_ERR_NEEDS_CATEGORY');
		$this->assertNotEmpty($this->version, 'COM_ARS_RELEASE_ERR_NEEDS_VERSION');

		// If the alias is missing, auto-create a new one
		if (!$this->alias)
		{
			$this->alias = ApplicationHelper::stringURLSafe(strtolower($this->version));
		}

		// If no alias could be auto-generated, fail
		$this->assertNotEmpty($this->alias, 'COM_ARS_CATEGORY_ERR_NEEDS_SLUG');

		// Check alias for uniqueness
		$db    = $this->getDbo();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->quoteName('alias'),
				$db->quoteName('version'),
			])
			->from($db->quoteName('#__ars_releases'))
			->where($db->quoteName('category_id') . ' = :catid')
			->bind(':catid', $this->category_id);

		if ($this->id)
		{
			$query->where($db->qn('id') . ' != :id')
				->bind(':id', $this->id);
		}

		$existingItems = $db->setQuery($query)->loadAssocList('alias', 'version');

		$this->assertNotInArray($this->version, array_values($existingItems), 'COM_ARS_RELEASE_ERR_NEEDS_VERSION_UNIQUE');

		$this->assertNotInArray($this->alias, array_keys($existingItems), 'COM_ARS_RELEASE_ERR_NEEDS_ALIAS_UNIQUE');

		// Automatically fix the maturity
		if (!in_array($this->maturity, ['alpha', 'beta', 'rc', 'stable']))
		{
			$this->maturity = 'beta';
		}

		/**
		 * Filter the notes using a safe HTML filter.
		 *
		 * Yes, the form does filter the input BUT this table may be used outside the backend controller. This is an
		 * extra precaution to ensure we're not missing anything.
		 */
		if (!empty($this->notes))
		{
			$filter      = InputFilter::getInstance([], [], 1, 1);
			$this->notes = $filter->clean($this->notes);
		}

		// Set the default access level
		if ($this->access <= 0)
		{
			$this->access = 1;
		}

		// Clamp 'published' to [0, 1]
		$this->published = max(0, min($this->published, 1));

		// Make sure a non-empty ordering is set
		$this->ordering = $this->ordering ?? 0;
	}
}