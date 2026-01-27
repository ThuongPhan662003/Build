<?php
  $db_connection = pg_connect("host=localhost dbname=fusionpbx user=fusionpbx password=LuYont4ZP5Y5Sem8aHfuwy8fJRYolrnD8zi1itbI");
  $result = pg_query($db_connection, "SELECT * FROM sunny_care where id=".htmlspecialchars($_GET["id"]));

  header('Access-Control-Allow-Origin: *');

  header('Access-Control-Allow-Methods: GET');

  header("Access-Control-Allow-Headers: X-Requested-With");

  if(!$result) {
    echo "false";
  }else {
    $row = pg_fetch_row($result);
    echo $row[2];
  }
?>
