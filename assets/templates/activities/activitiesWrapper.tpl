<h2>Activities ({{ activitiesCount }})</h2>
<table class="report-table" id="table-last-activities">
	<thead>
		<tr>
			<th></th>
			<th>Дата</th>
			<th>Название</th>
			<th>Дистанция</th>
			<th>Макс. скорость</th>
			<th>Ср скорость</th>
			<th>Подъём</th>
			<th>Чистое время</th>
		</tr>
	</thead>
	<tbody>
		{{ output|raw }}
	</tbody>
</table>