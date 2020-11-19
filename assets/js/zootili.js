$(document).ready(function() {

	// Ajax call to submit profile post.
	$('#submit_profile_post').click(function(){

		$.ajax({
			type: "POST",
			url: "includes/handlers/ajax_submit_profile_post.php",
			data: $('form.profile_post').serialize(),
			success: function(msg) {
				$("#post_form").modal('hide');
				location.reload();
			},
			error: function() {
				alert('Failure');
			}
		});
	});
});

function getUser(value, user) {
	$.post("includes/handlers/ajax_friend_search.php", {query:value, user_logged_in:user}, function(data) {
		$(".results").html(data);
	});
}

function getDropdownData(user, type) {

	if ($(".dropdown_data_window").css("height") == "0px") {

		var page_name;

		if (type == 'notification') {
			page_name = "ajax_load_notifications.php";
			$("span").remove("#unread_notification");
		} else if (type == 'message') {
			page_name = "ajax_load_messages.php";
			$("span").remove("#unread_message");
		}

		var ajax_req = $.ajax({
			url: "includes/handlers/" + page_name,
			type: "POST",
			data: "page=1&user_logged_in=" + user,
			cache: false,
			success: function(response) {
				$(".dropdown_data_window").html(response);
				$(".dropdown_data_window").css({"padding": "0px", "height" : "222px", "border" : "1px solid #DADADA"});
				$(".dropdown_data_type").val(type);
			}
		});

	} else {
		$(".dropdown_data_window").html("");
		$(".dropdown_data_window").css({"padding": "0px", "height" : "0px", "border" : "none"});
	}

}