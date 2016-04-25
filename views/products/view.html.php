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

class RDSubs_StatsViewProducts extends JViewLegacy
{
	protected $items;

	public function display($tpl = null)
	{
		$this->model = $this->getModel();
		$this->data  = $this->get('Data');

		$this->model->addToolbar('COM_RDSUBS_STATS_SUBMENU_PRODUCTS');

		parent::display($tpl);
	}
}
