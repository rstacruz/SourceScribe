// Usage: $($.hashJump);

;(function($)
{
    $.hashJumpStart = function()
    {
		$('a[href]').click(function()
		{
			return !($.hashJump($(this).attr('href')));
		});
		if (window.location.hash)
		    { $.hashJump(window.location.hash); }
		return $;
    };
    
	$.hashJump = function(href)
	{
        matches = /^(.*)#/.exec(href);
		url = (matches) ? (matches[1]) : '';
	
		// Rudimentary check to see if we're the URL in question
		if (String(window.location).indexOf(url) == -1) { return; }
	
		matches = /#(.*)$/.exec(href);
		if (!matches) { return false; }

        jumpname = matches[1];
		target = $('a[name='+jumpname+']');
		if (!target) { return false; }
    
        // Do it!
        $('.jump-highlight').removeClass('jump-highlight');
        
		offset = target.offset().top - 10;
		$('html, body').animate({ scrollTop: offset }, "slow",
		function() { 
		    id = '#'+jumpname.split('.').join('-');
		    $(id).addClass('jump-highlight');
		    window.setTimeout(function() {
		        $(id).removeClass('jump-highlight');
		    }, 1000);
		} );
		return true;
	};
	$.fn.jump = function()
	{
	    if (this.count == 0) { return this; }
	    offset = this.offset().top;
		$('html, body').animate({ scrollTop: offset }, time != null ? time : 'slow');
	    return this;
    };
})(jQuery);

$($.hashJumpStart);