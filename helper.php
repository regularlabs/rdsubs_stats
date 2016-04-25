<?php
// No direct access
defined('_JEXEC') or die;

function getTable($by, $data, $format = 'd M', $max = 0)
{
	$html   = array();
	$html[] = '<table class="table table-striped">';
	$html[] = '<thead>';
	$html[] = '<tr>';
	$html[] = '<th class="nowrap">Day</th>';
	if ($by != 'day')
	{
		$html[] = '<th width="20%" class="nowrap text-right"></th>';
		$html[] = '<th width="10%" class="nowrap text-right">Average</th>';
	}
	$html[] = '<th width="20%" class="nowrap text-right"></th>';
	$html[] = '<th width="10%" class="nowrap text-right">Sales</th>';
	$html[] = '</tr>';
	$html[] = '</thead>';
	$html[] = '<tbody>';

	$rows = array_reverse($data->data);
	if ($max)
	{
		$rows = array_slice($rows, 0, $max);
	}

	$date = new DateTime($rows['0']->date);
	$date = $by == 'day' ? $date->format($format) . ' - ' . $date->format('l') : $date->format($format);
	$date .= ' (Projected)';

	$html[] = '<tr class="info">';

	$html[] = '<td class=""> ' . $date . '</td>';

	if ($by != 'day')
	{
		$html[] = '<td class="nowrap text-right"><span class="ghosted">' . roundCountValue($data->projection->average->count) . '</span></td>';
		$html[] = '<td class="nowrap text-right"><span class="ghosted"><strong>€ ' . number_format($data->projection->average->value) . '</strong></span></td>';
	}

	$html[] = '<td class="nowrap text-right">' . roundCountValue($data->projection->count) . '</td>';
	$html[] = '<td class="nowrap text-right"><strong>€ ' . number_format($data->projection->value) . '</strong></td>';

	$html[] = '</tr>';

	foreach ($rows as $i => $row)
	{
		$date = new DateTime($row->date);
		$date = $by == 'day' ? $date->format($format) . ' - ' . $date->format('l') : $date->format($format);
		$date .= (!$i) ? ' (Current)' : '';

		$html[] = '<tr ' . (!$i ? ' class="warning"' : '') . '>';
		$html[] = '<td class=""> ' . $date . '</td>';

		if ($by != 'day')
		{
			$html[] = '<td class="nowrap text-right"><span class="ghosted">' . roundCountValue($row->average->count) . '</span></td>';
			$html[] = '<td class="nowrap text-right"><span class="ghosted"><strong>€ ' . number_format($row->average->value) . '</strong></span></td>';
		}

		$html[] = '<td class="nowrap text-right">' . roundCountValue($row->count) . '</td>';
		$html[] = '<td class="nowrap text-right"><strong>€ ' . number_format($row->value) . '</strong></td>';
		$html[] = '</tr>';
	}

	$html[] = '</tbody>';
	$html[] = '</table>';

	return implode('', $html);
}

function getSalesTable($sales)
{
	$html   = array();
	$html[] = '<table class="table table-striped">';
	$html[] = '<tbody>';

	foreach ($sales as $row)
	{
		$date = new DateTime($row->date);

		$class = $date->format('Ymd') != date('Ymd') ? 'ghosted' : '';
		$class = $row->country_id == 'RU' && $row->title == 'Bundle' ? ' error' : $class;

		$html[] = '<tr class="' . trim($class) . '">';
		$html[] = '<td>'
			. '<img src="' . JURI::root() . 'images/flags/' . strtolower($row->country_id) . '.png" width="16" height="11" title="' . $row->country_name . '" class="hasTooltip">'
			. ' <a href="index.php?option=com_rdsubs&controller=users&task=edit&cid[]=' . $row->user_id . '">'
			. $row->user_name
			. '</a>'
			. '</td>';
		$html[] = '<td style="white-space: nowrap;" class="text-right">€ ' . round($row->price) . '</td>';
		$html[] = '<td>';
		foreach ($row->orders as $order_id => $product)
		{
			$html[] = '<a href="index.php?option=com_rdsubs&controller=subscriptions&task=edit&cid[]=' . $order_id . '">'
				. $product
				. '</a><br>';
		}
		$html[] = '</td>';
		$html[] = '<td style="white-space: nowrap;"> ' . $date->format('d M - H:s') . '</td>';
		$html[] = '</tr>';
	}

	$html[] = '</tbody>';
	$html[] = '</table>';

	return implode('', $html);
}

