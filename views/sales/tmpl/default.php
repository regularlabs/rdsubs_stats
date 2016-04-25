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

$filter_level   = JFactory::getApplication()->input->get('level');
$filter_country = JFactory::getApplication()->input->get('country');

JHtml::_('behavior.tooltip');
JHtml::_('formbehavior.chosen', 'select');

JHtml::stylesheet('rdsubs_stats/style.css', false, true);
JHtml::script('rdsubs_stats/jquery.canvasjs.min.js', false, true);

$script = 'jQuery(document).ready(function(){
    ' . getChartScript('day', 'byday', $this->data->day, 'MMM D') . '
    ' . getChartScript('month', 'bymonth', $this->data->month, 'MMM') . '
    ' . getChartScript('year', 'byyear', $this->data->year, 'YYYY') . '
  });';
if ($filter_country || $filter_level)
{
	$script .= 'jQuery(document).ready(function(){
    ' . getChartScript('day', 'byday_100', $this->data->day, 'MMM D', true) . '
    ' . getChartScript('month', 'bymonth_100', $this->data->month, 'MMM', true) . '
    ' . getChartScript('year', 'byyear_100', $this->data->year, 'YYYY', true) . '
  });';
}
JFactory::getDocument()->addScriptDeclaration($script);

?>
<form
	action="<?php echo JRoute::_('index.php?option=com_rdsubs_stats&view=sales'); ?>"
	method="post" name="adminForm" id="module-form" class="form-validate">
	<select name="level" class="inputbox" onchange="this.form.submit()">
		<option value="">- <?php echo JText::_('COM_RDSUBS_STATS_SELECT_LEVEL'); ?> -</option>
		<?php foreach ($this->data->levels as $id => $level) : ?>
			<option value="<?php echo $id; ?>"<?php echo $id == $filter_level ? ' selected="selected"' : ''; ?>><?php echo $level->title; ?></option>
		<?php endforeach ?>
	</select>
	<select name="country" class="inputbox" onchange="this.form.submit()">
		<option value="">- <?php echo JText::_('COM_RDSUBS_STATS_SELECT_COUNTRY'); ?> -</option>
		<?php foreach ($this->data->countries as $id => $country) : ?>
			<option value="<?php echo $id; ?>"<?php echo $id == $filter_country ? ' selected="selected"' : ''; ?>><?php echo $country; ?></option>
		<?php endforeach ?>
	</select>
</form>

<h2><?php echo count($this->data->day->data); ?> Days</h2>

<?php if ($filter_country || $filter_level) : ?>
	<div class="row-fluid">
		<div class="span6">
			<h3>Total + filtered income</h3>
			<div id="byday" style="height: 300px; width: 100%;"></div>
		</div>
		<div class="span6">
			<h3>Filtered income (in % of total)</h3>
			<div id="byday_100" style="height: 300px; width: 100%;"></div>
		</div>
	</div>
<?php else: ?>
	<div id="byday" style="height: 300px; width: 100%;"></div>
<?php endif; ?>

<h2><?php echo count($this->data->month->data); ?> Months</h2>
<?php if ($filter_country || $filter_level) : ?>
	<div class="row-fluid">
		<div class="span6">
			<h3>Total + filtered income</h3>

			<div id="bymonth" style="height: 300px; width: 100%;"></div>
		</div>
		<div class="span6">
			<h3>Filtered income (in % of total)</h3>

			<div id="bymonth_100" style="height: 300px; width: 100%;"></div>
		</div>
	</div>
<?php else: ?>
	<div id="bymonth" style="height: 300px; width: 100%;"></div>
<?php endif; ?>

<h2><?php echo count($this->data->year->data); ?> Years</h2>
<?php if ($filter_country || $filter_level) : ?>
	<div class="row-fluid">
		<div class="span6">
			<h3>Total + filtered income</h3>

			<div id="byyear" style="height: 300px; width: 100%;"></div>
		</div>
		<div class="span6">
			<h3>Filtered income (in % of total)</h3>

			<div id="byyear_100" style="height: 300px; width: 100%;"></div>
		</div>
	</div>
<?php else: ?>
	<div id="byyear" style="height: 300px; width: 100%;"></div>
<?php endif; ?>

<div class="row-fluid">
	<div class="span4">
		<h2>Daily</h2>
		<?php echo getTable('day', $this->data->day, 'd M'); ?>
	</div>

	<div class="span4">
		<h2>Monthly</h2>
		<?php echo getTable('month', $this->data->month, 'M'); ?>
	</div>

	<div class="span4">
		<h2>Yearly</h2>
		<?php echo getTable('year', $this->data->year, 'Y'); ?>
	</div>
</div>
<br />
