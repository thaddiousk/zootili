<?php 
class Notification {
	private $user_obj;
	private $con;

	public function __construct($con, $user) {
		$this->con = $con;
		$this->user_obj = new User($con, $user);
	}

	public function getUnreadNotifications() {
		$user_logged_in = $this->user_obj->getUsername();
		$query = mysqli_query($this->con, "SELECT * FROM notifications WHERE viewed='no' AND user_to='$user_logged_in'");

		return mysqli_num_rows($query);
	}

	public function getNotifications($data, $limit) {
		$page = $data['page'];
		$user_logged_in = $this->user_obj->getUsername();
		$rtn_str = "";

		if ($page == 1)
			$start = 0;
		else
			$start = ($page - 1) * $limit;

		$set_viewed_query = mysqli_query($this->con, "UPDATE notifications SET viewed='yes' WHERE user_to='$user_logged_in'");

		$query = mysqli_query($this->con, "SELECT * FROM notifications WHERE user_to='$user_logged_in' ORDER BY id DESC");

		if (mysqli_num_rows($query) == 0) {
			echo "You have no notifications!";
			return;
		}

		$num_iterations = 0; // Number of messages checked.
		$count = 1; // Number of messages posted.

		while ($row = mysqli_fetch_array($query)) {

			if ($num_iterations++ < $start)
				continue;

			if ($count++ > $limit)
				break;

			$user_from = $row['user_from'];
			$user_data_query = mysqli_query($this->con, "SELECT * FROM users WHERE username='$user_from'");
			$user_data = mysqli_fetch_array($user_data_query);

			// Timeframe.
			$date_time_now = date("Y-m-d H:i:s");
			$start_date = new DateTime($row['datetime']); // Time of posting.
			$end_date = new DateTime($date_time_now); // Current time.
			$interval = $start_date->diff($end_date); // Difference between dates.
			if ($interval->y >= 1) {
				if ($interval == 1) {
					$time_message = $interval->y . " year ago."; // 1 year ago.
				} else {
					$time_message = $interval->y . " years ago."; // _ years ago.
				}
			} else if ($interval->m >= 1) {
				if ($interval->d == 0) {
					$days = " ago.";
				} else if ($interval->d == 1) {
					$days = $interval->d . " day ago.";
				} else {
					$days = $interval->d . " days ago.";
				}
				if ($interval->m == 1) {
					$time_message = $interval->m . " month, " . $days;
				} else {
					$time_message = $interval->m . " months, " . $days;
				}
			} else if ($interval->d >= 1) {
				if ($interval->d == 1) {
					$time_message = "Yesterday.";
				} else {
					$time_message = $interval->d . " days ago.";
				}
			} else if ($interval->h >= 1) {
				if ($interval->h == 1) {
					$time_message = $interval->h . " hour ago.";
				} else {
					$time_message = $interval->h . " hours ago.";
				}
			} else if ($interval->i >= 1) {
				if ($interval->i == 1) {
					$time_message = $interval->i . " minute ago.";
				} else {
					$time_message = $interval->i . " minutes ago.";
				}
			} else {
				if ($interval->s < 30) {
					$time_message = "Just now.";
				} else {
					$time_message = $interval->s . " seconds ago.";
				}
			}
			// End timeframe block.

			$opened = $row['opened'];
			$style = (isset($row['opened']) && $row['opened'] == 'no') ? "background-color: #DDEDFF" : "";

			$rtn_str .= "<a href='" . $row['link'] . "'>
							<div class='result_display result_display_notification' style='" . $style . "'>
								<div class='notifications_profile_pic'>
									<img src='" . $user_data['profile_pic'] . "'>
								</div>
								<p class='timestamp_smaller' id='grey'>" . $time_message . "</p>" . $row['message'] . "
							</div>
						</a>";
		}

		// If posts were loaded
		if ($count > $limit) {
			$rtn_str .= "<input type='hidden' class='next_page_dropdown_data' value='" . ($page + 1) . "'><input type='hidden' class='no_more_dropdown_data' value='false'>";
		} else {
			$rtn_str .= "<input type='hidden' class='no_more_dropdown_data' value='true'><p style='text-align: center;'>No more notifications to load!</p>";

		}

		return $rtn_str;
	}

	public function insertNotification($post_id, $user_to, $type) {

		$user_logged_in = $this->user_obj->getUsername();
		$user_logged_in_name = $this->user_obj->getFirstAndLastName();

		$date_time = date("Y-m-d H:i:s");

		switch ($type) {
			case 'comment':
				$message = $user_logged_in_name . " commented on your post!";
				break;

			case 'like':
				$message = $user_logged_in_name . " liked your post!";
				break;

			case 'profile_post':
				$message = $user_logged_in_name . " posted on your profile!";
				break;

			case 'comment_non_owner':
				$message = $user_logged_in_name . " commented on a post you commented on!";
				break;

			case 'profile_comment':
				$message = $user_logged_in_name . " commented on your profile post!";
				break;
		}

		$link = "post.php?id=" . $post_id;

		$insert_query = mysqli_query($this->con, "INSERT INTO notifications VALUES('', '$user_to', '$user_logged_in', '$message', '$link', '$date_time', 'no', 'no')");
	}

}
?>