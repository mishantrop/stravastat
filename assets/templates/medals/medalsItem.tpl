<div class="pedestal-item col s12 m4">
	<h2>{{ title }}</h2>
	<a href="https://www.strava.com/athletes/{{ athlete['id'] }}" class="pedestal-item__image-link">
		<img src="{{ athlete['profile'] }}" class="pedestal-item__image" />
	</a>
	<a href="https://www.strava.com/athletes/{{ athlete['id'] }}" class="pedestal-item__username-link">
		{{ athlete['firstname'] }} {{ athlete['lastname'] }}
	</a>
	<div class="pedestal-item__mark">
		{{ value }} {{ units }}
	</div>
</div>