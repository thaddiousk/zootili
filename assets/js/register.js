$(document).ready(function() {

	// On clicking 'signup,' hides login and shows registration form.
	$("#signup").click(function() {
		$("#shown_first").slideUp("slow", function(){
			$("#shown_second").slideDown("slow");
		});
	});

	// On clicking 'register,' hides registration and shows login form.
	$("#signin").click(function() {
		$("#shown_second").slideUp("slow", function(){
			$("#shown_first").slideDown("slow");
		});
	});

});