<?php
class Post {
	private $user_obj;
	private $con;

	public function __construct($con, $user) {
		$this->con = $con;
		$this->user_obj = new User($con, $user);
	}

	public function submitPost($body, $user_to) {
		//$body = strip_tags($body); // Removes html tags;
		//$body = mysqli_real_escape_string($this->con, $body);

		// Enables line breaks.
		$body = str_replace('\r\n', '\n', $body);
		$body = nl2br($body);

		$body = stripslashes($body);

		$check_empty = preg_replace("/\s+/", "", $body); // Deletes all spaces.
		if($check_empty != "") {

			// Current date and time.
			$date_added = date("Y-m-d H:i:s");
			// Get username.
			$added_by = $this->user_obj->getUserName();

			// If user is on own profile, user_to is 'none.'
			if($user_to == $added_by) {
				$user_to = "none";
			}

			// Insert post into database.
			// $query = mysqli_query($this->con, "INSERT INTO posts VALUES('', '$body', '$added_by', '$user_to', '$date_added', 'no', 'no', '0')");
			$post_id = ''; // Auto-increments.
			$closed = 'no';
			$deleted = 'no'; // Initial value;
			$likes = 0; // Initial value;
			// The following enables parameterized sql statements.
			$mysqli = new mysqli("localhost", "root", "", "social");
			if($mysqli->connect_error) {
			  exit("Our system's experiencing an error. Please try again later.");
			}
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$mysqli->set_charset("utf8mb4");
			$stmt = $mysqli->prepare("INSERT INTO posts VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("ssssssss", $post_id, $body, $added_by, $user_to, $date_added, $closed, $deleted, $likes);
			$stmt->execute();
			// Insert post.
			$returned_id = mysqli_insert_id($this->con);
			$stmt->close();

			// Insert notification.
			if ($user_to != 'none') {
				$notification = new Notification($this->con, $added_by);
				$notification->insertNotification($returned_id, $user_to, "profile_post");
			}

			// Update post count for user.
			$num_posts = $this->user_obj->getNumPosts();
			$num_posts++;
			$update_query = mysqli_query($this->con, "UPDATE users SET num_posts='$num_posts' WHERE username='$added_by'");
		}
	}

	public function loadPostsByFriends($data, $limit) {

		// Ajax functionality helpers (Post scrolling).
		$page = $data['page'];
		$user_logged_in = $this->user_obj->getUserName();

		if ($page == 1)
			$start = 0;
		else
			$start = ($page - 1) * $limit;
		
		$str = ""; // String to return.
		$data_query = mysqli_query($this->con, "SELECT * FROM posts WHERE deleted='no' ORDER BY id DESC");

		if (mysqli_num_rows($data_query) > 0) {

			$num_iterations = 0; // Number of results checked (not necessarily posted).
			$count = 1;

			while ($row = mysqli_fetch_array($data_query)) {
				$id = $row['id'];
				$body = $row['body'];
				$added_by = $row['added_by'];
				$date_time = $row['date_added'];

				// Prepare user_to string for inclusion even if not posted to a user.
				if ($row['user_to'] == "none") {
					$user_to = "";
				} else {
					$user_to_obj = new User($this->con, $row['user_to']);
					$user_to_name = $user_to_obj->getFirstAndLastName();
					$user_to = "to <a href='" . $row['user_to'] . "'>" . $user_to_name . "</a>";
				}

				// Check if user who posted has a closed account.
				$added_by_obj = new User($this->con, $added_by);
				if ($added_by_obj->isClosed()) {
					continue; 
				}

				// Ensures only posts by friends and self are loaded.
				$user_logged_obj = new User($this->con, $user_logged_in);
				if ($user_logged_obj->isFriend($added_by)) {

					if ($num_iterations++ < $start) {
						continue;
					}

					// Once 10 posts have been loaded, break.
					if ($count > $limit) {
						break;
					} else {
						$count++;
					}

					if ($user_logged_in == $added_by) {
						$delete_button = "<button class='delete_button btn-danger' id='post$id'>X</button>";
					} else {
						$delete_button = "";
					}

					$user_details_query = mysqli_query($this->con, "SELECT first_name, last_name, profile_pic FROM users WHERE username='$added_by'");
					$user_row = mysqli_fetch_array($user_details_query);
					$first_name = $user_row['first_name'];
					$last_name = $user_row['last_name'];
					$profile_pic = $user_row['profile_pic'];

					?>

					<script>

						// Toggles the display/nondisplay of post comments.
						function toggle<?php echo $id; ?>() {
							var target = $(event.target);
							if (!target.is("a")) {

								var element = document.getElementById("toggleComment<?php echo $id?>");

								if (element.style.display == "block") {
									element.style.display = "none";
								} else {
									element.style.display = "block";
								}
							}

						}

					</script>

					<?php

					$comments_check = mysqli_query($this->con, "SELECT * FROM comments WHERE post_id = '$id'");
					$comments_check_num = mysqli_num_rows($comments_check);

					// Timeframe.
					$date_time_now = date("Y-m-d H:i:s");
					$start_date = new DateTime($date_time); // Time of posting.
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

					// Output to feed.
					$str .= "<div class='status_post' onClick='javascript:toggle$id()'>
								<div class='post_profile_pic'>
									<img src='$profile_pic' width='50'>
								</div>

								<div class='posted_by' style='color:#ACACAC;'>
									<a href='$added_by'>
										$first_name $last_name
									</a>
									$user_to &nbsp;&nbsp;&nbsp;&nbsp;$time_message
									$delete_button
								</div>

								<div id='post_body'>
									$body
									<br>
									<br>
									<br>
								</div>

								<div class='newsfeedPostOptions'>
									Comments($comments_check_num)&nbsp;&nbsp;&nbsp;
									<iframe src='like.php?post_id=$id' scrolling='no'></iframe>
								</div>


							</div>
							<div class='post_comment' id='toggleComment$id' style='display:none;'>
								<iframe src='comment_frame.php?post_id=$id' id='comment_iframe' frameborder='0'></iframe>
							</div>
							<hr>";
					} // End friend (if) statement.

					?>

					<!-- Delete post functionality -->
					<script>

					$(document).ready(function() {

						$('#post<?php echo $id; ?>').on('click', function() {
							bootbox.confirm("Are you sure you want to delete this post?", function(result) {

								$.post("includes/form_handlers/delete_post.php?post_id=<?php echo $id; ?>", {result:result});

								if(result) {
									setTimeout(function() {
										location.reload();
									}, 300);
								}

							});
						});


					});

				</script>
					<?php

				} // End while loop.

				if ($count > $limit)
					$str .= "<input type='hidden' class='nextPage' value='" . ($page + 1) . "'>
								<input type='hidden' class='noMorePosts' value='false'>";
				else
					$str .= "<input type='hidden' class='noMorePosts' value='true'>
								<p style='text-align: center;'> No more posts to show! </p>";
			}

			echo $str;

	}

	public function loadProfilePosts($data, $limit) {

		// Ajax functionality helpers (Post scrolling).
		$page = $data['page'];
		$profileUser = $data['profileUsername'];
		$user_logged_in = $this->user_obj->getUserName();

		if ($page == 1)
			$start = 0;
		else
			$start = ($page - 1) * $limit;
		
		$str = ""; // String to return.
		$data_query = mysqli_query($this->con, "SELECT * FROM posts WHERE deleted='no' AND ((added_by='$profileUser' AND user_to='none') OR user_to='$profileUser') ORDER BY id DESC");

		if (mysqli_num_rows($data_query) > 0) {

			$num_iterations = 0; // Number of results checked (not necessarily posted).
			$count = 1;

			while ($row = mysqli_fetch_array($data_query)) {
				$id = $row['id'];
				$body = $row['body'];
				$added_by = $row['added_by'];
				$date_time = $row['date_added'];

				if ($num_iterations++ < $start) {
					continue;
				}

				// Once 10 posts have been loaded, break.
				if ($count > $limit) {
					break;
				} else {
					$count++;
				}

				if ($user_logged_in == $added_by) {
					$delete_button = "<button class='delete_button btn-danger' id='post$id'>X</button>";
				} else {
					$delete_button = "";
				}

				$user_details_query = mysqli_query($this->con, "SELECT first_name, last_name, profile_pic FROM users WHERE username='$added_by'");
				$user_row = mysqli_fetch_array($user_details_query);
				$first_name = $user_row['first_name'];
				$last_name = $user_row['last_name'];
				$profile_pic = $user_row['profile_pic'];

				?>

				<script>

					// Toggles the display/nondisplay of post comments.
					function toggle<?php echo $id; ?>() {
						var target = $(event.target);
						if (!target.is("a")) {

							var element = document.getElementById("toggleComment<?php echo $id?>");

							if (element.style.display == "block") {
								element.style.display = "none";
							} else {
								element.style.display = "block";
							}
						}

					}

				</script>

				<?php

				$comments_check = mysqli_query($this->con, "SELECT * FROM comments WHERE post_id = '$id'");
				$comments_check_num = mysqli_num_rows($comments_check);

				// Timeframe.
				$date_time_now = date("Y-m-d H:i:s");
				$start_date = new DateTime($date_time); // Time of posting.
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

				// Output to feed.
				$str .= "<div class='status_post' onClick='javascript:toggle$id()'>
							<div class='post_profile_pic'>
								<img src='$profile_pic' width='50'>
							</div>

							<div class='posted_by' style='color:#ACACAC;'>
								<a href='$added_by'>
									$first_name $last_name
								</a>
								&nbsp;&nbsp;&nbsp;&nbsp;$time_message
								$delete_button
							</div>

							<div id='post_body'>
								$body
								<br>
								<br>
								<br>
							</div>

							<div class='newsfeedPostOptions'>
								Comments($comments_check_num)&nbsp;&nbsp;&nbsp;
								<iframe src='like.php?post_id=$id' scrolling='no'></iframe>
							</div>


						</div>
						<div class='post_comment' id='toggleComment$id' style='display:none;'>
							<iframe src='comment_frame.php?post_id=$id' id='comment_iframe' frameborder='0'></iframe>
						</div>
						<hr>";
					?>

					<!-- Delete post functionality -->
					<script>

					$(document).ready(function() {

						$('#post<?php echo $id; ?>').on('click', function() {
							bootbox.confirm("Are you sure you want to delete this post?", function(result) {

								$.post("includes/form_handlers/delete_post.php?post_id=<?php echo $id; ?>", {result:result});

								if(result) {
									setTimeout(function() {
										location.reload();
									}, 300);
								}

							});
						});


					});

				</script>
				<?php

				} // End while loop.

				if ($count > $limit)
					$str .= "<input type='hidden' class='nextPage' value='" . ($page + 1) . "'>
								<input type='hidden' class='noMorePosts' value='false'>";
				else
					$str .= "<input type='hidden' class='noMorePosts' value='true'>
								<p style='text-align: center;'> No more posts to show! </p>";
			}

			echo $str;

	}

	public function getSinglePost($post_id) {
		// Ajax functionality helpers (Post scrolling).
		$user_logged_in = $this->user_obj->getUserName();

		// Close opened notifications
		$opened_query = mysqli_query($this->con, "UPDATE notifications set opened='yes' WHERE user_to='$user_logged_in' AND link LIKE '%=$post_id'");

		$str = ""; // String to return.
		$data_query = mysqli_query($this->con, "SELECT * FROM posts WHERE deleted='no' AND id='$post_id'");

		if (mysqli_num_rows($data_query) > 0) {

			$row = mysqli_fetch_array($data_query);
			$id = $row['id'];
			$body = $row['body'];
			$added_by = $row['added_by'];
			$date_time = $row['date_added'];

			// Prepare user_to string for inclusion even if not posted to a user.
			if ($row['user_to'] == "none") {
				$user_to = "";
			} else {
				$user_to_obj = new User($this->con, $row['user_to']);
				$user_to_name = $user_to_obj->getFirstAndLastName();
				$user_to = "to <a href='" . $row['user_to'] . "'>" . $user_to_name . "</a>";
			}

			// Check if user who posted has a closed account.
			$added_by_obj = new User($this->con, $added_by);
			if ($added_by_obj->isClosed()) {
				return; 
			}

			// Ensures only posts by friends and self are loaded.
			$user_logged_obj = new User($this->con, $user_logged_in);
			if ($user_logged_obj->isFriend($added_by)) {

				if ($user_logged_in == $added_by) {
					$delete_button = "<button class='delete_button btn-danger' id='post$id'>X</button>";
				} else {
					$delete_button = "";
				}

				$user_details_query = mysqli_query($this->con, "SELECT first_name, last_name, profile_pic FROM users WHERE username='$added_by'");
				$user_row = mysqli_fetch_array($user_details_query);
				$first_name = $user_row['first_name'];
				$last_name = $user_row['last_name'];
				$profile_pic = $user_row['profile_pic'];

				?>

				<script>

					// Toggles the display/nondisplay of post comments.
					function toggle<?php echo $id; ?>() {
						var target = $(event.target);
						if (!target.is("a")) {

							var element = document.getElementById("toggleComment<?php echo $id?>");

							if (element.style.display == "block") {
								element.style.display = "none";
							} else {
								element.style.display = "block";
							}
						}

					}

				</script>

				<?php

				$comments_check = mysqli_query($this->con, "SELECT * FROM comments WHERE post_id = '$id'");
				$comments_check_num = mysqli_num_rows($comments_check);

				// Timeframe.
				$date_time_now = date("Y-m-d H:i:s");
				$start_date = new DateTime($date_time); // Time of posting.
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

				// Output to feed.
				$str .= "<div class='status_post' onClick='javascript:toggle$id()'>
							<div class='post_profile_pic'>
								<img src='$profile_pic' width='50'>
							</div>

							<div class='posted_by' style='color:#ACACAC;'>
								<a href='$added_by'>
									$first_name $last_name
								</a>
								$user_to &nbsp;&nbsp;&nbsp;&nbsp;$time_message
								$delete_button
							</div>

							<div id='post_body'>
								$body
								<br>
								<br>
								<br>
							</div>

							<div class='newsfeedPostOptions'>
								Comments($comments_check_num)&nbsp;&nbsp;&nbsp;
								<iframe src='like.php?post_id=$id' scrolling='no'></iframe>
							</div>


						</div>
						<div class='post_comment' id='toggleComment$id' style='display:none;'>
							<iframe src='comment_frame.php?post_id=$id' id='comment_iframe' frameborder='0'></iframe>
						</div>
						<hr>";

				?>

				<!-- Delete post functionality -->
				<script>

				$(document).ready(function() {

					$('#post<?php echo $id; ?>').on('click', function() {
						bootbox.confirm("Are you sure you want to delete this post?", function(result) {

							$.post("includes/form_handlers/delete_post.php?post_id=<?php echo $id; ?>", {result:result});

							if(result) {
								setTimeout(function() {
									location.reload();
								}, 300);
							}

						});
					});


				});
				</script>
				<?php

			} else { // End friend (if) statement.
				echo "<p>You cannot see this post because you are not friends with this user.</p>";
				return;
			}

		} else {
			echo "<p>No post found. If you clicked a link it may be broken.</p>";
			return;
		}
		echo $str;
	}
}

?>