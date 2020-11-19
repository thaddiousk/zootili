<?php 
include("../../config/config.php");
include("../classes/User.php");
include("../classes/Post.php");

$limit = 10; // Number of posts loaded during each call.
$posts = new Post($con, $_REQUEST['user_logged_in']);
$posts->loadProfilePosts($_REQUEST, $limit);
?>
