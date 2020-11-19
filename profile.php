<?php 
include("includes/header.php");

$message_obj = new Message($con, $user_logged_in);

if (isset($_GET['profile_username'])) {
	$username = $_GET['profile_username'];
	$user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$username'");
	$user_array = mysqli_fetch_array($user_details_query);
	$num_friends = (substr_count($user_array['friend_array'], ",")) - 1;
}

if (isset($_POST['remove_friend'])) {
	$user = new User($con, $user_logged_in);
	$user->removeFriend($username);
}

if (isset($_POST['add_friend'])) {
	$user = new User($con, $user_logged_in);
	$user->sendRequest($username);
}

if (isset($_POST['respond_request'])) {
	header("Location: requests.php");
}

if (isset($_POST['post_message'])) {
	if (isset($_POST['message_body'])) {
		$body = mysqli_real_escape_string($con, $_POST['message_body']);
		$date = date("Y-m-d H:i:s");
		$message_obj->sendMessage($username, $body, $date);
	}

	$link = '#profile_tabs a[href="#messages_div"]';

	echo "<script>
			$(function() {
				$('" . $link . "').tab('show');
			});
		</script>";
}

?>

	<style type="text/css">
		.wrapper {
			margin-left: 0px;
			padding-left: 0px;
		}

	</style>

	<div class="profile_left">
		<img src="<?php echo $user_array['profile_pic']; ?>">

		<div class="profile_info">
			<p><?php echo "Posts: " . $user_array['num_posts']; ?></p>
			<p><?php echo "Likes: " . $user_array['num_likes']; ?></p>
			<p><?php echo "Friends: " . $num_friends; ?></p>
		</div>

		<form action="<?php echo $username; ?>" method="POST">
			<?php
				$profile_user_obj = new User($con, $username);
				if ($profile_user_obj->isClosed()) {
					header("Location: user_closed.php");
				}

				$logged_in_user_obj = new User($con, $user_logged_in);
				if ($user_logged_in != $username) {
					if ($logged_in_user_obj->isFriend($username)) {
						echo '<input type="submit" name="remove_friend" class="danger" value="Remove Friend"><br>';
					} else if ($logged_in_user_obj->didReceiveRequest($username)) {
						echo '<input type="submit" name="respond_request" class="warning" value="Respond to Request"><br>';
					} else if ($logged_in_user_obj->didSendRequest($username)) {
						echo '<input type="submit" name="" class="default" value="Request Sent"><br>';
					} else {
						echo '<input type="submit" name="add_friend" class="success" value="Add Friend"><br>';
					}

				}

				?>

		</form>

		<input type="submit" class="deep_blue" data-toggle="modal" data-target="#post_form" value="Post Something">

		<?php

			if ($user_logged_in != $username) {
				echo '<div class="profile_info_bottom">';
					$num_mutual_friends = $logged_in_user_obj->getMutualFriends($username);
					if ($num_mutual_friends == 1) {
						echo $num_mutual_friends . " Mutual friend.";
					} else {
						echo $num_mutual_friends . " Mutual friends.";
					}
				echo '</div>';
			} 


		?>

	</div>

	<div class="profile_main_column column">

		<ul class="nav nav-tabs" role="tablist" id="profile_tabs">
		  <li class="nav-item">
		    <a class="nav-link active" href="#newsfeed_div"	aria-controls="newsfeed_div" role="tab" data-toggle="tab">Newsfeed</a>
		  </li>
		  <!--
		  <li class="nav-item">
		    <a class="nav-link" href="#about_div" aria-controls="about_div" role="tab" data-toggle="tab">About</a>
		  </li>
			-->
		  <li class="nav-item">
		    <a class="nav-link" href="#messages_div" aria-controls="messages_div" role="tab" data-toggle="tab">Messages</a>
		  </li>
		</ul>

		<div class="tab-content">

			<div role="tabpanel" class="tab-pane fade in active" id="newsfeed_div">
				<div class='posts_area'></div>
				<img id="loading" src="assets/images/icons/loading.gif">
			</div>

			<div role="tabpanel" class="tab-pane fade" id="about_div">
				
			</div>

			<div role="tabpanel" class="tab-pane fade" id="messages_div">
				<?php
					echo "<h4>You and <a href='" . $username . "'>" . $profile_user_obj->getFirstAndLastName() . "</a></h4><hr><br>";
					echo "<div class='loaded_messages' id='scroll_messages'>";
						echo $message_obj->getMessages($username);
					echo "</div>";
				?>

				<div class="message_post">
					<form action="" method="POST">
						<textarea name='message_body' id='message_textarea' placeholder='Write your message...'></textarea>
						<input type='submit' name='post_message' class='info' id='message_submit' value='Send' style='font-size:20px;'>
					</form>
					
				</div>

				<script>
					var div = document.getElementById("scroll_messages");
					div.scrollTop = div.scrollHeight;
				</script>
			</div>
			

		</div>

	</div>

	<!-- Scrollable Modal -->
	<div class="modal fade" id="post_form" tabindex="-1" role="dialog" aria-labelledby="postModalLabel" aria-hidden="true">
	  <div class="modal-dialog modal-dialog-scrollable" role="document">
	    <div class="modal-content">

	      <div class="modal-header">
	        <h5 class="modal-title" id="exampleModalScrollableTitle">Post Something!</h5>
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	          <span aria-hidden="true">&times;</span>
	        </button>
	      </div>

	      <div class="modal-body">
	        <p>This will appear on the user's profile page as well as their newsfeed for your friends to see!</p>

	        <form class="profile_post" action="" method="POST">
	        	<div class="form-group">
	        		<textarea class="form-control" name="post_body"></textarea>
	        		<input type="hidden" name="user_from" value="<?php echo $user_logged_in; ?>">
	        		<input type="hidden" name="user_to" value="<?php echo $username; ?>">
	        	</div>
	        </form>
	      </div>

	      <div class="modal-footer">
	        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
	        <button type="button" class="btn btn-primary" name="post_button" id="submit_profile_post">Post</button>
	      </div>
	    </div>
	  </div>
	</div>

	<!-- Infinite Loading Script -->
	<script>
		$(function(){
			var user_logged_in = '<?php echo $user_logged_in; ?>';
			var profileUsername = '<?php echo $username; ?>';
			var inProgress = false;

			loadPosts(); //Load first posts
			$(window).scroll(function() {
				var bottomElement = $(".status_post").last();
				var noMorePosts = $('.posts_area').find('.noMorePosts').val();
				// isElementInViewport uses getBoundingClientRect(), which requires the HTML DOM object, not the jQuery object. The jQuery equivalent is using [0] as shown below.
				if (isElementInView(bottomElement[0]) && noMorePosts == 'false') {
					loadPosts();
				}
			});
			function loadPosts() {
				if(inProgress) { //If it is already in the process of loading some posts, just return
					return;
				}
				inProgress = true;
				$('#loading').show();
				var page = $('.posts_area').find('.nextPage').val() || 1; //If .nextPage couldn't be found, it must not be on the page yet (it must be the first time loading posts), so use the value '1'
				$.ajax({
					url: "includes/handlers/ajax_load_profile_posts.php",
					type: "POST",
					data: "page=" + page + "&user_logged_in=" + user_logged_in + "&profileUsername=" + profileUsername,
					cache:false,

					success: function(response) {
						$('.posts_area').find('.nextPage').remove(); //Removes current .nextpage
						$('.posts_area').find('.noMorePosts').remove(); //Removes current .nextpage
						$('.posts_area').find('.noMorePostsText').remove(); //Removes current .nextpage

						$('#loading').hide();
						$(".posts_area").append(response);

						inProgress = false;
					}
				});
			}

			//Check if the element is in view
			function isElementInView (el) {
				if(el == null) {
				return;
				}

				var rect = el.getBoundingClientRect();

				return (
					rect.top >= 0 &&
					rect.left >= 0 &&
					rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && //* or $(window).height()
					rect.right <= (window.innerWidth || document.documentElement.clientWidth) //* or $(window).width()
				);
			}
		});

	</script>

	</div>
</body>
</html>