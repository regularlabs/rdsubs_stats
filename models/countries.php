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

require_once __DIR__ . '/products.php';

class RDSubs_StatsModelCountries extends RDSubs_StatsModelProducts
{
	public function getData()
	{
		return $this->getDataType('countries');
	}
}
