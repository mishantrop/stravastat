<tr>
	<td data-value="{{ startDateTimestamp }}"><a href="https://www.strava.com/activities/{{ activity['id'] }}">{{ startDateDate }}</a></td>
	<td><a href="https://www.strava.com/activities/{{ activity['id'] }}">{{ activity['name'] }}</a></td>
	<td>{{ stravastat.convertDistance(activity['distance']) }}</td>
	<td>{{ stravastat.convertSpeed(activity['max_speed']) }}</td>
	<td>{{ stravastat.convertSpeed(activity['average_speed']) }}</td>
	<td>{{ activity['total_elevation_gain'] }}</td>
	<td data-value="{{ movingTimeTimestamp }}">{{ stravastat.convertTime(activity['moving_time']) }}</td>
</tr>