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

require_once __DIR__ . '/default.php';

class RDSubs_StatsModelSales extends RDSubs_StatsModelDefault
{
	public function getData()
	{
		$data = new stdClass;

		$last_month = $this->getDate('-1 month');

		$data->day   = $this->getAllData('day', date('t') + date('t', $last_month->getTimestamp()) + 1);
		$data->month = $this->getAllData('month', 25);
		$data->year  = $this->getAllData('year', 11);
		if (JFactory::getApplication()->input->get('level') || JFactory::getApplication()->input->get('country'))
		{
			$data->day->filtered   = $this->getAllData('day', 61, 0, true);
			$data->month->filtered = $this->getAllData('month', 25, 0, true);
			$data->year->filtered  = $this->getAllData('year', 11, 0, true);
		}

		$data->levels    = $this->getLevels();
		$data->countries = $this->getCountries();

		return $data;
	}
}
