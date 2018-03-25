<tr>
	<td data-value="{{ activity['athlete']['id'] }}">
		<a class="activity__athlete-link" href="https://www.strava.com/athletes/{{ activity['athlete']['id'] }}" title="{{ activity['athlete']['firstname'] }} {{ activity['athlete']['lastname'] }}">
			<img src="{{ activity['athlete']['profile'] }}" alt="" class="activity__athlete-avatar" />
		</a>
	</td>
	<td data-value="{{ startDateTimestamp }}">
		<a href="https://www.strava.com/activities/{{ activity['id'] }}">{{ startDateDate }}</a>
	</td>
	<td>
		<a href="https://www.strava.com/activities/{{ activity['id'] }}">{{ activity['name'] }}</a>
	</td>
	<td>
		{{ stravastat.convertDistance(activity['distance']) }}
	</td>
	<td>
		{{ stravastat.convertSpeed(activity['max_speed']) }}
	</td>
	<td>
		{{ stravastat.convertSpeed(activity['average_speed']) }}
	</td>
	<td>
		{{ activity['total_elevation_gain'] }}
	</td>
	<td data-value="{{ movingTimeTimestamp }}">
		{{ stravastat.convertTime(activity['moving_time']) }}
	</td>
</tr>