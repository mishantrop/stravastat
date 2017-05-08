<tr>
	<td data-value="'.strtotime($clubActivity['start_date']).'"><a href="https://www.strava.com/activities/'.$clubActivity['id'].'">'.date('d.m.Y H:i:s', strtotime($clubActivity['start_date'])).'</a></td>
	<td><a href="https://www.strava.com/activities/'.$clubActivity['id'].'">'.$clubActivity['name'].'</a></td>
	<td>'.$stravastat->convertDistance($clubActivity['distance']).'</td>
	<td>'.$stravastat->convertSpeed($clubActivity['max_speed']).'</td>
	<td>'.$stravastat->convertSpeed($clubActivity['average_speed']).'</td>
	<td data-value="'.strtotime($clubActivity['moving_time']).'">'.$stravastat->convertTime($clubActivity['moving_time']).'</td>
</tr>