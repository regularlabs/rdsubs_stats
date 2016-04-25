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
 * View class for default list view
 */
class RDSubs_StatsViewRenewals extends JViewLegacy
{
	protected $items;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		exit;
		$this->model = $this->getModel();
		$this->data  = $this->get('Data');

		$this->model->addToolbar('COM_RDSUBS_STATS_SUBMENU_RENEWALS');

		parent::display($tpl);
	}
}
