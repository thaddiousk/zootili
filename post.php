<?php 
include("includes/header.php");

if (isset($_GET['id'])) {
	$id = $_GET['id'];
} else {
	$id = 0;
}

?>

<div class="user_details column">
	<a href="<?php echo $user_logged_in; ?>">
		<img src="<?php echo $user['profile_pic']; ?>">
	</a>

	<div class="user_details_left_right">
		<a href="<?php echo $user_logged_in; ?>">
			<?php
			echo $user['first_name'] . " " . $user['last_name']; 
			?>
		</a>
		<br>
		<?php 
		echo "Posts: " . $user['num_posts'];
		?>
		<br>			
		<?php 
		echo "Likes: " . $user['num_likes'];
		?>
	</div>
	
</div>

<div class="main_column column" id="main_column">
	<div class="posts_area">

		<?php
			$post = new Post($con, $user_logged_in);
			$post->getSinglePost($id);

		?>

	</div>
</div>