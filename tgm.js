jQuery.noConflict();

var	tgm_url = tgm_objects.tgm_url;
var tgm_icons = []; //initiate global array of icons
var tgm_iw = new google.maps.InfoWindow();
var tgm_sh = new google.maps.MarkerImage("http://chart.googleapis.com/chart?chst=d_map_pin_shadow", new google.maps.Size(40, 37), null, new google.maps.Point(10, 37));
var tgm_size = new google.maps.Size(21, 34);

function thydzikgm(devid, en_xml, zoom, type, gpx, ext) {
	"use strict";

	// define variables
	var xml = tgm_url + "?u=" + en_xml,
		bounds = new google.maps.LatLngBounds(),
		map = new google.maps.Map(document.getElementById(devid));

	//eval("map.setMapTypeId(google.maps.MapTypeId." + type + ")");

	switch (type) {
	case 'SATELLITE':
		map.setMapTypeId(google.maps.MapTypeId.SATELLITE);
		break;
	case 'HYBRID':
		map.setMapTypeId(google.maps.MapTypeId.HYBRID);
		break;
	case 'TERRAIN':
		map.setMapTypeId(google.maps.MapTypeId.TERRAIN);
		break;
	default:
		map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
	}

	function gpx_link() {
		var cont, div, atag, img;
		cont = document.createElement('div');
		cont.style.marginRight = "5px";

		div = document.createElement('div');
		div.style.width = '50px';
		div.style.height = '21px';
		div.style.backgroundColor = '#fcfcfc';
		div.style.borderStyle = 'solid';
		div.style.borderWidth = '1px';
		div.style.borderColor = "#678ac7";
		cont.appendChild(div);

		atag = document.createElement('a');
		atag.href = xml + "&d=gpx";
		div.appendChild(atag);

		img = document.createElement('img');
		img.src = "http://chart.googleapis.com/chart?chst=d_simple_text_icon_left&chld=gpx|14|000|mobile|16|000|FFF&.png";
		img.alt = "gpx";
		img.title = "Download gpx file!";
		img.style.display = "block";
		img.style.marginTop = "2px";
		img.style.marginLeft = "2px";
		img.style.borderStyle = 'none';
		atag.appendChild(img);

		return cont;
	}

	if (gpx) {
		map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(gpx_link());
	}

    jQuery.get(xml, function (data) {
		//detect if kml file or xml
		if ((ext === 'KML') || (ext === 'KMZ')) {
			var kmlLayer = new google.maps.KmlLayer(xml);
			kmlLayer.setMap(map);
		} else {//eml
			jQuery(data).find("marker").each(function () {
				var lat = jQuery(this).attr("lat");
				var lng = jQuery(this).attr("lng");
				if (lat && lng) {
					var latlng = new google.maps.LatLng(parseFloat(lat), parseFloat(lng));
					bounds.extend(latlng);

					var text = (jQuery(this).attr("icon") || "").replace("Marker.png", "").substring(0, 2) || "%e2%bc%80"; //closest to a default dot symbol
					var colour = jQuery(this).attr("colour") || jQuery(this).attr("color") || "ff776b"; //google default red colour
					var key = text + colour; //text can never be a hex colour
					if (!tgm_icons[key]) {
						tgm_icons[key] = new google.maps.MarkerImage("http://chart.googleapis.com/chart?chst=d_map_pin_letter&chld=" + text + "|" + colour + "|000000&.png", tgm_size);
					}
					var marker = new google.maps.Marker({
						position: latlng,
						icon: tgm_icons[key],
						map: map,
						shadow: tgm_sh
					});

					var html = jQuery(this).attr("html");
					if (html) {
						google.maps.event.addListener(marker, 'click', function () {
							tgm_iw.setContent(html);
							tgm_iw.open(map, marker);
						});
					} else {
						marker.setClickable(false);
					}
				}
			});
			jQuery.each(["line", "points"], function () {
				jQuery(data).find(String(this)).each(function () {
					var latlng_arr = [];
					var colour = jQuery(this).attr("colour") || jQuery(this).attr("color") || "0000ff";
					if (colour.charAt(0) !== "#") {
						colour = "#" + colour;
					}

					jQuery(this).find("point").each(function () {
						var lat = jQuery(this).attr("lat");
						var lng = jQuery(this).attr("lng");
						if (lat && lng) {
							var latlng = new google.maps.LatLng(parseFloat(lat), parseFloat(lng));
							bounds.extend(latlng);
							latlng_arr.push(latlng);
						}
					});
					var line = new google.maps.Polyline({
						path: latlng_arr,
						strokeColor: colour,
						strokeOpacity: parseFloat(jQuery(this).attr("opacity") || "0.75"),
						strokeWeight: parseFloat(jQuery(this).attr("width") || "4")
					});
					line.setMap(map);
				});
			});
			map.fitBounds(bounds);

			if (zoom !== -1) {
				var listener = google.maps.event.addListener(map, "idle", function () {
					map.setZoom(zoom);
					google.maps.event.removeListener(listener);
				});
			}
			google.maps.event.addListener(map, 'click', function () {
				tgm_iw.close(map);
			});
		}
    }, "xml");
}