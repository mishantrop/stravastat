<div class="club-bage">
	<h2 class="club-bage__header">Club</h2>
	<a href="https://www.strava.com/clubs/{{ club['id'] }}" class="club-bage__link">
		<div class="club-bage__image-wrapper">
			<img src="{{ club['profile'] }}" class="club-bage__image" />
		</div>
		<div class="club-bage__info">
			<div class="club-bage__name">{{ club['name'] }}</div>
			<div class="club-bage__description">{{ club['description'] }}</div>
			<div class="club-bage__location">{{ club['country'] }}, {{ club['state'] }}, {{ club['city'] }}</div>
		</div>
	</a>
</div>