function getChartScript($by, $id = '', $data, $format = '', $fill_to_100 = false)
{
	$income     = $data->jsdata->data;
	$projection = $data->jsdata->projection;

	if ($data->projection->value > $data->data[count($data->data) - 1]->value)
	{
		$income[count($income) - 1] .= ', toolTipContent: "{x}<br><span style=\'\"\'color: {color};\'\"\'>{name}</span>: {y}"';
		$projection[count($projection) - 1] .= ', toolTipContent: "<span style=\'\"\'color: {color};\'\"\'>Projection</span>: {y}"';
	}

	$filtered = isset($data->filtered);
	if ($filtered)
	{
		$filtered_income = $data->filtered->jsdata->data;

		$filtered_income_percentage = array();
		$filtered_income_average    = array();
		$average                    = array();

		$filtered_key_offset = 0;
		foreach ($data->data as $i => $total_row)
		{
			$total_value = $total_row->value;

			$filtered_row = $data->filtered->data[$i - $filtered_key_offset];

			if ($filtered_row->date != $total_row->date)
			{
				$percentage = 0;
				$filtered_key_offset++;
			}
			else
			{
				$percentage = $total_value ? round($filtered_row->value / $total_value * 100, 1) : 0;
			}

			$date = date('Y', strtotime($filtered_row->date)) . ',' . (date('m', strtotime($filtered_row->date)) - 1) . ',' . date('d', strtotime($filtered_row->date));

			$filtered_income_percentage[$i] = 'x: new Date(' . $date . '), y: ' . $percentage;
			$average[]                      = array($date, $percentage);
		}

		foreach ($average as $i => $row)
		{
			$value                     = getAverageValue($average, $i, $by);
			$filtered_income_average[] = 'x: new Date(' . $row['0'] . '), y: ' . round($value, 1);
		}
	}

	$script = '
	var chart = new CanvasJS.Chart("' . $id . '",
	{
		axisY:{
			gridThickness: 1,
			gridColor: "#ddd",
			labelFontSize: 16,
			stripLines:[{
				value: ' . $data->average->value . ',
				thickness: 1,
				color:"#46a546",
				labelFontColor: "#46a546"
			}],
		},
		axisX:{
			gridThickness: 1,
			gridColor: "#ddd",
			labelFontSize: 16,
			' . ($by == 'year' ? 'interval: 1, intervalType: "year",' : '') . '
			' . ($format ? 'valueFormatString: "' . $format . '",' : '') . '
		},
		toolTip: {
			shared: "true"
		  },
		data: [';

	if ($fill_to_100 && $filtered)
	{
		$script .= '
			{
				name: "Average",
				type: "spline",
				markerType: "none",
				lineThickness: 4,
				color: "#ccc",
				toolTipContent: null,
				dataPoints: [ {' . implode('}, {', $filtered_income_average) . '} ]
			},
			{
				name: "Filtered income (percentage)_",
				type: "splineArea",
				markerSize: 0,
				lineThickness: 2,
				color: "#0064cd",
				fillOpacity: .2,
				dataPoints: [ {' . implode('}, {', $filtered_income_percentage) . '} ]
			},';
	}
	else
	{
		$script .= '
			{
				name: "Average",
				type: "spline",
				markerType: "none",
				lineThickness: 4,
				color: "#ccc",
				toolTipContent: null,
				dataPoints: [ {' . implode('}, {', $data->jsdata->average) . '} ]
			},
			{
				name: "Income",
				type: "scatter",
				markerSize: 8,
				color: "#0064cd",
				dataPoints: [ {' . implode('}, {', $income) . '} ]
			},
			{
				name: "Projection",
				type: "splineArea",
				markerSize: 0,
				color: "#0064cd",
				fillOpacity: .2,
				toolTipContent: null,
				dataPoints: [ {' . implode('}, {', $projection) . '} ]
			},';
		if ($filtered)
		{
			$script .= '
			{
				name: "Filtered income",
				type: "splineArea",
				markerSize: 0,
				lineThickness: 2,
				color: "#0064cd",
				fillOpacity: .2,
				dataPoints: [ {' . implode('}, {', $filtered_income) . '} ]
			},';
		}
	}

	$script .= '
		]
	});

	chart.render();
	';

	/*
	@blue:				  #049cdb;
	@blueDark:			  #0064cd;
	@green:				 #46a546;
	@red:				   #9d261d;
	@yellow:				#ffc40d;
	@orange:				#f89406;
	@pink:				  #c3325f;
	@purple:				#7a43b6;
	*/

	return $script;
}

