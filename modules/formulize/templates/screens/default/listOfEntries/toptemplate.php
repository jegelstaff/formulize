<?php

print "
<table cellpadding=10>
	<tr>
		<td id='titleTable' style='vertical-align: top;' width='100%'>
			<h1>$title</h1> $modifyScreenLink $submitButton
		</td>";
		
if($lockControls == false) {
	print "
		<td id='buttonsTable' class='outerTable' rowspan=3 style='vertical-align: bottom;'>
			<table>
				<tr>
					<td id='leftButtonColumn' class='innerTable' style='vertical-align: bottom;'>
						<p>
							<b>$actionButtonHeading</b>
							$changeColsButton
							$resetViewButton
							$saveViewButton
							$deleteViewButton
						</p>
					</td>
					<td id='middleButtonColumn' class='innerTable' style='vertical-align: bottom;'>
						<p style='text-align: center;'>
							$selectAllButton
							$clearSelectButton
							$cloneButton
							$deleteButton
						</p>
					</td>
					<td id='rightButtonColumn' class='innerTable' style='vertical-align: bottom;'>
						<p style='text-align: center;'>
							$calcButton
							$advCalcButton
							$advSearchButton
							$exportButton
							$importButton
							$notifButton
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td id='outerAddEntryPanel' style='vertical-align: top;'>
			<table style='width: 1%;'>
				<tr>
					<td id='innerAddEntryPanel' style='vertical-align: bottom;'>
						<p>
							<b>$addButtonHeading</b>
							$addButton
							$addMultiButton
							$addProxyButton
							<br><br>
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>";
				
} else { // controls are locked...
	print "
		<td></td>
	</tr>
</table>
<table>
	<tr>
		<td style='vertical-align: bottom;'>
			<p>
				$lockControlsWarning
			</p>
		</td>
	</tr>
	<tr>
		<td id='outerAddEntryPanel' style='vertical-align: top;'>
		</td>
	</tr>";
}

print "
	<tr>
		<td id=currentViewSelectTable style='vertical-align: bottom;'>
			$currentViewList
			$pageNavControls
		</td>
	</tr>
</table>";