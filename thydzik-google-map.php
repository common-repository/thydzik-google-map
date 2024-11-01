<?php 
/* 
	Plugin Name: thydzik Google Map
	Plugin URI: http://thydzik.com/category/thydzikgooglemap/
	Description: A plugin to create inline WordPress Google maps.
	Version: 3.3
	Author: Travis Hydzik
	Author URI: http://thydzik.com
*/ 
/*  Copyright 2018 Travis Hydzik (mail@thydzik.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

function utcdate() {
	return gmdate("Y-m-d\Th:i:s\Z");
}

$u = $_GET['u']; //the encoded url
$d = $_GET['d']; //convert to gpx
$de_u =  base64_decode($u); //the decoded url
$u_parts = pathinfo($de_u); //array of url parts
$u_ext = strtoupper($u_parts['extension']);
if (in_array($u_ext, array("XML", "KML"))) {

    $ch = curl_init($de_u);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');
    $data = curl_exec($ch);
    curl_close($ch);


	$dom_xml = new DOMDocument();
	$dom_xml->loadXML($data);
	$ret = $dom_xml->saveXML();

	if ($d) {
		//set some global counters
		$i_markers = 0;
		$i[lines] = 0;
		$i[points] = 0;
		
		if (!($ref = $_SERVER['HTTP_REFERER'])) {
			$ref = $u_parts['dirname']."/";
		}
	
		$dom_gpx = new DOMDocument('1.0', 'UTF-8');
		$dom_gpx->formatOutput = true;
		
		//root node
		$gpx = $dom_gpx->createElement('gpx');
		$gpx = $dom_gpx->appendChild($gpx);
		
		$gpx_version = $dom_gpx->createAttribute('version');
		$gpx->appendChild($gpx_version);
		$gpx_version_text = $dom_gpx->createTextNode('1.0');
		$gpx_version->appendChild($gpx_version_text);
		
		$gpx_creator = $dom_gpx->createAttribute('creator');
		$gpx->appendChild($gpx_creator);
		$gpx_creator_text = $dom_gpx->createTextNode('thydzik Google Map - http://thydzik.com/category/thydzikgooglemap/');
		$gpx_creator->appendChild($gpx_creator_text);
		
		$gpx_xmlns_xsi = $dom_gpx->createAttribute('xmlns:xsi');
		$gpx->appendChild($gpx_xmlns_xsi);
		$gpx_xmlns_xsi_text = $dom_gpx->createTextNode('http://www.w3.org/2001/XMLSchema-instance');
		$gpx_xmlns_xsi->appendChild($gpx_xmlns_xsi_text);
		
		$gpx_xmlns = $dom_gpx->createAttribute('xmlns');
		$gpx->appendChild($gpx_xmlns);
		$gpx_xmlns_text = $dom_gpx->createTextNode('http://www.topografix.com/GPX/1/0');
		$gpx_xmlns->appendChild($gpx_xmlns_text);
		
		$gpx_xsi_schemaLocation = $dom_gpx->createAttribute('xsi:schemaLocation');
		$gpx->appendChild($gpx_xsi_schemaLocation);
		$gpx_xsi_schemaLocation_text = $dom_gpx->createTextNode('http://www.topografix.com/GPX/1/0 http://www.topografix.com/GPX/1/0/gpx.xsd');
		$gpx_xsi_schemaLocation->appendChild($gpx_xsi_schemaLocation_text);
		
		$gpx_url = $dom_gpx->createElement('url');
		$gpx_url = $gpx->appendChild($gpx_url);
		$gpx_url_text = $dom_gpx->createTextNode($ref);
		$gpx_url->appendChild($gpx_url_text);
		
		$gpx_time = $dom_gpx->createElement('time');
		$gpx_time = $gpx->appendChild($gpx_time);
		$gpx_time_text = $dom_gpx->createTextNode(utcdate());
		$gpx_time->appendChild($gpx_time_text);
		
		//do different actions depending if xml of kml

		
		if ($u_ext=='KML') {
			// placemarks
			$names = array();
			foreach ($dom_xml->getElementsByTagName('Placemark') as $placemark) {
				//name
				foreach ($placemark->getElementsByTagName('name') as $name) {
					$name  = $name->nodeValue;
					//check if the key exists
					if (array_key_exists($name, $names)) {
						//increment the value
						++$names[$name];
						$name = $name." ({$names[$name]})";
					} else {
						$names[$name] = 0;
					}
				}
				//description
				foreach ($placemark->getElementsByTagName('description') as $description) {
					$description  = $description->nodeValue;
				}
				foreach ($placemark->getElementsByTagName('Point') as $point) {
					foreach ($point->getElementsByTagName('coordinates') as $coordinates) {
						//add the marker
						$coordinate = $coordinates->nodeValue;
						$coordinate = str_replace(" ", "", $coordinate);//trim white space
						$latlng = explode(",", $coordinate);
						
						if (($lat = $latlng[1]) && ($lng = $latlng[0])) {
							$gpx_wpt = $dom_gpx->createElement('wpt');
							$gpx_wpt = $gpx->appendChild($gpx_wpt);

							$gpx_wpt_lat = $dom_gpx->createAttribute('lat');
							$gpx_wpt->appendChild($gpx_wpt_lat);
							$gpx_wpt_lat_text = $dom_gpx->createTextNode($lat);
							$gpx_wpt_lat->appendChild($gpx_wpt_lat_text);
							
							$gpx_wpt_lon = $dom_gpx->createAttribute('lon');
							$gpx_wpt->appendChild($gpx_wpt_lon);
							$gpx_wpt_lon_text = $dom_gpx->createTextNode($lng);
							$gpx_wpt_lon->appendChild($gpx_wpt_lon_text);
							
							$gpx_time = $dom_gpx->createElement('time');
							$gpx_time = $gpx_wpt->appendChild($gpx_time);
							$gpx_time_text = $dom_gpx->createTextNode(utcdate());
							$gpx_time->appendChild($gpx_time_text);
							
							$gpx_name = $dom_gpx->createElement('name');
							$gpx_name = $gpx_wpt->appendChild($gpx_name);
							$gpx_name_text = $dom_gpx->createTextNode($name);
							$gpx_name->appendChild($gpx_name_text);
							
							$gpx_desc = $dom_gpx->createElement('desc');
							$gpx_desc = $gpx_wpt->appendChild($gpx_desc);
							$gpx_desc_text = $dom_gpx->createTextNode($description);
							$gpx_desc->appendChild($gpx_desc_text);
							
							//$gpx_url = $dom_gpx->createElement('url');
							//$gpx_url = $gpx_wpt->appendChild($gpx_url);
							//$gpx_url_text = $dom_gpx->createTextNode($ref);
							//$gpx_url->appendChild($gpx_url_text);
							
							$gpx_sym = $dom_gpx->createElement('sym');
							$gpx_sym = $gpx_wpt->appendChild($gpx_sym);
							$gpx_sym_text = $dom_gpx->createTextNode('Waypoint');
							$gpx_sym->appendChild($gpx_sym_text);
						}
					}
				}
				foreach ($placemark->getElementsByTagName('LineString') as $lineString) {
					foreach ($lineString->getElementsByTagName('coordinates') as $coordinates) {
						//add the new track
						$gpx_trk = $dom_gpx->createElement('trk');
						$gpx_trk = $gpx->appendChild($gpx_trk);
						
						$gpx_name = $dom_gpx->createElement('name');
						$gpx_name = $gpx_trk->appendChild($gpx_name);
						$gpx_name_text = $dom_gpx->createTextNode($name);
						$gpx_name->appendChild($gpx_name_text);
						
						$gpx_trkseg = $dom_gpx->createElement('trkseg');
						$gpx_trkseg = $gpx_trk->appendChild($gpx_trkseg);
					
						$coordinates = $coordinates->nodeValue;
						$coordinates = preg_split("/[\s\r\n]+/", $coordinates); //split the coords by new line
						foreach ($coordinates as $coordinate) {
							$latlng = explode(",", $coordinate);
							
							if (($lat = $latlng[1]) && ($lng = $latlng[0])) {
								$gpx_trkpt = $dom_gpx->createElement('trkpt');
								$gpx_trkpt = $gpx_trkseg->appendChild($gpx_trkpt);

								$gpx_trkpt_lat = $dom_gpx->createAttribute('lat');
								$gpx_trkpt->appendChild($gpx_trkpt_lat);
								$gpx_trkpt_lat_text = $dom_gpx->createTextNode($lat);
								$gpx_trkpt_lat->appendChild($gpx_trkpt_lat_text);
								
								$gpx_trkpt_lon = $dom_gpx->createAttribute('lon');
								$gpx_trkpt->appendChild($gpx_trkpt_lon);
								$gpx_trkpt_lon_text = $dom_gpx->createTextNode($lng);
								$gpx_trkpt_lon->appendChild($gpx_trkpt_lon_text);
								
								$gpx_time = $dom_gpx->createElement('time');
								$gpx_time = $gpx_trkpt->appendChild($gpx_time);
								$gpx_time_text = $dom_gpx->createTextNode(utcdate());
								$gpx_time->appendChild($gpx_time_text);
							}
							
						}
					}
				}
			}
		
		} else { //xml
			// markers
			foreach ($dom_xml->getElementsByTagName('marker') as $xml_marker) {
				if (($lat=$xml_marker->getAttribute('lat')) && ($lng=$xml_marker->getAttribute('lng'))) {

					$gpx_wpt = $dom_gpx->createElement('wpt');
					$gpx_wpt = $gpx->appendChild($gpx_wpt);

					$gpx_wpt_lat = $dom_gpx->createAttribute('lat');
					$gpx_wpt->appendChild($gpx_wpt_lat);
					$gpx_wpt_lat_text = $dom_gpx->createTextNode($lat);
					$gpx_wpt_lat->appendChild($gpx_wpt_lat_text);
					
					$gpx_wpt_lon = $dom_gpx->createAttribute('lon');
					$gpx_wpt->appendChild($gpx_wpt_lon);
					$gpx_wpt_lon_text = $dom_gpx->createTextNode($lng);
					$gpx_wpt_lon->appendChild($gpx_wpt_lon_text);
					
					$gpx_time = $dom_gpx->createElement('time');
					$gpx_time = $gpx_wpt->appendChild($gpx_time);
					$gpx_time_text = $dom_gpx->createTextNode(utcdate());
					$gpx_time->appendChild($gpx_time_text);
					
					$label=$xml_marker->getAttribute('icon');
					if (!$label || ($label == "Marker.php")) {
						$label = str_pad(++$i_markers, 3, "0", STR_PAD_LEFT);
					}
					$gpx_name = $dom_gpx->createElement('name');
					$gpx_name = $gpx_wpt->appendChild($gpx_name);
					$gpx_name_text = $dom_gpx->createTextNode($label);
					$gpx_name->appendChild($gpx_name_text);
					
					if ($html =$xml_marker->getAttribute('html')) {
						$gpx_desc = $dom_gpx->createElement('desc');
						$gpx_desc = $gpx_wpt->appendChild($gpx_desc);
						$gpx_desc_text = $dom_gpx->createTextNode($html);
						$gpx_desc->appendChild($gpx_desc_text);
					}
					
					$gpx_url = $dom_gpx->createElement('url');
					$gpx_url = $gpx_wpt->appendChild($gpx_url);
					$gpx_url_text = $dom_gpx->createTextNode($ref);
					$gpx_url->appendChild($gpx_url_text);
					
					$gpx_sym = $dom_gpx->createElement('sym');
					$gpx_sym = $gpx_wpt->appendChild($gpx_sym);
					$gpx_sym_text = $dom_gpx->createTextNode('Waypoint');
					$gpx_sym->appendChild($gpx_sym_text);
				}
			}
			// lines
			foreach (array('line', 'points') as $lines) {
				foreach ($dom_xml->getElementsByTagName($lines) as $points) {
					$gpx_trk = $dom_gpx->createElement('trk');
					$gpx_trk = $gpx->appendChild($gpx_trk);
					
					$gpx_name = $dom_gpx->createElement('name');
					$gpx_name = $gpx_trk->appendChild($gpx_name);
					$gpx_name_text = $dom_gpx->createTextNode(ucfirst($lines)." ".++$i[$lines].": ".$u_parts['filename']);
					$gpx_name->appendChild($gpx_name_text);
					
					$gpx_trkseg = $dom_gpx->createElement('trkseg');
					$gpx_trkseg = $gpx_trk->appendChild($gpx_trkseg);
					
					foreach ($points->getElementsByTagName('point') as $point) {
						if (($lat=$point->getAttribute('lat')) && ($lng=$point->getAttribute('lng'))) {
							$gpx_trkpt = $dom_gpx->createElement('trkpt');
							$gpx_trkpt = $gpx_trkseg->appendChild($gpx_trkpt);

							$gpx_trkpt_lat = $dom_gpx->createAttribute('lat');
							$gpx_trkpt->appendChild($gpx_trkpt_lat);
							$gpx_trkpt_lat_text = $dom_gpx->createTextNode($lat);
							$gpx_trkpt_lat->appendChild($gpx_trkpt_lat_text);
							
							$gpx_trkpt_lon = $dom_gpx->createAttribute('lon');
							$gpx_trkpt->appendChild($gpx_trkpt_lon);
							$gpx_trkpt_lon_text = $dom_gpx->createTextNode($lng);
							$gpx_trkpt_lon->appendChild($gpx_trkpt_lon_text);
							
							$gpx_time = $dom_gpx->createElement('time');
							$gpx_time = $gpx_trkpt->appendChild($gpx_time);
							$gpx_time_text = $dom_gpx->createTextNode(utcdate());
							$gpx_time->appendChild($gpx_time_text);
						}
					}
				}
			}
		}
		
		if ($d=="gpx") {//create a gpx file
			header("Content-disposition: attachment; filename=".$u_parts['filename'].".gpx");
			ob_clean();
			flush();
		} else {
			header("Content-Type: text/xml");
		}
		echo $dom_gpx->saveXML();
		
	} else {
		//convert relative links to absolute links
		$xml_path = $u_parts['dirname']."/";
		$ret = preg_replace('/((?:href|src) *= *(?:&apos;|&quot;|\'|")(?!(http|ftp)))/i', "$1$xml_path", $ret);
		header("Content-Type: text/xml");
		echo $ret;
	}
	exit;
} elseif ($u_ext=="KMZ") {
	//kmz (zipped kml file)
	header("Content-type: application/octet-stream");
	header("Content-disposition: attachment; filename=".$u_parts['filename'].".kmz");	
	ob_clean();
	flush();
	$session = curl_init($de_u);
	curl_exec($session);
	curl_close($session);
	exit;
}



$mygpGeotagsGeoMetatags_key = "mygpGeotagsGeoMetatags";
function tgm_create_xml() {
	global $wpdb, $mygpGeotagsGeoMetatags_key;

	$posts = $wpdb->get_results("SELECT * FROM $wpdb->posts where post_status='publish'");
	
	$markers_arr = array();
	
	foreach ($posts as $post) {
		$data = get_post_meta($post->ID, $mygpGeotagsGeoMetatags_key, true);
		if (isset($data['position'])) {
			$position = $data['position'];
			if ($position) {
			
				$permalink = get_permalink($post->ID);
				
				if (!$permalink_url_host) { //the the url host once
					$permalink_url_host = parse_url($permalink, PHP_URL_HOST);
				}
				
				$html_text = "<a href='{$permalink}'>{$post->post_title}</a>";
				
				if (array_key_exists($position, $markers_arr)) {
					$markers_arr[$position]['html'] = "{$markers_arr[$position]['html']}<br>{$html_text}";
				} else {
					$markers_arr[$position]['html'] = $html_text;
					$markers_arr[$position]['placename'] = $data['placename'];
				}
			}
		}
	}
	
	// generate the xml compatible with thydzik google maps
	$xml = new DOMDocument();
	$xml->formatOutput = true;
	
	$markers = $xml->createElement('markers');
	$markers = $xml->appendChild($markers);
	
	foreach ($markers_arr as $position => $value) {
    
		$latlng = split(";", $position); 
		$lat_text = $latlng[0];
		$lng_text = $latlng[1];
	
		$marker = $xml->createElement('marker');
		$marker = $markers->appendChild($marker);
		
		$lat = $xml->createAttribute('lat');
		$marker->appendChild($lat);
		$lat_text = $xml->createTextNode($lat_text);
		$lat->appendChild($lat_text);
		
		$lng = $xml->createAttribute('lng');
		$marker->appendChild($lng);
		$lng_text = $xml->createTextNode($lng_text);
		$lng->appendChild($lng_text);
		
		$html = $xml->createAttribute('html');
		$marker->appendChild($html);
		$html_text = $xml->createTextNode($value['html']);
		$html->appendChild($html_text);
	}

	$file = dirname(__FILE__)."/geocodes.xml";
	$xml->save($file);
	
	// generate the kml compatible with everything else
	$xml = new DOMDocument();
	$xml->formatOutput = true;
	
	$kml = $xml->createElement('kml');
	$kml = $xml->appendChild($kml);
	
	$xmlns = $xml->createAttribute('xmlns');
	$kml->appendChild($xmlns);
	$xmlns_text = $xml->createTextNode("http://www.opengis.net/kml/2.2");
	$xmlns->appendChild($xmlns_text);
	
	$Document = $xml->createElement('Document');
	$Document = $kml->appendChild($Document);
	
	$name = $xml->createElement('name');
	$name = $Document->appendChild($name);
	$name_text = $xml->createTextNode("{$permalink_url_host} Geocoded Posts");
	$name->appendChild($name_text);
	
	//$description = $xml->createElement('description');
	//$description = $Document->appendChild($description);
	//$description_text = $xml->createTextNode("SonyaandTravis.com Geocoded Blog Posts");
	//$description->appendChild($description_text);
	
	$Style = $xml->createElement('Style');
	$Style = $Document->appendChild($Style);
	
	$id = $xml->createAttribute('id');
	$Style->appendChild($id);
	$id_text = $xml->createTextNode("favicon");
	$id->appendChild($id_text);
	
	$IconStyle = $xml->createElement('IconStyle');
	$IconStyle = $Style->appendChild($IconStyle);
	
	$scale = $xml->createElement('scale');
	$scale = $IconStyle->appendChild($scale);
	$scale_text = $xml->createTextNode("1");
	$scale->appendChild($scale_text);
	
	$Icon = $xml->createElement('Icon');
	$Icon = $IconStyle->appendChild($Icon);
	
	$href = $xml->createElement('href');
	$href = $Icon->appendChild($href);
	$href_text = $xml->createTextNode("http://www.google.com/s2/favicons?domain={$permalink_url_host}");
	$href->appendChild($href_text);
	
	$hotSpot = $xml->createElement('hotSpot');
	$hotSpot = $IconStyle->appendChild($hotSpot);

	$x = $xml->createAttribute('x');
	$hotSpot->appendChild($x);
	$x_text = $xml->createTextNode("0.5");
	$x->appendChild($x_text);
	$y = $xml->createAttribute('y');
	$hotSpot->appendChild($y);
	$y_text = $xml->createTextNode("0.5");
	$y->appendChild($y_text);
	$xunits = $xml->createAttribute('xunits');
	$hotSpot->appendChild($xunits);
	$xunits_text = $xml->createTextNode("fraction");
	$xunits->appendChild($xunits_text);
	$yunits = $xml->createAttribute('yunits');
	$hotSpot->appendChild($yunits);
	$yunits_text = $xml->createTextNode("fraction");
	$yunits->appendChild($yunits_text);
	
	foreach ($markers_arr as $position => $value) {
    
		$latlng = split(";", $position); 
		$lat_text = $latlng[0];
		$lng_text = $latlng[1];
		
		$Placemark = $xml->createElement('Placemark');
		$Placemark = $Document->appendChild($Placemark);
		
		$name = $xml->createElement('name');
		$name = $Placemark->appendChild($name);
		$name_text = $xml->createTextNode($value['placename']);
		$name->appendChild($name_text);
		
		$description = $xml->createElement('description');
		$description = $Placemark->appendChild($description);
		$description_text = $xml->createTextNode($value['html']);
		$description->appendChild($description_text);
		
		// $styleUrl = $xml->createElement('styleUrl');
		// $styleUrl = $Placemark->appendChild($styleUrl);
		// $styleUrl_text = $xml->createTextNode("#favicon");
		// $styleUrl->appendChild($styleUrl_text);
		
		$Point = $xml->createElement('Point');
		$Point = $Placemark->appendChild($Point);
		
		$coordinates = $xml->createElement('coordinates');
		$coordinates = $Point->appendChild($coordinates);
		$coordinates_text = $xml->createTextNode("{$lng_text},{$lat_text},0");
		$coordinates->appendChild($coordinates_text);
		
	}
	
	$file = dirname(__FILE__)."/geocodes.kml";
	$xml->save($file);
}

//above does not get fired for upgrades, so check if file exists instead
if (!file_exists(dirname(__FILE__)."/geocodes.xml") || !file_exists(dirname(__FILE__)."/geocodes.kml")) {
	tgm_create_xml();
}

// generate index whenever a page/post is modified
add_action('edit_post', 'tgm_create_xml');

//tiny bit of css to fix twenty eleven themes and google maps
function tgm_css() {
	echo '<style type="text/css">.tgm_div img {max-width: none;}</style>';
}

//load the scripts
function tgm_init() {
    if (!is_admin()) {
		
		$key = get_option("thydzikgooglemap_key");
		
        wp_deregister_script('jquery');
        wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js', array(), Null);
        wp_enqueue_script('jquery');
		wp_register_script('google-maps', "https://maps.googleapis.com/maps/api/js?key={$key}&callback=initMap", array(), Null);
		wp_enqueue_script('google-maps');
		wp_register_script('thydzik-google-map', plugins_url('tgm.min.js', __FILE__), array('jquery', 'google-maps'));
		wp_enqueue_script('thydzik-google-map' );
		
		//pass some variables from php to javascript
		$tgm_data = array(
			"tgm_url" => plugins_url(basename(__FILE__),__FILE__));
		wp_localize_script('thydzik-google-map', 'tgm_objects', $tgm_data);
    
		//add a tiny bit of css to fix twenty eleven themes and google maps
		add_action('wp_head', 'tgm_css');
	}
}    
 
add_action('init', 'tgm_init');

//default to resolution of 4:3
if (!get_option("thydzikgooglemap_r")) {
	update_option("thydzikgooglemap_r", 0.75);
}

add_filter('the_content', 'tgm_find');

function tgm_trim_value(&$value){ 
	$value = trim($value); 
}

function tgm_find($content) {
	global $post;
	$postid = $post->ID;
	$count = 0;
	preg_match_all("/(?:<p>|(?:\r\n|\n\r|\r|\n))\s*thydzikgooglemap\(([^<]*)\)\s*(?:<\/\s*p>|<br\s*\/>)/i", $content, $regs, PREG_SET_ORDER);
	foreach ($regs as $val) {
		
		//split the parameters and trim any spaces
		$params = split(',', $val[1]);

		array_walk($params, 'tgm_trim_value');

		//assume the first parameters is always the xml file
		$xml_path = $params[0];
		$xml_path_parts = pathinfo($xml_path);
		$xml_ext = strtoupper($xml_path_parts['extension']);
		if ($xml_path_parts['basename']==$xml_path) {
			$xml_path = plugins_url($xml_path,__FILE__);
		}

		if (urlExists($xml_path)) { //the file is accessible
			++$count;
			//process any other parameters
			//assume width is always before height, zoom is always < 20 and both are whole numbers

			//set defaults first
			$ratio_val = get_option("thydzikgooglemap_r");

			if (get_option("thydzikgooglemap_gpx") == "checked") {
				$gpx_val = 1;
			} else {
				$gpx_val = 0;
			}
			$zoom_val = -1; // a value of -1 means automatic zoom
			$type_val = "ROADMAP";
			$width_found = false; // used to determine if the width has been found
			
			foreach(array_slice($params,1) as $param) {
				if (is_numeric($param)) { // is a numeric
					if ($param == intval($param)) { // is a whole number
						if ($param < 20) { // a zoom level
							$zoom_val = $param;
						} else {
							if ($width_found) { //assume a height
								$height_val = $param;
							} else { //assume a width
								$width_val = $param;
								$width_found = true;
							}
						}
					} else { // a decimal number
						$ratio_val = $param;
					}
				} else { // a string
					$param = strtoupper($param);
					if (in_array($param, array("NORMAL","G_NORMAL_MAP","N","ROADMAP","R"))) {
						$type_val = "ROADMAP";
					} else if (in_array($param, array("SATELLITE","G_SATELLITE_MAP","S"))) {
						$type_val = "SATELLITE";
					} else if (in_array($param, array("HYBRID","G_HYBRID_MAP","H"))) {
						$type_val = "HYBRID";
					} else if (in_array($param, array("PHYSICAL","G_PHYSICAL_MAP","P","TERRAIN","T"))) {
						$type_val = "TERRAIN";
					} else if (in_array($param, array("GPX","D","DOWNLOAD"))) {
						$gpx_val = 1;
					}
				}
			}
			
			if ($width_val && $height_val) {
				$ratio_val = $height_val/$width_val;
			}
			
			$ratio_val = round(100*$ratio_val, 2);
			
			$mapid = "map{$postid}n{$count}";
			$en_xml_path = base64_encode($xml_path);
			$code = "<!--thydzikgooglemap-->\r\n".
					"<div class='tgm_wrap' style='position: relative; width: 100%; padding-bottom: {$ratio_val}%'>\r\n".
					"<div class='tgm_div' id='{$mapid}' style='position: absolute; top: 0; bottom: 0; left: 0; right: 0;'></div>\r\n".
					"</div>\r\n".
					"<script type='text/javascript'>\r\n".
					"google.maps.event.addDomListener(window, 'load', function () {thydzikgm('{$mapid}', '{$en_xml_path}', {$zoom_val}, '{$type_val}', {$gpx_val}, '{$xml_ext}'); });\r\n".
					"</script>\r\n".
					"<!--/thydzikgooglemap-->\r\n";

			$val[0] = preg_quote($val[0], "/");
			$content =  preg_replace('/'.$val[0].'/', $code, $content, 1);
		}
	}
	return $content;
}

function urlExists($url = NULL) {
    if ($url == NULL) return false;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36');
    $data = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpcode >= 200 && $httpcode <= 301) {
        return true;
    } else {
        return false;
    }
}

function tgm_admin_menu() {
	if (function_exists("add_submenu_page")) {
		add_submenu_page("plugins.php", "thydzik Google Map","thydzik Google Map", 10, basename(__FILE__), "tgm_submenu_page");
	}
}

function tgm_submenu_page() {
	echo "<div class='wrap'><h2>thydzik Google Map Options</h2>";
	if($_POST['action'] == "save") {
		echo  "<div id='message' class='updated fade'><p>thydzik Google Map Options Updated.</p></div>";
		update_option("thydzikgooglemap_r", $_POST["tgm_r"]);
		update_option("thydzikgooglemap_key", $_POST["tgm_key"]);
		
		if ($_POST["tgm_gpx"]) {
			update_option("thydzikgooglemap_gpx", 'checked');
		} else {
			update_option("thydzikgooglemap_gpx", '');
		}
	}
	
	echo 	"<form name='form' method='post'><p>\r\n".
			"<table border='0'>\r\n".
			"<tr>\r\n".
			"	<td>Default map size (for 4:3 enter 0.75):</td>\r\n".
			"	<td><input type='text' size='4' name='tgm_r' value='".get_option("thydzikgooglemap_r")."'></td>\r\n".
			"</tr>\r\n".
			"<tr>\r\n".
			"	<td>Enable gpx file download:</td>\r\n".
			"	<td><input type='checkbox' name='tgm_gpx' value='anyvalue' ".get_option("thydzikgooglemap_gpx")."></td>\r\n".
			"</tr>\r\n".
			"<tr>\r\n".
			"	<td>API Key:</td>\r\n".
			"	<td><input type='text' size='40' name='tgm_key' value='".get_option("thydzikgooglemap_key")."'></td>\r\n".
			"</tr>\r\n".
			"</table>\r\n".
			"<p class='submit'>\r\n".
			"	<input type='hidden' name='action' value='save'>\r\n".
			"	<input type='submit' name='submit' value='Update options &raquo;'>\r\n".
			"</p></form></div>";
}

// admin hooks
add_action("admin_menu", "tgm_admin_menu");
?>