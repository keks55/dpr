jQuery(document).ready(function($) {

	$(".tab-links a").click(function(e){
		e.preventDefault();
		var tabval = $(this).attr('href');
		$(".tab-content " + tabval).show().siblings().hide();
		$(this).parent().addClass("active").siblings().removeClass("active");
	});

	$('.alert-close').click(function(e){
		e.preventDefault();
		$(this).parent().addClass("closed"); 
	}); 

	//replace <> to < in <pre>
	$('.post pre').each(function() {
   		var text = $(this).text();
    	$(this).text(text.replace('<>', '<')); 
	});
});