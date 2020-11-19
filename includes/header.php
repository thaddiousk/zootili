<?php
require 'config/config.php';
require 'config/mysqli_connect.php';
include("includes/classes/User.php");
include("includes/classes/Post.php");
include("includes/classes/Message.php");
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

?>

<!DOCTYPE html>
<html>
<head>
	<title>Welcome to Zootili!</title>

	<!-- Javascript -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	<script src="assets/js/bootstrap.js"></script>
	<script src="assets/js/bootbox.min.js"></script>
	<script src="assets/js/zootili.js"></script>
	<script src="assets/js/jquery.jcrop.js"></script>
	<script src="assets/js/jcrop_bits.js"></script>

	<!-- CSS -->
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="assets/css/bootstrap.css">
	<link rel="stylesheet" type="text/css" href="assets/css/style.css">
	<link rel="stylesheet" type="text/css" href="assets/css/jquery.Jcrop.css">

</head>
<body>

	<div class="top_bar">

		<div class="logo">
			<a href="index.php">Zootili!</a>
		</div>

		<div class="search">
			<form action="search.php" method="GET" name="search_form">
				<input type="text" onkeyup="getLiveSearchUsers(this.value, '<?php echo $user_logged_in; ?>')" name="q" placeholder="Search..." autocomplete="off" id="search_text_input">

				<div class="button_holder">
					<img src="assets/images/icons/hand_glass.png">
				</div>
			</form>

			<div class="search_results"></div>
			<div class="search_results_footer_empty"></div>
			
		</div>

		<nav>

			<?php 
				// Unread messages.
				$messages = new Message($con, $user_logged_in);
				$num_messages = $messages->getNumUnreadMessages();

				// Unread notifications.
				$notifications = new Notification($con, $user_logged_in);
				$num_notifications = $notifications->getUnreadNotifications();

				// Unread friend requests.
				$user_obj = new User($con, $user_logged_in);
				$num_requests = $user_obj->getNumberOfFriendRequests();
			?>

			<a href="<?php echo $user_logged_in; ?>">
				<?php echo $user['first_name']; ?>
			</a>
			<a href="index.php">
				<i class="fa fa-home fa-lg"></i>
			</a>
			<a href="#Javascript:void(0);" onclick="getDropdownData('<?php echo $user_logged_in; ?>', 'message')">
				<i class="fa fa-envelope fa-lg"></i>
				<?php
				if ($num_messages > 0)
					echo '<span class="notification_badge id="unread_message">' . $num_messages . '</span>';
				?>
			</a>
			<a href="#Javascript:void(0);" onclick="getDropdownData('<?php echo $user_logged_in; ?>', 'notification')">
				<i class="fa fa-bell-o fa-lg"></i>
				<?php
				if ($num_notifications > 0)
					echo '<span class="notification_badge id="unread_notification">' . $num_notifications . '</span>';
				?>
			</a>
			<a href="requests.php">
				<i class="fa fa-users fa-lg"></i>
				<?php
				if ($num_requests > 0)
					echo '<span class="notification_badge id="unread_requests">' . $num_requests . '</span>';
				?>
			</a>
			<a href="#">
				<i class="fa fa-cog fa-lg"></i>
			</a>
			<a href="includes/handlers/logout.php">
				<i class="fa fa-sign-out fa-lg"></i>
			</a>
		</nav>

		<div class="dropdown_data_window" style="height: 0px; border: none;"></div>
		<input type="hidden" name="" id="dropdown_data_type" value="">

	</div>

	<!-- Infinite Loading Script -->
	<script>
	var user_logged_in = '<?php echo $user_logged_in; ?>';

	$(document).ready(function() {

		$('.dropdown_data_window').scroll(function() {
			var inner_height = $('.dropdown_data_window').innerHeight(); //Div containing data
			var scroll_top = $('.dropdown_data_window').scrollTop();
			var page = $('.dropdown_data_window').find('.next_page_dropdown_data').val();
			var noMoreData = $('.dropdown_data_window').find('.no_more_dropdown_data').val();

			if ((scroll_top + inner_height >= $('.dropdown_data_window')[0].scrollHeight) && noMoreData == 'false') {

				var page_name; //Holds name of page to send ajax request to
				var type = $('#dropdown_data_type').val();

				if(type == 'notification')
					page_name = "ajax_load_notifications.php";
				else if(type = 'message')
					page_name = "ajax_load_messages.php"


				var ajaxReq = $.ajax({
					url: "includes/handlers/" + page_name,
					type: "POST",
					data: "page=" + page + "&user_logged_in=" + user_logged_in,
					cache:false,

					success: function(response) {
						$('.dropdown_data_window').find('.next_page_dropdown_data').remove(); //Removes current .nextpage 
						$('.dropdown_data_window').find('.no_more_dropdown_data').remove(); //Removes current .nextpage 
						$('.dropdown_data_window').append(response);
					}
				});

			} //End if 

			return false;

		}); //End (window).scroll(function())

	});

	</script>

	<!--
	<script>
		$(function(){
			var user_logged_in = '<?php echo $user_logged_in; ?>';
			var inProgress = false;

			$(window).scroll(function() {
				var bottomElement = $(".dropdown_data_window").last();
				var noMoreData = $('.dropdown_data_window').find('.no_more_dropdown_data').val();
				// isElementInViewport uses getBoundingClientRect(), which requires the HTML DOM object, not the jQuery object. The jQuery equivalent is using [0] as shown below.
				if (isElementInView(bottomElement[0].scrollHeight) && noMoreData == 'false') {
					loadPosts();
				}
			});
			function loadPosts() {
				if(inProgress) { //If it is already in the process of loading some posts, just return
					return;
				}
				inProgress = true;
				var page_name; // Name of page to send request to.
				var type = $('#dropdown_data_type').val();

				if (type == 'notification')
					page_name = "ajax_load_notifications.php";
				else if (type == 'message') {
					page_name = "ajax_load_messages.php";
				}

				var page = $('.dropdown_data_window').find('.next_page_dropdown_data').val() || 1; //If .nextPage couldn't be found, it must not be on the page yet (it must be the first time loading posts), so use the value '1'
				$.ajax({
					url: "includes/handlers/" + page_name,
					type: "POST",
					data: "page=" + page + "&user_logged_in=" + user_logged_in,
					cache: false,

					success: function(response) {
						$('.dropdown_data_window').find('.next_page_dropdown_data').remove(); //Removes current .nextpage
						$('.dropdown_data_window').find('.no_more_dropdown_data').remove(); //Removes current .nextpage
						$(".dropdown_data_window").append(response);

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
-->
	
	<div class="wrapper"> 
