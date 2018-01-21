<?php
/*
require_once( "sparqllib.php" );

$db = sparql_connect( "localhost:3030/SNCF/query6513547" );
if( !$db ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }
sparql_ns( "rdfs","http://www.w3.org/2000/01/rdf-schema#" );

$sparql = "SELECT ?subject WHERE { ?subject rdfs:label ?name } LIMIT 25";
print $sparql."<br>";
$result = sparql_query( $sparql );
print $result."<br>";
if( !$result ) { print sparql_errno() . ": " . sparql_error(). "\n"; exit; }
$fields = sparql_field_array( $result );

print "<p>Number of rows: ".sparql_num_rows( $result )." results.</p>";
print "<table class='example_table'>";
print "<tr>";

foreach( $fields as $field )
{
	print "<th>$field</th>";
}
print "</tr>";
while( $row = sparql_fetch_array( $result ) )
{
	print "<tr>";
	foreach( $fields as $field )
	{
		print "<td>$row[$field]</td>";
	}
	print "</tr>";
}
print "</table>";

*/
function sparqlQuery($query, $baseURL, $format="json")
{
	$params=array(
		"default-graph" =>  "",
		"should-sponge" =>  "soft",
		"query" =>  $query,
		"debug" =>  "on",
		"timeout" =>  "30000",
		"format" =>  $format,
		"save" =>  "display",
		"fname" =>  ""
	);

	$querypart="?";	
	foreach($params as $name => $value) 
    {
		$querypart=$querypart . $name . '=' . urlencode($value) . "&";
	}
	
	$sparqlURL=$baseURL . $querypart;
	#print $sparqlURL."<br><br>";
    
	return json_decode(file_get_contents($sparqlURL));
};
$keywords=$_GET["keywords"];
$query = "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX ns: <http://www.example.com/GTFS#>
PREFIX prop: <http://www.example.com/GTFS/properties/>
PREFIX geo:   <http://www.w3.org/2003/01/geo/wgs84_pos#>


SELECT ?subject ?name ?lat ?lon ?type
WHERE {
  ?subject a ns:station;
           rdfs:label ?name;
		   geo:long ?lon;
		   geo:lat ?lat;
		   prop:location_type ?type.
  FILTER regex(?name, '$keywords', 'i')
}";

$data=sparqlQuery($query, "http://localhost:3030/SNCF2/sparql");

echo json_encode($data, JSON_PRETTY_PRINT);

?>
