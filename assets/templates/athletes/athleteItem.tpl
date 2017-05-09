<div class="athletes-item">
	<a href="https://www.strava.com/athletes/{{ athlete['id'] }}" class="athletes-item__image-link">
		<img src="{{ athlete['profile'] }}" class="athletes-item__image" />
	</a>
	<a href="https://www.strava.com/athletes/{{ athlete['id'] }}" class="athletes-item__username-link">
		{{ athlete['firstname'] }} {{ athlete['lastname'] }}
	</a>
</div>