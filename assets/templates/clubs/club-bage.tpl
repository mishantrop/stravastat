<div class="club-bage">
	<h2 class="club-bage__header">Клуб</h2>
	<a href="https://www.strava.com/clubs/{{ club['id'] }}" style="display: block;">
		<img src="{{ club['profile'] }}" style="display: block; border-radius: 50%;"  class="club-bage__image" />
		<div class="club-bage__name">{{ club['name'] }}</div>
		<div class="club-bage__description">{{ club['description'] }}</div>
		<div class="club-bage__location">{{ club['country'] }}, {{ club['state'] }}, {{ club['city'] }}</div>
	</a>
</div>