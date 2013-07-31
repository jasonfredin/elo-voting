<?php

//
// A webpage to display the Urban Leauge planning notices on a google map.
// End goal is to possibly make this a worpress widget.
// 
//

$html= "" ;
$url = "http://urbanleague.ca/feed";

$temp = file_get_contents($url);
$XmlObj = simplexml_load_string($temp); 

// mess of regex to grab the address from the rss titles
// =====================================================

for ($i = 0; $i < 10; $i++){
  $title = $XmlObj->channel->item[$i]->title;
	$add1 = preg_replace('/^[^–]+(–)/' ,'' , $title); 					// remove everything before the first em dash
	$add2 = preg_replace('/\(([^\(]*)$/' ,'' , $add1);					// remove everything after the first ( 
	$add3 = preg_replace('/\s+/' ,'+' , $add2);							// replace the spaces with + 
	$add4 = preg_replace('/^./' ,'' , $add3);							// remove first +
	$address = preg_replace('/.$/' ,'' , $add4);						// remove last +
	// echo "$i, " . $title . "<br />";									// echo the title for testing
	// echo $address . "<br />";										// echo the resulting address for testing
	$link = $XmlObj->channel->item[$i]->link;
	
// build the url for google map api
// ================================

	$geoq = 	"http://maps.googleapis.com/maps/api/geocode/xml?address=".
				$address . 
				",london,ontario&sensor=false";	
	// echo $geoq . "<br />";
	
	$gtemp = file_get_contents($geoq);
	$GoogleXmlObj = simplexml_load_string($gtemp);
	if ($GoogleXmlObj->status == "OK") {								// verify connection
		// echo "Success<br />";
		if ($GoogleXmlObj->result->geometry->location_type != "APPROXIMATE") {		// ensure accurate result not London wide
			// echo "accurate result<br />";
			// echo $GoogleXmlObj->result->geometry->location->lat;
			// echo ",";
			// echo $GoogleXmlObj->result->geometry->location->lng;
			// echo "<hr />";
			// build api co-ordinates
			$pins .= 	"{ lat: ". $GoogleXmlObj->result->geometry->location->lat . 
						", lng: " . $GoogleXmlObj->result->geometry->location->lng . 
						", name: \"<a href=$link><h4>$title</h4></a>\" },";
				} 
			}	
}

?>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" /> 
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/> 
<title>City of London Planning Notices Map</title> 
<link href="http://code.google.com/apis/maps/documentation/javascript/examples/default.css" rel="stylesheet" type="text/css" /> 
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script> 
<script type="text/javascript"> 
  function initialize() {

    // Create the map 
    // No need to specify zoom and center as we fit the map further down.
    var map = new google.maps.Map(document.getElementById("map_canvas"), {
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      streetViewControl: false
    });
 
 // Create the shared infowindow with two DIV placeholders
    // One for a text string, the other for the StreetView panorama.
    var content = document.createElement("DIV");
    content.style.width = "200px";
    var title = document.createElement("DIV");
    content.appendChild(title);
    var infowindow = new google.maps.InfoWindow({
   content: content
    });

    // Define the list of markers.
    // This could be generated server-side with a script creating the array.
    var markers = [<?php echo $pins ?>];
      // { lat: -33.85, lng: 151.05, name: "marker 1" },
  

    // Create the markers
    for (index in markers) addMarker(markers[index]);
    function addMarker(data) {
   var marker = new google.maps.Marker({
  position: new google.maps.LatLng(data.lat, data.lng),
  map: map,
        title: data.name
   });
   google.maps.event.addListener(marker, "click", function() {
  openInfoWindow(marker);
   });
    }
    
    // Handle the DOM ready event to create the StreetView panorama
    // as it can only be created once the DIV inside the infowindow is loaded in the DOM.
    var panorama = null;
    var pin = new google.maps.MVCObject();
    google.maps.event.addListenerOnce(infowindow, "domready", function() {
      panorama = new google.maps.StreetViewPanorama(streetview, {
       navigationControl: false,
       enableCloseButton: false,
       addressControl: false,
       linksControl: false,
       visible: false
      });
      panorama.bindTo("position", pin);
    });

    // Zoom and center the map to fit the markers
    // This logic could be conbined with the marker creation.
    // Just keeping it separate for code clarity.
    var bounds = new google.maps.LatLngBounds();
    for (index in markers) {
   var data = markers[index];
   bounds.extend(new google.maps.LatLng(data.lat, data.lng));
 }
 map.fitBounds(bounds);

    
    // Set the infowindow content and display it on marker click.
    // Use a 'pin' MVCObject as the order of the domready and marker click events is not garanteed.
    function openInfoWindow(marker) {
   title.innerHTML = marker.getTitle();
   pin.set("position", marker.getPosition());
   infowindow.open(map, marker);
    }
  }
</script> 
</head> 
<body onload="initialize()"> 
  <div id="map_canvas"></div> 
</body> 
</html>
