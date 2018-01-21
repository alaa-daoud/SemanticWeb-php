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
$depart=$_GET["depart_name"];
$arrive=$_GET["arrive_name"];
$itenerary=$_GET["type"];
$depart_date=$_GET["depart_date"].":00";
$depart_time=$_GET["depart_time"].":00";
$dayOfWeek = date("l", strtotime($depart_date));
if($itenerary=='direct')
$query = "#( from - to ) filtered by date and time range (between 8&10 am) only direct trips
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX ns-type: <http://www.example.com/GTFS#>
PREFIX ns-prop: <http://www.example.com/GTFS/properties/>
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_post#>
PREFIX time: <https://www.w3.org/TR/owl-time/#time:>

SELECT  distinct ?trip_name ?r_name ?name1 ?depart_time ?name2 ?arrive_time {
  {
    SELECT *
    WHERE {
      ?depatr a ns-type:trip_step;
        ns-prop:depart_time ?depart_time;
        ns-prop:station ?station1;
        ns-prop:belongs_to_trip ?trip.
  ?arrive a ns-type:trip_step;
        ns-prop:arrive_time ?arrive_time;
        ns-prop:station ?station2;
        ns-prop:belongs_to_trip ?trip.
  ?trip rdfs:label ?trip_name;
        ns-prop:of_service ?serv;
        ns-prop:belongs_to_route ?route.
       ?route rdfs:label ?r_name.
   ?serv ns-prop:regular_days ?calendar;
            ns-prop:exception_day ?exc.
     ?calendar ns-prop:available_on time:$dayOfWeek.
      ?exc ns-prop:exception_type ?type.
  ?station1 a ns-type:station;
           rdfs:label ?name1.
  ?station2 a ns-type:station;
           rdfs:label ?name2.
  FILTER regex(?name1, '$depart', 'i').
  FILTER regex(?name2, '$arrive', 'i').
      FILTER (?depart_time<?arrive_time).
      FILTER NOT EXISTS{
        ?exc ns-prop:exception_type 'Remove';
           ns-prop:exception_date '$depart_date'^^xsd:date.
      }.
    }
  }
UNION {
   SELECT  *
WHERE {
  ?depatr a ns-type:trip_step;
        ns-prop:depart_time ?depart_time;
        ns-prop:station ?station1;
        ns-prop:belongs_to_trip ?trip.
  ?arrive a ns-type:trip_step;
        ns-prop:arrive_time ?arrive_time;
        ns-prop:station ?station2;
        ns-prop:belongs_to_trip ?trip.
  ?trip rdfs:label ?trip_name;
        ns-prop:of_service ?serv;
        ns-prop:belongs_to_route ?route.
  ?serv ns-prop:exception_day ?exc.
  ?exc ns-prop:exception_type ?type.
  ?route rdfs:label ?r_name.
  ?station1 a ns-type:station;
           rdfs:label ?name1.
  ?station2 a ns-type:station;
           rdfs:label ?name2.
  FILTER regex(?name1, '$depart', 'i').
  FILTER regex(?name2, '$arrive', 'i').
      FILTER (?depart_time<?arrive_time).
  FILTER EXISTS{{ ?exc ns-prop:exception_type 'Add';
                   ns-prop:exception_date 'depart_date'^^xsd:date. }}
     }
  }
  Filter(?depart_time > '$depart_time'^^xsd:time).
  
}
ORDER BY ?depart_time
LIMIT 10
";
else
	$query="# from to with restrictions of depart and arrive , only one change .. we can unune this with direct trips
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX ns-type: <http://www.example.com/GTFS#>
PREFIX ns-prop: <http://www.example.com/GTFS/properties/>
PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_post#>
PREFIX time: <https://www.w3.org/TR/owl-time/#time:>

