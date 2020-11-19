<?php
class Message {
	private $user_obj;
	private $con;

	public function __construct($con, $user) {
		$this->con = $con;
		$this->user_obj = new User($con, $user);
	}

	public function getMostRecentUser() {
		$user_logged_in = $this->user_obj->getUsername();
		$query = mysqli_query($this->con, "SELECT user_to, user_from FROM messages WHERE user_to='$user_logged_in' OR user_from='$user_logged_in' ORDER BY id DESC LIMIT 1");

		if (mysqli_num_rows($query) == 0) {
			return false;
		}

		$row = mysqli_fetch_array($query);
		$user_to = $row['user_to'];
		$user_from = $row['user_from'];

		if ($user_to != $user_logged_in) {
			return $user_to;
		} else {
			return $user_from;
		}
	}

	public function sendMessage($user_to, $body, $date) {

		if ($body != "") {
			$user_logged_in = $this->user_obj->getUsername();
			$query = mysqli_query($this->con, "INSERT INTO messages VALUES('', '$user_to', '$user_logged_in', '$body', '$date', 'no', 'no', 'no')");
		}
	}

	public function getMessages($other_user) {
		$user_logged_in = $this->user_obj->getUsername();
		$data = "";

		/*
		$query = mysqli_query($this->con, "UPDATE messages SET opened='yes' WHERE user_to='$user_logged_in' AND user_from='$other_user'");

		$get_messages_query = mysqli_query($this->con, "SELECT * FROM messages WHERE (user_to='$user_logged_in' AND user_from='$other_user') OR (user_from='$user_logged_in' AND user_to='$other_user')");

		while ($row = mysqli_fetch_array($get_messages_query)) {
			$user_to = $row['user_to'];
			$user_from = $row['user_from'];
			$body = $row['body'];

			$div_top = ($user_to == $user_logged_in) ? "<div class='message' id='green'>" : "<div class='message' id='blue'>";
			$data = $data . $div_top . $body . "</div><br><br>";
		}
		return $data; */
		$query = mysqli_query($this->con, "UPDATE messages SET opened='yes' WHERE user_to='$user_logged_in' AND user_from='$other_user'");
 
		$get_messages_query = mysqli_query($this->con, "SELECT * FROM messages WHERE (user_to='$user_logged_in' AND user_from='$other_user') OR (user_from='$user_logged_in' AND user_to='$other_user')");
 
		while($row = mysqli_fetch_array($get_messages_query)) {
			$user_to = $row['user_to'];
			$user_from = $row['user_from'];
			$body = $row['body'];
			$id = $row['id'];
 
			$div_top = ($user_to == $user_logged_in) ? "<div class='message' id='green'>" : "<div class='message' id='blue'>";
			$data = $data . $div_top . $body . "</div><br><br>";
		}
		return $data;
	}

	public function getLatestMessage($user_logged_in, $user2) {
		$details_arr = array();
		$query = mysqli_query($this->con, "SELECT body, user_to, date FROM messages WHERE (user_to='$user_logged_in' AND user_from='$user2') OR (user_to='$user2' AND user_from='$user_logged_in') ORDER BY id DESC LIMIT 1");

		$row = mysqli_fetch_array($query);
		$sent_by = ($row['user_to'] == $user_logged_in) ? "They said: " : "You said: ";

		// Timeframe.
		$date_time_now = date("Y-m-d H:i:s");
		$start_date = new DateTime($row['date']); // Time of posting.
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

		array_push($details_arr, $sent_by);
		array_push($details_arr, $row['body']);
		array_push($details_arr, $time_message);

		return $details_arr;
	}

