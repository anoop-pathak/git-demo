<table class="table table-striped bottom-table text-right" style="margin-bottom: 0; width: 45%; border: 1px solid #ddd;">

	<tr>
		<td>Total Tax </td>
		<td colspan="2" class="ng-binding">
			{{ currencyFormat($insuranceMeta->tax_total) }}
		</td>
	</tr>

	<tr>
		<td>Total RCV </td>
		<td colspan="2" class="ng-binding">
			{{ currencyFormat($insuranceMeta->rcv_total) }}
		</td>
	</tr>

	<tr>
		<td>Depreciation </td>
		<td colspan="2" class="ng-binding">
			{{ currencyFormat($insuranceMeta->depreciation_total) }}
		</td>
	</tr>

	<tr>
		<td>Total ACV </td>
		<td colspan="2" class="ng-binding">
			{{ currencyFormat($insuranceMeta->acv_total) }}
		</td>
	</tr>
</table>