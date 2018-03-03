window.page = 'home';
$(document).ready(function() {
    var logo = new Image();
    logo.src = "images/dealspotrlogo.png";
    logo.onload = function () {
        var logoContainer = $('#logo-container');
		var introContainer = $('#intro-container');
        logoContainer.animate({opacity: 0}, 0);
		introContainer.animate({opacity: 0}, 0);
        logoContainer.css('background-image', 'url(' + logo.src + ')');
        setTimeout(function () {
            logoContainer.animate({opacity: 1, marginTop: "40px"}, 2000);
			introContainer.animate({opacity: 1, top: "0"}, 2000);
			
        }, 1000);
    };

    $(document).on("click", "#venue-login", function(){
       window.location.href = "http://my.dealchasr.co.uk";
    });
});