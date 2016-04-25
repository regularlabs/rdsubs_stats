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

class RDSubs_StatsModelProducts extends RDSubs_StatsModelDefault
{
	public function getData()
	{
		return $this->getDataType('products');
	}

	public function getDataType($type = 'products')
	{
		$data = new stdClass;

		$periods = array('week', 'month', 'year', 'total');
		foreach ($periods as $period)
		{
			$id                     = $type . '_' . $period;
			$data->{$id . '_value'} = $this->getDataBy($type, $period);
			$data->{$id . '_count'} = $this->getDataBy($type, $period, true);
		}

		return $data;
	}

	public function getDataBy($type = 'products', $period = 'week', $use_count = false)
	{
		$db = JFactory::getDBO();

		$from = $this->getDate($period == 'total' ? '-20 years' : '-1 ' . $period);
		$to   = $this->getDate('+1 day');

		$count = $use_count ? 'COUNT(o.id)' : 'ROUND(SUM(o.net_price), 2) * 1';

		$query = $db->getQuery(true)
			->select($count . ' as count')
			->from($db->quoteName('#__rd_subs_orders', 'o'))
			->join('LEFT', $db->quoteName('#__rd_subs_invoices', 'i') . ' ON i.ordercode = o.ordercode')
			->where(array(
				'o.date > ' . $db->quote($from->format('Y-m-d')),
				'o.date < ' . $db->quote($to->format('Y-m-d')),
				'o.net_price > 0',
				'i.id IS NOT NULL',
				'i.paid = 1',
				'i.refund_reason = ' . $db->quote(''),
			))
			->order('count DESC');

		switch ($type)
		{
			case 'products':
				$query
					->select('p.name as type')
					->join('INNER', $db->quoteName('#__rd_subs_products', 'p') . ' ON p.id = o.product_id');

				$db->setQuery($query);
				break;

			case 'countries':
				$query
					->select('c.country as type')
					->join('INNER', $db->quoteName('#__rd_subs_users', 'u') . ' ON u.userid = o.userid')
					->join('INNER', $db->quoteName('#__rd_subs_countries', 'c') . ' ON c.id = u.country');
				break;
		}

		$db->setQuery($query);
		$total = $db->loadObjectList();
		$total = $total['0']->count;

		$query->group('type');

		$db->setQuery($query);

		$data = $db->loadObjectList();

		$min_count = 20;
		if (count($data) < $min_count)
		{
			return $data;
		}

		$min_value    = $total / 50; // 2%
		$others_types = array();
		$others_count = 0;

		$new_data = array();

		foreach ($data as $row)
		{
			if ($row->count >= $min_value)
			{
				$new_data[] = $row;
				continue;
			}

			$others_types[] = $row->type;
			$others_count += $row->count;
		}

		$new_data[] = (object) array(
			'type'  => 'Others' . (count($others_types) < 5 ? ': ' . implode(', ', $others_types) : ''),
			'count' => $others_count,
		);

		return $new_data;
	}

}
