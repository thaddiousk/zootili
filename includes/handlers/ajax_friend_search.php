<?php 
include("../../config/config.php");
include("../classes/User.php");

$query = $_POST['query'];
$user_logged_in = $_POST['user_logged_in'];

$names = explode(" ", $query);

if (strpos($query, "_") !== false) {
	$users_returned = mysqli_query($con, "SELECT * FROM users WHERE username LIKE '$query%' AND user_closed='no' LIMIT 8");
} else if (count($names) == 2) {
	$users_returned = mysqli_query($con, "SELECT * FROM users WHERE (first_name LIKE '%$names[0]%' AND last_name LIKE '%$names[1]%') AND user_closed='no' LIMIT 8");
} else {
	$users_returned = mysqli_query($con, "SELECT * FROM users WHERE (first_name LIKE '%$names[0]%' OR last_name LIKE '%$names[0]%') AND user_closed='no' LIMIT 8");
}

if ($query != "") {
	while ($row = mysqli_fetch_array($users_returned)) {
		
		$user = new User($con, $user_logged_in);

		if ($row['username'] != $user_logged_in) {
			$mutual_friends = $user->getMutualFriends($row['username']) . " friends in common.";
		} else {
			$mutual_friends = "";
		}

		if ($user->isFriend($row['username']) && $row['username'] != $user_logged_in) {
			echo 	"<div class='result_display'>
						<a href='messages.php?u=" . $row['username'] . "' style='color: #000'>

							<div class='live_search_profile_pic'>
								<img src='" . $row['profile_pic'] . "'>
							</div>

							<div class='live_search_text'>
								" . $row['first_name'] . " " . $row['last_name'] . "
								<p style='margin: 0;'>" . $row['username'] . " </p>
								<p id='grey'>" . $mutual_friends . " </p>
							</div>

						</a>
					</div>";
		}

	}
}

?>