<?php 
include("../../config/config.php");
include("../classes/User.php");
include("../classes/Message.php");

$limit = 4; // Number of messages to load.
$message = new Message($con, $_REQUEST['user_logged_in']);
echo $message->getConvosDropdown($_REQUEST, $limit);

?>