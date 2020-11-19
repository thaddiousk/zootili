<?php 
include("../../config/config.php");
include("../classes/User.php");
include("../classes/Notification.php");

$limit = 7; // Number of messages to load.
$notification = new Notification($con, $_REQUEST['user_logged_in']);
echo $notification->getNotifications($_REQUEST, $limit);

?>