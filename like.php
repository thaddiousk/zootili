<?php
	require 'config/config.php';
	require 'config/mysqli_connect.php';
	include("includes/classes/User.php");
	include("includes/classes/Post.php");
	include("includes/classes/Notification.php");

	if (isset($_SESSION['username'])) {
		$user_logged_in = $_SESSION['username'];
		$stmt = $mysqli->prepare("SELECT * FROM users WHERE username=?");
		$stmt->bind_param("s", $user_logged_in);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();
		$stmt->close();

	} else {
		header("Location: register.php");
	}

	// Get id of post.
	if (isset($_GET['post_id'])) {
		$post_id = $_GET['post_id'];
	}

	$get_likes = mysqli_query($con, "SELECT likes, added_by FROM posts WHERE id='$post_id'");
	$row = mysqli_fetch_array($get_likes);
	$total_likes = $row['likes'];
	$user_liked = $row['added_by'];

	$user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$user_liked'");
	$row = mysqli_fetch_array($user_details_query);
	$total_user_likes = $row['num_likes'];

	// Like button.
	if (isset($_POST['like_button'])) {
		$total_likes++;
		$total_user_likes++;
		$query = mysqli_query($con, "UPDATE posts SET likes='$total_likes' WHERE id='$post_id'");
		$user_likes = mysqli_query($con, "UPDATE users SET num_likes='$total_user_likes'
			WHERE username='$user_liked'");
		$insert_user = mysqli_query($con, "INSERT INTO likes VALUES('', '$user_logged_in', '$post_id')");

		// Insert notification.
		if ($user_liked != $user_logged_in) {
			$notification = new Notification($con, $user_logged_in);
			$notification->insertNotification($post_id, $user_liked, "like");
		}

	}

	// Unlike button.
	if (isset($_POST['unlike_button'])) {
		$total_likes--;
		$total_user_likes--;
		$query = mysqli_query($con, "UPDATE posts SET likes='$total_likes' WHERE id='$post_id'");
		$user_likes = mysqli_query($con, "UPDATE users SET num_likes='$total_user_likes'
			WHERE username='$user_liked'");
		$insert_user = mysqli_query($con, "DELETE FROM likes WHERE username='$user_logged_in' 
			AND post_id=$post_id");

	}

	// Check for previous likes.
	$check_query = mysqli_query($con, "SELECT * FROM likes WHERE username='$user_logged_in' 
		AND post_id='$post_id'");
	$num_rows = mysqli_num_rows($check_query);

	if ($num_rows > 0) {
		echo '<form action="like.php?post_id=' . $post_id . '" method="POST">
				<input type="submit" class="comment_like" name="unlike_button" value="Unlike">
				<div class="like_value">
					'. $total_likes .' Likes
				</div>
			</form>
		';
	}
	else {
		echo '<form action="like.php?post_id=' . $post_id . '" method="POST">
				<input type="submit" class="comment_like" name="like_button" value="Like">
				<div class="like_value">
					'. $total_likes .' Likes
				</div>
			</form>
		';
	}


?>
<!DOCTYPE html>
<html>
<head>
	<title></title>
	<link rel="stylesheet" type="text/css" href="assets/css/style.css">
</head>
<body>

	<style type="text/css">
	* {
		font-family: Arial, Helvetica, Sans-serif;
	}
	body {
		background-color: #FFF;
	}

	form {
		position: absolute;
		top: 0;
	}

	</style>

</body>
</html>