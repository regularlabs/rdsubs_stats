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
JHtml::_('formbehavior.chosen', 'select');

JHtml::stylesheet('rdsubs_stats/style.css', false, true);
JHtml::script('rdsubs_stats/jquery.canvasjs.min.js', false, true);

$periods = array('week', 'month', 'year', 'total');

$script = 'jQuery(document).ready(function(){';
foreach ($periods as $period)
{
	$id = 'products_' . $period;
	$script .= getPieChartScriptType($id . '_value', $this->data->{$id . '_value'});
	$script .= getPieChartScriptType($id . '_count', $this->data->{$id . '_count'});
}
$script .= '});';

JFactory::getDocument()->addScriptDeclaration($script);

?>

<div class="row-fluid">
	<div class="span6 center">
		<h2>Income</h2>
		<?php foreach ($periods as $period) : ?>
			<h3><?php echo $period == 'total' ? ucfirst($period) : 'Last ' . $period; ?></h3>
			<div id="products_<?php echo $period; ?>_value" style="height: 300px; width: 100%;"></div>
		<?php endforeach; ?>
	</div>
	<div class="span6 center">
		<h2>Amount of Sales</h2>
		<?php foreach ($periods as $period) : ?>
			<h3><?php echo $period == 'total' ? ucfirst($period) : 'Last ' . $period; ?></h3>
			<div id="products_<?php echo $period; ?>_count" style="height: 300px; width: 100%;"></div>
		<?php endforeach; ?>
	</div>
</div>
