jQuery(document).ready(function(){
	jQuery("#ft_wpecards_edit_card").click(function(){
		jQuery("#ft_wpecard_viewcard").hide();
		jQuery("#ft_wpecards_previewing_card").show();
		return false;
	});
});