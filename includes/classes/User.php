<?php 
class User {
	private $user;
	private $con;

	public function __construct($con, $user) {
		$this->con = $con;
		// The following enables parameterized sql statements.
		$mysqli = new mysqli("localhost", "root", "", "social");
		if($mysqli->connect_error) {
		  exit("Our system's experiencing an error. Please try again later.");
		}
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$mysqli->set_charset("utf8mb4");
				$stmt = $mysqli->prepare("SELECT * FROM users WHERE username=?");
		$stmt->bind_param("s", $user);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();
		$this->user = $user;
		$stmt->close();
	}

	public function getUserName() {
		return $this->user['username'];
	}

	public function getNumPosts() {
		$username = $this->user['username'];
		// The following enables parameterized sql statements.
		$mysqli = new mysqli("localhost", "root", "", "social");
		if($mysqli->connect_error) {
		  exit("Our system's experiencing an error. Please try again later.");
		}
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$mysqli->set_charset("utf8mb4");
		$stmt = $mysqli->prepare("SELECT num_posts FROM users WHERE username=?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();
		return $row['num_posts'];
	}

	// Returns user's name in readable format.
	public function getFirstAndLastName() {
		$username = $this->user['username'];
		// The following enables parameterized sql statements.
		$mysqli = new mysqli("localhost", "root", "", "social");
		if($mysqli->connect_error) {
		  exit("Our system's experiencing an error. Please try again later.");
		}
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$mysqli->set_charset("utf8mb4");
		$stmt = $mysqli->prepare("SELECT first_name, last_name FROM users WHERE username=?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();
		return $row['first_name'] . " " . $row['last_name'];
	}

	// Returns user profile picture.
	public function getProfilePic() {
		$username = $this->user['username'];
		// The following enables parameterized sql statements.
		$mysqli = new mysqli("localhost", "root", "", "social");
		if($mysqli->connect_error) {
		  exit("Our system's experiencing an error. Please try again later.");
		}
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$mysqli->set_charset("utf8mb4");
		$stmt = $mysqli->prepare("SELECT profile_pic FROM users WHERE username=?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();
		return $row['profile_pic'];
	}

	// Returns array of user's friends.
	public function getFriendArray() {
		$username = $this->user['username'];
		// The following enables parameterized sql statements.
		$mysqli = new mysqli("localhost", "root", "", "social");
		if($mysqli->connect_error) {
		  exit("Our system's experiencing an error. Please try again later.");
		}
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$mysqli->set_charset("utf8mb4");
		$stmt = $mysqli->prepare("SELECT friend_array FROM users WHERE username=?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();
		return $row['friend_array'];
	}


	public function isClosed() {
		$username = $this->user['username'];
		// The following enables parameterized sql statements.
		$mysqli = new mysqli("localhost", "root", "", "social");
		if($mysqli->connect_error) {
		  exit("Our system's experiencing an error. Please try again later.");
		}
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$mysqli->set_charset("utf8mb4");
		$stmt = $mysqli->prepare("SELECT user_closed FROM users WHERE username=?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();

		if ($row['user_closed'] == 'yes') {
			return true;
		} else {
			return false;
		}
	}

	public function isFriend($username_to_check) {
		$usernameComma = "," . $username_to_check . ",";

		if (strstr($this->user['friend_array'], $usernameComma) 
			|| $username_to_check == $this->user['username']) {
			return true;
		} else {
			return false;
		}
	}

	public function didReceiveRequest($user_from) {
		$user_to = $this->user['username'];
		$check_request_query = mysqli_query($this->con, "SELECT * FROM friend_requests WHERE user_to='$user_to' AND user_from='$user_from'");

		if (mysqli_num_rows($check_request_query) > 0) {
			return true;
		} else {
			return false;
		}
	}

	public function didSendRequest($user_to) {
		$user_from = $this->user['username'];
		$check_request_query = mysqli_query($this->con, "SELECT * FROM friend_requests WHERE user_to='$user_to' AND user_from='$user_from'");

		if (mysqli_num_rows($check_request_query) > 0) {
			return true;
		} else {
			return false;
		}
	}

	public function sendRequest($user_to) {
		$user_from = $this->user['username'];
		$query = mysqli_query($this->con, "INSERT INTO friend_requests VALUES('', '$user_to', '$user_from')");
	}

	public function removeFriend($user_to_remove) {
		$logged_in_user = $this->user['username'];

		// Fetch friend name.
		$query = mysqli_query($this->con, "SELECT friend_array FROM users WHERE username='$user_to_remove'");
		$row = mysqli_fetch_array($query);
		$friend_array_username = $row['friend_array'];

		// Remove friend's name from user's friend list.
		$new_friend_array = str_replace($user_to_remove . ",", "", $this->user['friend_array']);

		// Update database.
		$remove_friend = mysqli_query($this->con, "UPDATE users SET friend_array='$new_friend_array' WHERE username='$logged_in_user'");

		// Remove user's name from friend's friend list.
		$new_friend_array = str_replace($this->user['username'] . ",", "", $friend_array_username);

		// Update database.
		$remove_friend = mysqli_query($this->con, "UPDATE users SET friend_array='$new_friend_array' WHERE username='$user_to_remove'");
	}


	// Returns the number of shared friends between this user and username_to_check.
	public function getMutualFriends($username_to_check) {
		$mutualFriends = 0;
		$user_array = $this->user['friend_array'];
		$user_array_explode = explode(",", $user_array);
		$query = mysqli_query($this->con, "SELECT friend_array FROM users WHERE username='$username_to_check'");
		$row = mysqli_fetch_array($query);
		$user_to_check_array = $row['friend_array'];
		$user_to_check_array_explode = explode(",", $user_to_check_array);

		foreach ($user_array_explode as $i) {
			foreach ($user_to_check_array_explode as $j) {
				if ($i == $j && $i != "") {
					$mutualFriends++;
				}
			}
		}
		return $mutualFriends;
	}

	public function getNumberOfFriendRequests() {
		$username = $this->user['username'];
		// The following enables parameterized sql statements.
		$mysqli = new mysqli("localhost", "root", "", "social");
		if($mysqli->connect_error) {
		  exit("Our system's experiencing an error. Please try again later.");
		}
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$mysqli->set_charset("utf8mb4");
		$stmt = $mysqli->prepare("SELECT * FROM friend_requests WHERE user_to=?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();
		$rtn = mysqli_num_rows($result);
		$stmt->close();
		return $rtn;
	}

	public function getPeopleInRegion() {
		// To-Do
	}

}
?>
