<?php
/**
 * @package            RD-Subscriptions Stats
 * @version            0.1.0
 *
 * @author             Peter van Westen <peter@nonumber.nl>
 * @link               http://www.nonumber.nl
 * @copyright          Copyright © 2012 NoNumber All Rights Reserved
 * @license            http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

class RDSubs_StatsModelDefault extends JModelList
{
	var $projection_average = null;
	var $countries          = null;
	var $levels             = null;
	var $db_data            = array();
	var $time_offset        = null;

	public function __construct($config = array())
	{
		$this->time_offset = $this->getTimeOffset();

		parent::__construct($config);
	}

	public function getTimeOffset()
	{
		$config = JFactory::getConfig();
		$user   = JFactory::getUser();

		$date_server    = new JDate(date('Y-m-d H:i:s'));
		$date_server->setTimezone(new DateTimeZone($user->getParam('timezone', $config->get('offset'))));

		$date_now    = new JDate(date('Y-m-d H:i:s'));

		$diff = strtotime((string) $date_server) - strtotime((string) $date_now);
		$diff = $diff / 60 / 60;

		return $diff . ' HOUR';
	}

	public function getData()
	{
		$data = new stdClass;

		$data->day   = $this->getAllData('day', 15);
		$data->month = $this->getAllData('month', 15);
		$data->year  = $this->getAllData('year', 5);

		$data->sales = $this->getSalesData();

		return $data;
	}

	public function getDataObject($data, $by = 'day', $filtered = false)
	{
		if (empty($data))
		{
			$data = array();
		}

		$last = empty($data) ? null : clone end($data);

		$this->setDataAverages($data, $by);

		$projection = $filtered || !$last ? null : $this->getProjection($last, $by);

		$average = $filtered || !$last ? null : $this->getAverage($data);

		$jsdata = $this->getJavascriptData($data, $projection, $by, $filtered);

		return (object) array(
			'data'       => $data,
			'jsdata'     => $jsdata,
			'projection' => $projection,
			'average'    => $average,
		);
	}

	public function getAllData($by = 'day', $amount = 15, $to_offset = 0, $filtered = false, $getObject = true)
	{
		switch ($by)
		{
			case 'year':
				$date_format  = '%Y-01-01';
				$group_format = '%Y';

				$from = $this->getDate();
				$from->setDate(gmdate('Y'), 1, 1);
				$from->modify('-' . ($amount - 1) . ' years');
				break;

			case 'month':
				$date_format  = '%Y-%m-01';
				$group_format = '%Y.%m';

				$from = $this->getDate();
				$from->setDate(gmdate('Y'), gmdate('m'), 1);
				$from->modify('-' . ($amount - 1) . ' months');
				break;

			case 'day':
			default:
				$date_format  = '%Y-%m-%d';
				$group_format = '%Y-%m-%d';

				$from = $this->getDate('-' . ($amount - 1) . ' days');

				break;
		}

		$to = $this->getDate('+' . (1 + $to_offset) . ' day');

		$data = $this->getAllDataFromDb($from->format('Y-m-d'), $to->format('Y-m-d'), $date_format, $group_format, $filtered);

		$to = $this->getDate('-1 day', $to);
		$this->fillMissingDates($data, $by, $from, $to, $date_format, $group_format);

		if ($getObject)
		{
			$data = $this->getDataObject($data, $by, $filtered);
		}

		return $data;
	}

	public function getAllDataFromDb($from, $to, $date_format, $group_format, $filtered = false)
	{
		$id = md5($from . '_' . $to . '_' . $date_format . '_' . $group_format . '_' . $filtered);

		if (isset($this->db_data[$id]))
		{
			return $this->db_data[$id];
		}

		$db = JFactory::getDBO();

		$query = $db->getQuery(true)
			->select(array(
				'DATE_FORMAT(DATE_ADD(i.invoicedate, INTERVAL ' . $this->time_offset . '), ' . $db->quote($group_format) . ') as grouping',
				'DATE_FORMAT(DATE_ADD(i.invoicedate, INTERVAL ' . $this->time_offset . '), ' . $db->quote($date_format) . ') as date',
				'COUNT(i.id) as count',
				'ROUND(SUM(i.net_price) - SUM(i.vat_amount), 2) * 1 as value',
			))
			->from($db->quoteName('#__rd_subs_invoices', 'i'))
			->where(array(
				'DATE_ADD(i.invoicedate, INTERVAL ' . $this->time_offset . ') > ' . $db->quote($from),
				'DATE_ADD(i.invoicedate, INTERVAL ' . $this->time_offset . ') < ' . $db->quote($to),
				'i.paid = 1',
				'i.net_price > 0',
				'i.refund_invoice = 0',
				'i.refund_reason = ' . $db->quote(''),
			))
			->group('grouping');

		if ($filtered && $level = JFactory::getApplication()->input->get('level'))
		{
			//$query->where('o.product_id = ' . $level);
		}

		if ($filtered && $country = JFactory::getApplication()->input->get('country'))
		{
			$query
				->join('INNER', $db->quoteName('#__rd_subs_users', 'u') . ' ON u.userid = i.userid')
				->join('INNER', $db->quoteName('#__rd_subs_countries', 'c') . ' ON c.id = u.country')
				->where('c.country_2_code = ' . $db->quote($country));
		}

		$db->setQuery($query);

		$this->db_data[$id] = $db->loadObjectList('grouping');

		return $this->db_data[$id];
	}

	public function fillMissingDates(&$data, $by, $from, $to, $date_format, $group_format, $fill_all = false)
	{
		$from = clone $from;

		if (empty($data))
		{
			$data = array();
		}

		$date_format  = str_replace('%', '', $date_format);
		$group_format = str_replace('%', '', $group_format);

		$start = (bool) $fill_all;
		while ($from->format($group_format) <= $to->format($group_format))
		{
			$id = $from->format($group_format);
			if (isset($data[$id]))
			{
				$start = 1;
			}

			if ($start && !isset($data[$id]))
			{
				$data[$id] = (object) array(
					'grouping' => $id,
					'date'     => $from->format($date_format),
					'count'    => 0,
					'value'    => 0,
				);
			}

			$from->modify('+1 ' . $by);
		}

		ksort($data);

		$data = array_values($data);
	}

	public function setDataAverages(&$data, $by = 'day')
	{
		foreach ($data as $i => &$row)
		{
			$last = ($i == count($data) - 1);
			switch ($by)
			{
				case 'year':
					$numberofdays = $last ? date('z') + 1 : date('z', strtotime(date(substr($row->date, 0, 4) . '-12-31'))) + 1;
					break;

				case 'month':
					$numberofdays = $last ? date('j') : date('t', strtotime($row->date));
					break;

				case 'day':
				default:
					$numberofdays = '1';
					break;
			}

			$row->average = (object) array(
				'count' => round($row->count / $numberofdays, 1),
				'value' => round($row->value / $numberofdays),
			);
		}
	}

	public function getProjectionAverage()
	{
		if (isset($this->projection_average))
		{
			return clone $this->projection_average;
		}

		$data = $this->getAllData('day', 90, -1, false, false);

		$count  = 0;
		$value  = 0;
		$weight = 0;
		$ratio  = 0;
		foreach ($data as $row)
		{
			$weight++;
			$count += $row->count * $weight;
			$value += $row->value * $weight;
			$ratio += $weight;
		}

		$count   = $count / $ratio;
		$value   = $value / $ratio;
		$average = $value / $count;

		$this->projection_average = (object) array(
			'count' => round($count, 1),
			'value' => round($value),
			'avg'   => round($average),
		);

		$this->projection_average->average = (object) array(
			'count' => round($count, 1),
			'value' => round($value),
		);

		return clone $this->projection_average;
	}

	public function getAverage($data)
	{
		$count = 0;
		$value = 0;
		foreach ($data as $row)
		{
			$count += $row->count;
			$value += $row->value;
		}

		return (object) array(
			'count' => round($count / count($data), 1),
			'value' => round($value / count($data)),
		);
	}

	public function getProjectionAverageByType($by = 'day')
	{
		$average = $this->getProjectionAverage();

		switch ($by)
		{
			case 'year':
				$number_of_days = date('z', strtotime(date('Y-12-31'))) + 1;
				break;

			case 'month':
				$number_of_days = date('t');
				break;

			case 'day':
			default:
				$number_of_days = '1';
				break;
		}

		if ($number_of_days == 1)
		{
			return $average;
		}

		$average->count = $average->count * $number_of_days;
		$average->value = $average->value * $number_of_days;
		$average->avg   = $average->value / $average->count;

		return $average;
	}

	public function getProjection($data, $by = 'day')
	{
		$average = $this->getProjectionAverageByType($by);

		switch ($by)
		{
			case 'year':
				$days_total     = date('z', strtotime(date('Y-12-31'))) + 1;
				$days_remaining = $days_total - (date('z') + 1);
				break;

			case 'month':
				$days_total     = date('t');
				$days_remaining = $days_total - date('j');
				break;

			case 'day':
			default:
				$days_total = 1;
				$count      = $data->count;
				$value      = $data->value;

				if ($value < $average->value)
				{
					$diff       = $average->value - $data->value;
					$diff_count = round($diff / $average->avg);

					$count += $diff_count;
					$value += $diff_count * $average->avg;
				}

				if ($count < $average->count)
				{
					$count = round($average->count);

					$value = $data->value + ($average->avg * (round($average->count) - $data->count));
				}

				$count = max($count, $average->count);
				$value = max($value, $average->value);
				break;
		}

		if ($by != 'day')
		{
			$day_average = $this->getProjectionAverageByType('day');
			$last_day    = $this->getAllData('day', 1, 0, false, false);

			$count = $data->count;
			$value = $data->value;

			if (isset($last_day['0']))
			{
				if ($last_day['0']->count < $day_average->count)
				{
					$count -= $last_day['0']->count;
					$count += $day_average->count;
				}

				if ($last_day['0']->value < $day_average->value)
				{
					$value -= $last_day['0']->value;
					$value += $day_average->value;
				}
			}

			$count += $day_average->count * $days_remaining;
			$value += $day_average->value * $days_remaining;
		}

		$data->next_count = $average->count;
		$data->next_value = $average->value;

		$data->count = $count;
		$data->value = $value;

		$data->average = (object) array(
			'count' => $average->average->count,
			'value' => $average->average->value,
		);

		$data->average = (object) array(
			'count' => $data->count / $days_total,
			'value' => $data->value / $days_total,
		);

		return $data;
	}

	public function getJavascriptData($data, $projection, $by = 'day', $filtered = false)
	{
		$js_data       = array();
		$js_projection = array();
		$js_average    = array();
		$average       = array();

		$last = count($data) - 1;
		foreach ($data as $i => $row)
		{
			$date = date('Y', strtotime($row->date)) . ',' . (date('m', strtotime($row->date)) - 1) . ',' . date('d', strtotime($row->date));

			$js_data[] = 'x: new Date(' . $date . '), y: ' . round($row->value);

			if ($filtered)
			{
				continue;
			}

			$value           = ($i >= $last) && isset($projection) ? $projection->value : $row->value;
			$js_projection[] = 'x: new Date(' . $date . '), y: ' . round($value);

			$average[] = array($date, round($value));
		}

		if (!$filtered)
		{
			foreach ($average as $i => $row)
			{
				$value        = $this->getAverageValue($average, $i, $by);
				$js_average[] = 'x: new Date(' . $row['0'] . '), y: ' . round($value);
			}
		}

		return (object) array(
			'data'       => $js_data,
			'projection' => $js_projection,
			'average'    => $js_average,
		);
	}

	public function getAverageValue($data, $key = 0, $by = 'day')
	{
		switch ($by)
		{
			case 'year':
				$range = 2;
				break;

			case 'month':
				$range = 4;
				break;

			case 'day':
			default:
				$range = 5;
				break;
		}

		$value  = 0;
		$weight = 0;
		$ratio  = 0;

		for ($i = $range; $i >= 0; $i--)
		{
			$weight++;
			if (isset($data[$key - $i]))
			{
				$value += $data[$key - $i]['1'] * $weight;
				$ratio += $weight;
			}
			if ($i && isset($data[$key + $i]))
			{
				$value += $data[$key + $i]['1'] * $weight;
				$ratio += $weight;
			}
		}

		if (!$ratio)
		{
			return 0;
		}

		$value = $value / $ratio;

		return $value;
	}

	public function getDate($modify = '', $date = null)
	{
		if (!$date)
		{
			$date = new DateTime();
			$date->setDate(gmdate('Y'), gmdate('m'), gmdate('d'));
			$date->setTime(0, 0, 0);
		}
		if ($modify)
		{
			$date = clone $date;
			$date->modify($modify);
		}

		return $date;
	}

	public function getSalesData()
	{
		$db = JFactory::getDBO();

		$query = $db->getQuery(true)
			->select('i.id')
			->from($db->quoteName('#__rd_subs_invoices', 'i'))
			->where(array(
				'i.paid = 1',
				'i.net_price > 0',
				'i.refund_invoice = 0',
				'i.refund_reason = ' . $db->quote(''),
			))
			->order('i.invoicedate DESC');
		$db->setQuery($query, 0, 20);

		$invoice_ids = $db->loadColumn();

		$query = $db->getQuery(true)
			->select(array(
				'i.id as invoice_id',
				'i.ordercode',
				'DATE_ADD(i.invoicedate, INTERVAL ' . $this->time_offset . ') as date',
				'i.disco_price as discount',
				'i.gross_price - i.disco_price as price',
				'o.discount_group',
				'o.coupon_code',
				'x.name as coupon_name',
				'u.id as user_id',
				'CONCAT(u.firstname, " ", u.lastname) as user_name',
				'c.country as country_name',
				'c.country_2_code as country_id',
			))
			->from($db->quoteName('#__rd_subs_invoices', 'i'))
			->join('LEFT', $db->quoteName('#__rd_subs_orders', 'o') . ' ON o.ordercode = i.ordercode AND o.discount_group != ' . $db->quote(''))
			->join('LEFT', $db->quoteName('#__rd_subs_coupons', 'x') . ' ON x.coupon_code = o.coupon_code AND x.type = 0')
			->join('LEFT', $db->quoteName('#__rd_subs_users', 'u') . ' ON u.userid = i.userid')
			->join('LEFT', $db->quoteName('#__rd_subs_countries', 'c') . ' ON c.id = u.country')
			->where('i.id IN (' . implode(',', $invoice_ids) . ')')
			->order('i.invoicedate DESC')
			->group('i.id');
		$db->setQuery($query);

		$invoices = $db->loadObjectList();

		foreach ($invoices as $i => $invoice)
		{

			$query = $db->getQuery(true)
				->select(array(
					'p2u.id',
					'p.name as title',
				))
				->from($db->quoteName('#__rd_subs_orders', 'o'))
				->join('LEFT', $db->quoteName('#__rd_subs_product2user', 'p2u') . ' ON p2u.order_id = o.id AND p2u.product_id = o.product_id')
				->join('LEFT', $db->quoteName('#__rd_subs_products', 'p') . ' ON p.id = o.product_id')
				->where('o.ordercode  = ' . $invoice->ordercode)
				->where('o.product_id > 0')
				->order('p.name ASC');
			$db->setQuery($query);

			$invoice->orders = $db->loadAssocList('id', 'title');
		}

		return $invoices;
	}

	/*public function getLevels()
	{
		if (isset($this->levels))
		{
			return $this->levels;
		}

		$db = JFactory::getDBO();

		$query = $db->getQuery(true)
			->select(array(
				'l.akeebasubs_level_id as id',
				'l.title',
			))
			->from('#__akeebasubs_levels as l')
			->where('l.enabled = 1')
			->order('l.ordering ASC');
		$db->setQuery($query);

		return $db->loadObjectList('id');
	}*/

	public function getCountryById($id)
	{
		$countries = $this->getCountries();

		return isset($countries[$id]) ? $countries[$id] : $id;
	}

	function getCountries()
	{
		if (isset($this->countries))
		{
			return $this->countries;
		}

		$db = JFactory::getDBO();

		$query = $db->getQuery(true)
			->select(array(
				'c.country',
				'c.country_2_code',
			))
			->from($db->quoteName('#__rd_subs_transactions', 't'))
			->join('INNER', $db->quoteName('#__rd_subs_users', 'u') . ' ON u.userid = t.userid')
			->join('INNER', $db->quoteName('#__rd_subs_countries', 'c') . ' ON c.id = u.country')
			->group('c.country')
			->order('c.country');

		$db->setQuery($query);

		$this->countries = $db->loadAssocList('country_2_code', 'country');

		return $this->countries;
	}

	function addToolbar($sub_title)
	{
		$title = JText::_('com_rdsubs_stats');
		$title .= $sub_title ? ' - ' . JText::_($sub_title) : '';

		JFactory::getDocument()->setTitle($title);
		JToolBarHelper::title($title);

		$bar = JToolBar::getInstance('toolbar');

		/*$bar->appendButton(
			'custom',
			'<a href="index.php?option=com_rdsubs_stats" class="btn btn-default">'
			. '<span class="icon-dashboard"></span> '
			. JText::_('COM_RDSUBS_STATS_SUBMENU_DASHBOARD')
			. '</a>'
		);
		$bar->appendButton(
			'custom',
			'<a href="index.php?option=com_rdsubs_stats&view=sales" class="btn btn-default">'
			. '<span class="icon-basket"></span> '
			. JText::_('COM_RDSUBS_STATS_SUBMENU_SALES')
			. '</a>'
		);
		$bar->appendButton(
			'custom',
			'<a href="index.php?option=com_rdsubs_stats&view=renewals" class="btn btn-default">'
			. '<span class="icon-refresh"></span> '
			. JText::_('COM_RDSUBS_STATS_SUBMENU_RENEWALS')
			. '</a>'
		);
		$bar->appendButton(
			'custom',
			'<a href="index.php?option=com_rdsubs_stats&view=products" class="btn btn-default">'
			. '<span class="icon-cube"></span> '
			. JText::_('COM_RDSUBS_STATS_SUBMENU_PRODUCTS')
			. '</a>'
		);
		$bar->appendButton(
			'custom',
			'<a href="index.php?option=com_rdsubs_stats&view=countries" class="btn btn-default">'
			. '<span class="icon-location"></span> '
			. JText::_('COM_RDSUBS_STATS_SUBMENU_COUNTRIES')
			. '</a>'
		);*/
		$bar->appendButton(
			'custom',
			'<a href="index.php?option=com_rdsubs" class="btn btn-default">'
			. '<span class="icon-arrow-right"></span> '
			. JText::_('COM_RDSUBS')
			. '</a>'
			, 'right'
		);
	}
}
