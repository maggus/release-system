<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use Akeeba\ReleaseSystem\Admin\Helper\AmazonS3;
use Akeeba\ReleaseSystem\Admin\Model\Mixin;
use FOF30\Container\Container;
use FOF30\Model\DataModel;

/**
 * Model for the download Categories
 *
 * Fields:
 *
 * @property  int     $id
 * @property  string  $title
 * @property  string  $alias
 * @property  string  $description
 * @property  string  $type
 * @property  string  $groups
 * @property  string  $directory
 * @property  int     $vgroup_id
 * @property  string  $created
 * @property  string  $modified
 * @property  int     $checked_out
 * @property  string  $checked_out_time
 * @property  int     $access
 * @property  bool    $show_unauth_links
 * @property  string  $redirect_unauth
 * @property  int     $published
 * @property  string  $language
 *
 * Filters:
 *
 * @method  $this  id()                 id(int $v)
 * @method  $this  title()              title(string $v)
 * @method  $this  alias()              alias(string $v)
 * @method  $this  description()        description(string $v)
 * @method  $this  type()               type(string $v)
 * @method  $this  groups()             groups(string $v)
 * @method  $this  directory()          directory(string $v)
 * @method  $this  vgroup_id()          vgroup_id(int $v)
 * @method  $this  created()            created(string $v)
 * @method  $this  created_by()         created_by(int $v)
 * @method  $this  modified()           modified(string $v)
 * @method  $this  modified_by()        modified_by(int $v)
 * @method  $this  checked_out()        checked_out(int $v)
 * @method  $this  checked_out_time()   checked_out_time(string $v)
 * @method  $this  ordering()           ordering(int $v)
 * @method  $this  access()             access(int $v)
 * @method  $this  show_unauth_links()  show_unauth_links(bool $v)
 * @method  $this  redirect_unauth()    redirect_unauth(string $v)
 * @method  $this  published()          published(int $v)
 * @method  $this  language()           language(string $v)
 */
class Categories extends DataModel
{
	use Mixin\ImplodedArrays;
	use Mixin\Assertions;

	/**
	 * Public constructor. Overrides the parent constructor.
	 *
	 * @see DataModel::__construct()
	 *
	 * @param   Container  $container  The configuration variables to this model
	 * @param   array      $config     Configuration values for this model
	 *
	 * @throws \FOF30\Model\DataModel\Exception\NoTableColumns
	 */
	public function __construct(Container $container, array $config = array())
	{
		$config['tableName'] = '#__ars_categories';
		$config['idFieldName'] = 'id';
		$config['aliasFields'] = [
			'slug' 	      => 'alias',
			'enabled'     => 'published',
			'created_on'  => 'created',
			'modified_on' => 'modified',
			'locked_on'   => 'checked_out_time',
			'locked_by'   => 'checked_out',
		];

		parent::__construct($container, $config);

		// Relations
		$this->hasMany('release', 'Releases', 'id', 'category_id');

		// Behaviours
		$this->addBehaviour('Filters');
		$this->addBehaviour('Created');
		$this->addBehaviour('Modified');

		// Some filters we will have to handle programmatically so we need to exclude them from the behaviour
		$this->blacklistFilters([
			'vgroup_id',
			'language'
		]);
	}

	/**
	 * Implements custom filtering
	 *
	 * @param   \JDatabaseQuery  $query           The model query we're operating on
	 * @param   bool             $overrideLimits  Are we told to override limits?
	 *
	 * @return  void
	 */
	protected function onBeforeBuildQuery(\JDatabaseQuery &$query, $overrideLimits = false)
	{
		$db = $this->getDbo();

		// Visual Groups filter
		$fltVgroup = $this->getState('vgroup', null, 'int');

		if ($fltVgroup)
		{
			$query->where($db->qn('vgroup_id') . ' = ' . $db->q($fltVgroup));
		}

		// Access by user filter
		$fltAccessUser = $this->getState('access_user', null, 'int');

		if (!is_null($fltAccessUser))
		{
			$access_levels = \JFactory::getUser($fltAccessUser)->getAuthorisedViewLevels();
			$access_levels = array_map(array($db, 'quote'), $access_levels);
			$query->where($db->qn('access') . ' IN (' . implode(',', $access_levels) . ')');
		}

		// No unpublished Bleeding Edge categories filter
		$fltNoBEUnpub = $this->getState('nobeunpub', null, 'int');

		if ($fltNoBEUnpub)
		{
			$query->where('NOT(' . $db->qn('published') . ' = ' . $db->q('0') . ' AND ' .
				$db->qn('type') . '=' . $db->q('bleedingedge') . ')');
		}

		// Language filter
		$fltLanguage = $this->getState('language', null, 'cmd');
		$fltLanguage2 = $this->getState('language2', null, 'string');

		if ($fltLanguage && ($fltLanguage != '*'))
		{
			$query->where($db->qn('language') . ' IN(' . $db->q('*') . ',' . $db->q($fltLanguage) . ')');
		}
		elseif ($fltLanguage2)
		{
			$query->where($db->qn('language') . ' = ' . $db->q($fltLanguage2));
		}

		// Generic search (matching title or description) filter
		$search = $this->getState('search', null);

		if ($search)
		{
			$search = '%' . $search . '%';
			$query->where(
				'(' .
				'(' . $db->qn('title') . ' LIKE ' . $db->quote($search) . ') OR' .
				'(' . $db->qn('description') . ' LIKE ' . $db->quote($search) . ')' .
				')'
			);
		}
	}

