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

JHtml::_('behavior.tooltip');

JHtml::stylesheet('rdsubs_stats/style.css', false, true);

echo generateTable($this->data, 'extension');
echo generateTable($this->data, 'bundle');
echo "\n\n<!-- ========================== \n";
print_r($this->data);
echo "\n========================== -->\n\n";

function generateTable($data, $type)
{
	$html   = array();
	$html[] = '<table class="table">';

	foreach ($data->{$type}->data as $to => $to_data)
	{
		$badge = getBadge($type);
		if ($to != 'first')
		{
			$badge .=
				' <span class="icon-arrow-right"></span> '
				. getBadge($to);
		}

		$html[] = '<tr>';
		$html[] = '<td width="10%" class="nowrap">';
		$html[] = $badge;
		$html[] = '</td>';
		$html[] = '<td width="5%" class="nowrap">';
		$html[] = $to_data->count;
		$html[] = '</td>';
		$html[] = '<td width="5%" class="nowrap">';
		$html[] = $to_data->percentage . ' %';
		$html[] = '</td>';
		$html[] = '<td>';
		$html[] = generateBar($to, $to_data->percentage, $data->{$type}->max);
		$html[] = '</td>';
		$html[] = '</tr>';
	}
	$html[] = '</table>';

	return implode('', $html);
}

function getBadge($type)
{
	switch ($type)
	{
		case 'canceled':
			return '<span class="badge badge-important hasTooltip" title="No renewal">X</span>';

		case 'extension':
			return '<span class="badge badge-warning hasTooltip" title="Single extension subscription">Single</span>';

		case 'bundle':
			return '<span class="badge badge-success hasTooltip" title="Bundle subscription">Bundle</span>';

		case 'lifetime':
			return '<span class="badge badge-info badge-primary hasTooltip" title="Lifetime Bundle subscription">Lifetime</span>';
	}
}

function generateBar($type, $percentage, $max)
{
	switch ($type)
	{
		case 'canceled':
			$class = 'important progress-danger';
			break;

		case 'extension':
			$class = 'warning';
			break;

		case 'bundle':
			$class = 'success';
			break;

		case 'lifetime':
			$class = 'primary';
			break;

		default:
			$class = 'info';
			break;
	}

	$width = $percentage / $max * 100;

	return
		'<div class="progress progress-' . $class . '" style="margin: 0;">'
		. '<div class="bar" style="width: ' . $width . '%"></div>'
		. '</div>';
}
