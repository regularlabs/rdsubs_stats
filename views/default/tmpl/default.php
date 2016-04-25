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
JHtml::script('rdsubs_stats/jquery.canvasjs.min.js', false, true);

$script = 'jQuery(document).ready(function(){
    ' . getChartScript('day', 'byday', $this->data->day, 'MMM D') . '
    ' . getChartScript('month', 'bymonth', $this->data->month, 'MMM') . '
    ' . getChartScript('year', 'byyear', $this->data->year, 'YYYY') . '
  });';
JFactory::getDocument()->addScriptDeclaration($script);

?>
<div class="row-fluid">
	<div class="span7">

		<h2>Daily</h2>

		<div class="row-fluid">
			<div class="span5">
				<?php echo getTable('day', $this->data->day, 'd M', 3); ?>
			</div>
			<div class="span7">
				<div id="byday" style="height: 200px; width: 100%;"></div>
			</div>
		</div>

		<h2>Monthly</h2>

		<div class="row-fluid">
			<div class="span5">
				<?php echo getTable('month', $this->data->month, 'M', 3); ?>
			</div>
			<div class="span7">
				<div id="bymonth" style="height: 200px; width: 100%;"></div>
			</div>
		</div>

		<h2>Yearly</h2>

		<div class="row-fluid">
			<div class="span5">
				<?php echo getTable('year', $this->data->year, 'Y', 3); ?>
			</div>
			<div class="span7">
				<div id="byyear" style="height: 200px; width: 100%;"></div>
			</div>
		</div>
	</div>

	<div class="span5">
		<h2>Sales</h2>
		<?php echo getSalesTable($this->data->sales); ?>
		<a href="index.php?option=com_rdsubs&controller=subscriptions">More...</a>
	</div>
</div>
<br />
