<?php 

if (isset($_POST['login_button'])) {

	$email = filter_var($_POST['log_email'], FILTER_SANITIZE_EMAIL); // Ensures email is in correct format.
	$_SESSION['log_email'] = $email; // Keeps email stored in session variable.

	// Password verification.
	$password = strip_tags($_POST['log_password']); // Remove html tags.
	$stmt = $mysqli->prepare("SELECT * FROM users WHERE email=?");
	$stmt->bind_param("s", $email);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	$user_password = $row['password'];
	if (password_verify($password, $user_password)) {

		// Retrieves username from prior database query.
		$username = $row['username'];

		// Reopens user account if they previously closed it.
		$user_closed = 'yes';
		$stmt = $mysqli->prepare("SELECT * FROM users WHERE email=? AND user_closed=?");
		$stmt->bind_param("ss", $email, $user_closed);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		if ($result->num_rows == 1) {
			$reopen_account = mysqli_query($con, "UPDATE users SET user_closed='no' WHERE 
				email='$email'");
			$stmt = $mysqli->prepare("UPDATE users SET user_closed = ? WHERE email = ?");
			$user_closed = 'no';
			$stmt->bind_param("ss", $user_closed, $email);
			$stmt->execute();
		}

		// Saves username into session and redirects user to the main page.
		$_SESSION['username'] = $username;
		header("Location: index.php");
		$stmt->close();
		exit();
	} else {
		array_push($error_array, "Email or password was incorrect.<br>");
	}

}
?>
