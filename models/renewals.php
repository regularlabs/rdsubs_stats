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

class RDSubs_StatsModelRenewals extends RDSubs_StatsModelDefault
{
	public function getData()
	{

		$to_data = array(
			'first'     => 0,
			'canceled'  => 0,
			'extension' => 0,
			'bundle'    => 0,
			'lifetime'  => 0,
		);
		$data    = (object) array(
			'extension' => (object) array('data' => (object) $to_data),
			'bundle'    => (object) array('data' => (object) $to_data),
			'lifetime'  => (object) array('data' => (object) $to_data),
		);
		$totals  = (object) array(
			'extension' => 0,
			'bundle'    => 0,
			'lifetime'  => 0,
		);

		$renewals = $this->getRenewals();

		foreach ($renewals as $row)
		{
			switch (true)
			{
				case($row->previous && !$row->level) :
					$from = $this->getLevel($row->previous);
					$to   = 'canceled';
					break;

				case(!$row->previous && $row->level) :
					$from = $this->getLevel($row->level);
					$to   = 'first';
					break;

				case($row->previous && $row->level) :
				default:
					$from = $this->getLevel($row->previous);
					$to   = $this->getLevel($row->level);
					break;
			}

			$data->{$from}->data->{$to}++;
			$totals->{$from}++;
		}

		$this->addPercentageData($data, $totals);

		return $data;
	}

	public function addPercentageData($data, $totals)
	{
		foreach ($data as $from_type => &$from)
		{
			$max = 0;
			foreach ($from->data as &$to)
			{
				$to = (object) array(
					'count'      => $to,
					'percentage' => round($to / $totals->{$from_type} * 100, 1),
				);

				if ($to->percentage > 10)
				{
					$to->percentage = round($to->percentage);
				}

				$max = max($max, $to->percentage);
			}

			$from->max = $max;
		}
	}

	public function getRenewals()
	{
		$db = JFactory::getDBO();

		$query = '
			(
				SELECT
					a.user_id as user,
					a.akeebasubs_level_id as level,
					a.publish_down as date,
					b.akeebasubs_level_id as previous

				FROM #__akeebasubs_subscriptions as a

				LEFT JOIN #__akeebasubs_subscriptions as b
				ON (
					b.user_id = a.user_id
					AND b.publish_down BETWEEN DATE_SUB(NOW(), INTERVAL 1 YEAR) AND NOW()
					AND b.state = "C"
					AND b.gross_amount > 0
				)

				WHERE a.publish_down > NOW()
				AND a.state = "C"
				AND a.gross_amount > 0
			)
			UNION
			(
				SELECT
					a.user_id as user,
					b.akeebasubs_level_id as level,
					a.publish_down as date,
					a.akeebasubs_level_id as previous

				FROM #__akeebasubs_subscriptions as a

				LEFT JOIN #__akeebasubs_subscriptions as b
				ON (
					b.user_id = a.user_id
					AND b.akeebasubs_level_id IN (1, 2, a.akeebasubs_level_id)
					AND b.publish_down > NOW()
					AND b.state = "C"
					AND b.gross_amount > 0
				)

				WHERE a.publish_down BETWEEN DATE_SUB(NOW(), INTERVAL 1 YEAR) AND NOW()
				AND a.state = "C"
				AND a.gross_amount > 0

				AND b.user_id IS NULL
			)

			ORDER BY user ASC, date ASC, level DESC
		';
		echo "\n\n<!-- ========================== \n";
		print_r((string) $query);
		echo "\n========================== -->\n\n";
		exit;

		$db->setQuery($query);

		$renewals = $db->loadObjectList('user');

		return $renewals;
	}

	public function getLevel($level)
	{
		switch ($level)
		{
			case 1:
				return 'lifetime';

			case 2:
				return 'bundle';

			default:
				return 'extension';
		}
	}
}
