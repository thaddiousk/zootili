<?php 
// The following enables parameterized sql statements.
$mysqli = new mysqli("localhost", "root", "", "social");
if($mysqli->connect_error) {
  exit("Our system's experiencing an error. Please try again later.");
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli->set_charset("utf8mb4");
 ?>
 