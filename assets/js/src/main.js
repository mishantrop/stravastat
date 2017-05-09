$(function() {
	$('.spoiler .spoiler__trigger').click(function(e){
		var spoiler = $(this).parent();
		$(spoiler).find('.spoiler__content').toggle();
	});
	
	$('#table-last-activities').tablesorter({
		textExtraction: function(node) {
			var data = node.dataset;
			var value = data.value;
			if (value != undefined) {
				//console.log('value 0: ' + value);
				return value;
			}  else {
				//console.log('value 1: ' + node.textContent);
				return node.textContent;
			}
		}
	});
	
	initActivitiesMap();
});

function googleApiAvailable() {
	return (typeof window.google === 'object' && window.google !== null && typeof window.google.maps === 'object' && window.google.maps !== null);
}

function initActivitiesMap() {
	/**
     * Доступен ли Google Maps API
     */
    if (!googleApiAvailable()) {
        return false;
    }

    var mapContainerId = '#activities-map';
    var coords = {
        lat: 54.0001,
        lng: 55.0001
    };
    var mapContainer = document.querySelector(mapContainerId);
    if (typeof mapContainer !== 'object' || mapContainer === null) {
        return false;
    }
    var lat = mapContainer.getAttribute('data-lat');
    var lng = mapContainer.getAttribute('data-lng');
    if (lat !== null && lng !== null) {
        coords.lat = parseFloat(lat);
        coords.lng = parseFloat(lng);
    }

    var mapOptions = {
        zoom: 10,
        scrollwheel: false,
        navigationControl: true,
        mapTypeControl: false,
        scaleControl: false,
        center: new google.maps.LatLng(coords.lat, coords.lng)
    }
    var map = new google.maps.Map(mapContainer, mapOptions);

	if ('mapActivities' in window && typeof window.mapActivities == 'object' && window.mapActivities !== null) {
		var markers = [];
		var infowindow = new google.maps.InfoWindow();
		var avgLat = 0;
		var avgLng = 0;
		
		
		
		for (idx in window.mapActivities) {
			var place = window.mapActivities[idx];
			avgLat += parseFloat(place.lat/window.mapActivities.length)
			avgLng += parseFloat(place.lng/window.mapActivities.length)
			//var image = 'assets/templates/main/images/contacts-map__marker-image.png';
			var myLatLng = new google.maps.LatLng(parseFloat(place.lat), parseFloat(place.lng));
			
			var marker = new google.maps.Marker({
				position: myLatLng,
				map: map,
				//icon: image,
				optimized: false
			});
			google.maps.event.addListener(marker, 'click', (function(marker, idx) {
				return function() {
					infowindow.setContent(window.mapActivities[idx].title);
					infowindow.setOptions({maxWidth: 256});
					infowindow.open(map, marker);
				}
			}) (marker, idx));
			
			markers.push(marker);
		}
		
		/*var markers = window.mapActivities.map(function(location, i) {
			return new google.maps.Marker({
				position: {lat: parseFloat(location.lat), lng: parseFloat(location.lng)},
				//label: location.title,
			});
        });*/
		
		var avgPosition = new google.maps.LatLng(avgLat, avgLng);
		map.setCenter(avgPosition);
		console.debug(markers);
		var mc = new MarkerClusterer(map, markers, {
			gridSize: 50,
			maxZoom: 15,
			imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m',
		});
	}
}