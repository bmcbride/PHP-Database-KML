<?php
/**
 * Title:   PostGIS to KML
 * Notes:   Query a PostGIS table or view and return the results in KML format.
 * Author:  Bryan R. McBride, GISP
 * Contact: bryanmcbride.com
 * GitHub:  https://github.com/bmcbride/PHP-Database-KML
 */

# Connect to PostgreSQL database
$conn = new PDO('pgsql:host=localhost;dbname=mypostgisdb', 'myusername', 'mypassword');

# Build SQL SELECT statement and return the geometry as a KML element
$sql = 'SELECT *, public.ST_AsKML(public.ST_Transform((the_geom),4326)) as kml FROM mytable';

# Try query or error
$rs = $conn->query($sql);
if (!$rs) {
    echo 'An SQL error occured.\n';
    exit;
}

# Create an array of strings to hold the lines of the KML file.
$kml   = array(
    '<?xml version="1.0" encoding="UTF-8"?>'
);
$kml[] = '<kml xmlns="http://earth.google.com/kml/2.1">';
$kml[] = '<Document>';
$kml[] = '<Style id="generic">';
$kml[] = '<IconStyle>';
$kml[] = '<scale>1.3</scale>';
$kml[] = '<Icon>';
$kml[] = '<href>http://maps.google.com/mapfiles/kml/pushpin/red-pushpin.png</href>';
$kml[] = '</Icon>';
$kml[] = '<hotSpot x="20" y="2" xunits="pixels" yunits="pixels"/>';
$kml[] = '</IconStyle>';
$kml[] = '<LineStyle>';
$kml[] = '<color>ff0000ff</color>';
$kml[] = '<width>2</width>';
$kml[] = '</LineStyle>';
$kml[] = '<PolyStyle>';
$kml[] = '<fill>0</fill>';
$kml[] = '</PolyStyle>';
$kml[] = '</Style>';

# Loop through rows to build placemarks
while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
    $data = $row;
    # Remove kml and geometry fields from data
    unset($data['kml']);
    unset($data['geom']);
    unset($data['the_geom']);
    $kml[] = '<Placemark id="placemark' . $data['gid'] . '">';
    $kml[] = '<name>' . htmlentities($data['gid']) . '</name>';
    $kml[] = '<ExtendedData>';
    # Build extended data from fields
    foreach ($data as $key => $value) {
        $kml[] = '<Data name="' . $key . '">';
        $kml[] = '<value><![CDATA[' . $value . ']]></value>';
        $kml[] = '</Data>';
    }
    $kml[] = '</ExtendedData>';
    $kml[] = '<styleUrl>#generic</styleUrl>';
    $kml[] = $row['kml'];
    $kml[] = '</Placemark>';
}

$kml[]     = '</Document>';
$kml[]     = '</kml>';
$kmlOutput = join("\n", $kml);
header('Content-Type: application/vnd.google-earth.kml+xml kml');
header('Content-Disposition: attachment; filename="data.kml"');
//header ("Content-Type:text/xml");  // For debugging
echo $kmlOutput;
$conn = NULL;
?>