	public function getConvos() {
		$user_logged_in = $this->user_obj->getUsername();
		$rtn_str = "";
		$convos = array();

		$query = mysqli_query($this->con, "SELECT user_to, user_from FROM messages WHERE user_to='$user_logged_in' OR user_from='$user_logged_in' ORDER BY id DESC");

		while ($row = mysqli_fetch_array($query)) {
			$user_to_push = ($row['user_to'] != $user_logged_in) ? $row['user_to'] : $row['user_from'];

			if(!in_array($user_to_push, $convos)) {
				array_push($convos, $user_to_push);
			}
		}

		foreach ($convos as $username) {
			$user_found_obj = new User($this->con, $username);
			$latest_msg_details = $this->getLatestMessage($user_logged_in, $username);

			$dots = (strlen($latest_msg_details[1]) >= 12) ? "..." : "";
			$split = str_split($latest_msg_details[1], 12);
			$split = $split[0] . $dots;

			$rtn_str .= "<a href='messages.php?u=$username'> 
							<div class='user_found_messages'>
							<img src='" . $user_found_obj->getProfilePic() . "' style='border-radius: 5px; margin-right: 5px;'>
							" . $user_found_obj->getFirstAndLastName() . "
							<br>
							<span class='timestamp_smaller' id='grey'> " . $latest_msg_details[2] . "</span>
							<p id='grey' style='margin: 0;'>" . $latest_msg_details[0] . $split . " </p>
							</div>
						</a>";
		}

		return $rtn_str;
	}

	public function getConvosDropdown($data, $limit) {
		$page = $data['page'];
		$user_logged_in = $this->user_obj->getUsername();
		$rtn_str = "";
		$convos = array();

		if ($page == 1)
			$start = 0;
		else
			$start = ($page - 1) * $limit;

		$set_viewed_query = mysqli_query($this->con, "UPDATE messages SET viewed='yes' WHERE user_to='$user_logged_in'");

		$query = mysqli_query($this->con, "SELECT user_to, user_from FROM messages WHERE user_to='$user_logged_in' OR user_from='$user_logged_in' ORDER BY id DESC");

		while ($row = mysqli_fetch_array($query)) {
			$user_to_push = ($row['user_to'] != $user_logged_in) ? $row['user_to'] : $row['user_from'];

			if (!in_array($user_to_push, $convos))
				array_push($convos, $user_to_push);
		}

		$num_iterations = 0; // Number of messages checked.
		$count = 1; // Number of messages posted.

		foreach ($convos as $username) {

			if ($num_iterations++ < $start)
				continue;

			if ($count++ > $limit)
				break;

			$is_unread_query = mysqli_query($this->con, "SELECT opened FROM messages WHERE user_to='$user_logged_in' AND user_from='$username' ORDER BY id DESC");
			$row = mysqli_fetch_array($is_unread_query);

			$style = (isset($row['opened']) && $row['opened'] == 'no') ? "background-color: #DDEDFF" : "";

			$user_found_obj = new User($this->con, $username);
			$latest_msg_details = $this->getLatestMessage($user_logged_in, $username);

			$dots = (strlen($latest_msg_details[1]) >= 12) ? "..." : "";
			$split = str_split($latest_msg_details[1], 12);
			$split = $split[0] . $dots;

			$rtn_str .= "<a href='messages.php?u=$username'> 
							<div class='user_found_messages' style='" . $style . "'>
							<img src='" . $user_found_obj->getProfilePic() . "' style='border-radius: 5px; margin-right: 5px;'>
							" . $user_found_obj->getFirstAndLastName() . "
							<br>
							<span class='timestamp_smaller' id='grey'> " . $latest_msg_details[2] . "</span>
							<p id='grey' style='margin: 0;'>" . $latest_msg_details[0] . $split . " </p>
							</div>
						</a>";
		}

		// If posts were loaded
		if ($count > $limit) {
			$rtn_str .= "<input type='hidden' class='next_page_dropdown_data' value='" . ($page + 1) . "'><input type='hidden' class='no_more_dropdown_data' value='false'>";
		} else {
			$rtn_str .= "<input type='hidden' class='no_more_dropdown_data' value='true'><p style='text-align: center;'>No more messages to load!</p>";

		}

		return $rtn_str;
	}

	public function getNumUnreadMessages() {
		$user_logged_in = $this->user_obj->getUsername();
		$query = mysqli_query($this->con, "SELECT * FROM messages WHERE viewed='no' AND user_to='$user_logged_in'");

		return mysqli_num_rows($query);
	}

}
?>