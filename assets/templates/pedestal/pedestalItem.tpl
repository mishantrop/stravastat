<div class="pedestal-item col s12 m3 l3">
	<h2 class="pedestal-item__header">{{ medal.title }}</h2>
	<a href="https://www.strava.com/athletes/{{ medal.athlete['id'] }}" class="pedestal-item__image-link">
		<img src="{{ medal.athlete['profile'] }}" class="pedestal-item__image" />
	</a>
	<a href="https://www.strava.com/athletes/{{ medal.athlete['id'] }}" class="pedestal-item__username-link">
		{{ medal.athlete['firstname'] }} {{ medal.athlete['lastname'] }}
	</a>
	<div class="pedestal-item__mark">
		{{ medal.value }} {{ medal.units }}
	</div>
</div>