SELECT  distinct ?trip_name ?r_name ?name1 ?depart_time ?name3 ?ch_arrive_time  ?trip2_name ?r2_name ?ch_depart_time ?name2 ?arrive_time {
  {
    SELECT *
    WHERE {
      ?depatr a ns-type:trip_step;
        ns-prop:depart_time ?depart_time;
        ns-prop:station ?station1;
        ns-prop:belongs_to_trip ?trip.
      ?ch_step a ns-type:trip_step;
        ns-prop:arrive_time ?ch_arrive_time;
        ns-prop:station ?ch_station;
        ns-prop:belongs_to_trip ?trip.
      ?ch_step2 a ns-type:trip_step;
        ns-prop:depart_time ?ch_depart_time;
        ns-prop:station ?ch_station;
        ns-prop:belongs_to_trip ?trip2.
  ?arrive a ns-type:trip_step;
        ns-prop:arrive_time ?arrive_time;
        ns-prop:station ?station2;
        ns-prop:belongs_to_trip ?trip2.
  ?trip rdfs:label ?trip_name;
        ns-prop:of_service ?serv;
        ns-prop:belongs_to_route ?route.
  ?trip2 rdfs:label ?trip2_name;
        ns-prop:of_service ?serv2;
        ns-prop:belongs_to_route ?route2.
       
       ?route2 rdfs:label ?r2_name.
       ?route rdfs:label ?r_name.
      
   ?serv ns-prop:regular_days ?calendar;
            ns-prop:exception_day ?exc.
     ?calendar ns-prop:available_on time:$dayOfWeek.
      ?exc ns-prop:exception_type ?type.
      
      ?serv2 ns-prop:regular_days ?calendar2;
            ns-prop:exception_day ?exc2.
     ?calendar2 ns-prop:available_on time:$dayOfWeek.
      ?exc2 ns-prop:exception_type ?type2.
      
  ?station1 a ns-type:station;
           rdfs:label ?name1.
  ?station2 a ns-type:station;
           rdfs:label ?name2.
  ?ch_station a ns-type:station;
           rdfs:label ?name3.
  FILTER regex(?name1, '$depart', 'i').
  FILTER regex(?name2, '$arrive', 'i').
      FILTER (?depart_time< ?ch_arrive_time).
      FILTER (?ch_arrive_time< ?ch_depart_time).
      Filter (?ch_step!=?ch_step2 )
      FILTER (?ch_depart_time < ?arrive_time).
      FILTER NOT EXISTS{
        ?exc ns-prop:exception_type 'Remove';
           ns-prop:exception_date '$depart_time'^^xsd:date.
        ?exc2 ns-prop:exception_type 'Remove';
           ns-prop:exception_date '$depart_time'^^xsd:date.
      }.
    }
  }
UNION {
   SELECT  *
WHERE {
   ?depatr a ns-type:trip_step;
        ns-prop:depart_time ?depart_time;
        ns-prop:station ?station1;
        ns-prop:belongs_to_trip ?trip.
      ?ch_step a ns-type:trip_step;
        ns-prop:arrive_time ?ch_arrive_time;
        ns-prop:station ?ch_station;
        ns-prop:belongs_to_trip ?trip.
      ?ch_step2 a ns-type:trip_step;
        ns-prop:depart_time ?ch_depart_time;
        ns-prop:station ?ch_station;
        ns-prop:belongs_to_trip ?trip2.
  ?arrive a ns-type:trip_step;
        ns-prop:arrive_time ?arrive_time;
        ns-prop:station ?station2;
        ns-prop:belongs_to_trip ?trip2.
  ?trip rdfs:label ?trip_name;
        ns-prop:of_service ?serv;
        ns-prop:belongs_to_route ?route.
  ?trip2 rdfs:label ?trip2_name;
        ns-prop:of_service ?serv2;
        ns-prop:belongs_to_route ?route2.
       
       ?route2 rdfs:label ?r2_name.
       ?route rdfs:label ?r_name.
      
   ?serv ns-prop:exception_day ?exc.
    
      ?exc ns-prop:exception_type ?type.
      
      ?serv2 ns-prop:exception_day ?exc2.
      ?exc2 ns-prop:exception_type ?type2.
      
  ?station1 a ns-type:station;
           rdfs:label ?name1.
  ?station2 a ns-type:station;
           rdfs:label ?name2.
  ?ch_station a ns-type:station;
           rdfs:label ?name3.
  FILTER regex(?name1, '$depart','i').
  FILTER regex(?name2, '$arrive','i').
      FILTER (?depart_time< ?ch_arrive_time).
      FILTER (?ch_arrive_time< ?ch_depart_time).
      Filter (?ch_step!=?ch_step2 )
      FILTER (?ch_depart_time < ?arrive_time).
  FILTER EXISTS{{ ?exc ns-prop:exception_type 'Add';
                   ns-prop:exception_date '$depart_date'^^xsd:date. 
        ?exc2 ns-prop:exception_type 'Add';
                   ns-prop:exception_date '$depart_date'^^xsd:date. }}
     }
  }
  Filter(?depart_time > '$depart_time'^^xsd:time).
  
  
  
 
}
ORDER BY  (?arrive_time - ?depart_time) ?depart_time
LIMIT 25 ";


$data=sparqlQuery($query, "http://localhost:3030/SNCF2/sparql");

echo json_encode($data, JSON_PRETTY_PRINT);

?>
