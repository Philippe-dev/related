$(function() {
	$filtersform = $('#filters-form');
	$('#filter-control').show().text(dotclear.msg.filter_posts_list);

	if( dotclear.msg.show_filters == 'false' ) {
		$filtersform.hide();
	} else {
		$('#filter-control')
			.addClass('open')
			.text(dotclear.msg.cancel_the_filter);
	}

	$('#filter-control').click(function(e) {
		if( $(this).hasClass('open') ) {
			if( dotclear.msg.show_filters == 'true' ) {
				return true;
			} else {
				$filtersform.hide();
				$(this).removeClass('open')
					   .text(dotclear.msg.filter_posts_list);
			}
		} else {
			$filtersform.show();
			$(this).addClass('open')
				   .text(dotclear.msg.cancel_the_filter);
		}
		e.preventDefault();
	});
});
