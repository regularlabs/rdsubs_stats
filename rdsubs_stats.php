<?php
/**
 * @package            RD-Subscriptions Stats
 * @version            1.0.0
 *
 * @author             Peter van Westen <peter@nonumber.nl>
 * @link               http://www.nonumber.nl
 * @copyright          Copyright © 2012 NoNumber All Rights Reserved
 * @license            http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
JHtml::_('behavior.tabstate');

if (!JFactory::getUser()->authorise('core.manage', 'com_rdsubs_stats'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

$lang = JFactory::getLanguage();
$lang->load('com_rdsubs_stats', JPATH_ADMINISTRATOR);

require_once JPATH_ADMINISTRATOR . '/components/com_rdsubs_stats/helper.php';

$controller = JControllerLegacy::getInstance('RDSubs_Stats');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