	public function check()
	{
		$this->assertNotEmpty($this->title, 'COM_ARS_CATEGORY_ERR_NEEDS_TITLE');

		// If the alias is missing, auto-create a new one
		if (!$this->alias)
		{
			\JLoader::import('joomla.filter.input');
			$alias = str_replace(' ', '-', strtolower($this->title));
			$this->alias = (string)preg_replace('/[^A-Z0-9_-]/i', '', $alias);
		}

		// If no alias could be auto-generated, fail
		$this->assertNotEmpty($this->alias, 'COM_ARS_CATEGORY_ERR_NEEDS_SLUG');

		// Check alias for uniqueness
		$db = $this->getDBO();
		$query = $db->getQuery(true)
					->select($db->qn('alias'))
					->from($db->qn('#__ars_categories'));

		if ($this->id)
		{
			$query->where('NOT(' . $db->qn('id') . ' = ' . $db->q($this->id) . ')');
		}

		$db->setQuery($query);
		$aliases = $db->loadColumn();

		$this->assertNotInArray($this->alias, $aliases, 'COM_ARS_CATEGORY_ERR_NEEDS_UNIQUE_SLUG');

		// Check directory
		\JLoader::import('joomla.filesystem.folder');

		$this->directory = rtrim($this->directory, '/');

		if ($this->directory == 's3:')
		{
			$this->directory = 's3://';
		}

		$check = trim($this->directory);

		$this->assertNotEmpty($check, 'COM_ARS_CATEGORY_ERR_NEEDS_DIRECTORY');

		$potentialPrefix = substr($check, 0, 5);
		$potentialPrefix = strtolower($potentialPrefix);

		if ($potentialPrefix == 's3://')
		{
			$check = substr($check, 5);

			if (!empty($check))
			{
				$check .= '/';
			}

			$s3 = AmazonS3::getInstance();
			$items = $s3->getBucket('', $check);

			$this->assertNotEmpty($items, 'COM_ARS_CATEGORY_ERR_S3_DIRECTORY_NOT_EXISTS');
		}
		else
		{
			if (!\JFolder::exists($this->directory))
			{
				$directory = JPATH_SITE . '/' . $this->directory;

				$this->assert(\JFolder::exists($directory), 'COM_ARS_CATEGORY_ERR_DIRECTORY_NOT_EXISTS');
			}
		}

		// Automaticaly fix the type
		if (!in_array($this->type, array('normal', 'bleedingedge')))
		{
			$this->type = 'normal';
		}

		// Set the access to registered if there are subscriptions groups defined
		if (empty($this->access))
		{
			$this->access = 1;
		}

		if (!empty($this->groups) && ($this->access == 1))
		{
			$this->access = 2;
		}

		if (empty($this->published) && ($this->published !== 0))
		{
			$this->published = 0;
		}

		parent::check();

		return $this;
	}

	/**
	 * Checks if we are allowed to delete this record. If there are releases linked to this category then the deletion
	 * will fails with a RuntimeException.
	 *
	 * @param   int  $oid  The numeric ID of the category to delete
	 *
	 * @return  void
	 */
	function onBeforeDelete(&$oid)
	{
		$joins = array(
			array(
				'label'     => 'version',
				'name'      => '#__ars_releases',
				'idfield'   => 'id',
				'idalias'   => 'rel_id',
				'joinfield' => 'category_id'
			)
		);

		$this->canDelete($oid, $joins);
	}

	/**
	 * Converts the loaded comma-separated list of subscription levels into an array
	 *
	 * @param   string  $value  The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getGroupsAttribute($value)
	{
		return $this->getAttributeForImplodedArray($value);
	}

	/**
	 * Converts the array of subscription levels into a comma separated list
	 *
	 * @param   array  $value  The array of values
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setGroupsAttribute($value)
	{
		return $this->setAttributeForImplodedArray($value);
	}
}