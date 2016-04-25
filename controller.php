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

/**
 * Default manager master display controller.
 *
 * @package        Joomla.Administrator
 * @subpackage     com_rdsubs_stats
 * @since          1.6
 */
class RDSubs_StatsController extends JControllerLegacy
{
	protected $default_view = 'default';

	public function display($cachable = false, $urlparams = false)
	{
		parent::display();

		return $this;
	}
}