function getChartScriptType($type, $id, $data, $format = '', $fill_to_100 = false)
{
	$script = '
	var chart = new CanvasJS.Chart("' . $type . '_' . $id . '",
	{
		axisY:{
			gridThickness: 1,
			gridColor: "#ddd",
			labelFontSize: 16,
		},
		axisX:{
			gridThickness: 1,
			gridColor: "#ddd",
			labelFontSize: 16,
			' . ($format ? 'valueFormatString: "' . $format . '",' : '') . '
		},
		toolTip: {
			shared: "true"
		  },
		data: [';

	foreach ($data as $dat)
	{
		array_pop($dat->jsdata->data);

		$script .= '
			{
				name: "' . $dat->title . '",
				type: "' . ($fill_to_100 ? 'stackedArea100' : 'stackedArea') . '",
				markerSize: 0,
				lineThickness: 2,
				dataPoints: [ {' . implode('}, {', $dat->jsdata->data) . '} ]
			},
			';
	}

	$script .= '
		]
	});

	chart.render();
	';

	/*
	@blue:				  #049cdb;
	@blueDark:			  #0064cd;
	@green:				 #46a546;
	@red:				   #9d261d;
	@yellow:				#ffc40d;
	@orange:				#f89406;
	@pink:				  #c3325f;
	@purple:				#7a43b6;
	*/

	return $script;
}

function getPieChartScriptType($id, $data)
{
	$script = '
	var chart = new CanvasJS.Chart("' . $id . '",
	{
		axisY:{
			gridThickness: 1,
			gridColor: "#ddd",
			labelFontSize: 16,
		},
		axisX:{
			gridThickness: 1,
			gridColor: "#ddd",
			labelFontSize: 16,
		},
		toolTip: {
			shared: "true"
		  },
		data: [
			{
				toolTipContent: "<span style=\'\"\'color: {color};\'\"\'>{name}</span><br><span style=\'\"\'color: \#999;\'\"\'>[ #percent% ]</span> {y}",
				type: "pie",
				dataPoints: [';

	foreach ($data as $dat)
	{
		$script .= '
					{
						name: "' . $dat->type . '",
						label: "' . $dat->type . '",
						y: ' . $dat->count . '
					},
			';
	}

	$script .= '
				]
			}
		]
	});

	chart.render();
	';

	/*
	@blue:				  #049cdb;
	@blueDark:			  #0064cd;
	@green:				 #46a546;
	@red:				   #9d261d;
	@yellow:				#ffc40d;
	@orange:				#f89406;
	@pink:				  #c3325f;
	@purple:				#7a43b6;
	*/

	return $script;
}

function roundCountValue($value, $max = 20)
{
	if ($value < $max)
	{
		return round($value, 1);
	}

	return round($value);
}

function getAverageValue($data, $key = 0, $by = 'day')
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
