<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

$editor =& JFactory::getEditor();
?>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<input type="hidden" name="option" value="<?php echo JRequest::getCmd('option') ?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getCmd('view') ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="id" value="<?php echo $this->item->id ?>" />

	<fieldset>
		<legend><?php echo JText::_('LBL_ARS_AUTODESC_BASIC'); ?></legend>

		<div class="editform-row">
			<label for="category"><?php echo JText::_('LBL_AUTODESC_CATEGORY'); ?></label>
			<?php echo ArsHelperSelect::categories($this->item->category, 'category') ?>
		</div>
		<div class="editform-row">
			<label for="packname"><?php echo JText::_('LBL_AUTODESC_PACKNAME'); ?></label>
			<input type="text" name="packname" id="packname" value="<?php echo $this->item->packname ?>">
		</div>
		<div class="editform-row">
			<label for="title"><?php echo JText::_('LBL_AUTODESC_TITLE'); ?></label>
			<input type="text" name="title" id="title" value="<?php echo $this->item->title ?>">
		</div>
		<div class="editform-row">
			<label for="published"><?php echo JText::_('PUBLISHED'); ?></label>
			<div>
				<?php echo JHTML::_('select.booleanlist', 'published', null, $this->item->published); ?>
			</div>
		</div>
		<div style="clear:left"></div>
	</fieldset>

	<fieldset>
		<legend><?php echo JText::_('LBL_AUTODESC_DESCRIPTION'); ?></legend>
		<?php echo $editor->display( 'description',  $this->item->description, '600', '350', '60', '20', array() ) ; ?>
	</fieldset>
</form>