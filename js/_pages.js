$(function() {
    $('.checkboxes-helpers').each(function() {
	dotclear.checkboxesHelpers(this);
    });
    $("#pages-list").sortable({
	cursor:'move',
	stop: function( event, ui ) {
	    $("#pages-list tr td input.position").each(function(i) {
		$(this).val(i+1);
	    });
	}
    });
    $("#pages-list tr").hover(function () {
	$(this).css({'cursor':'move'});
    }, function () {
	$(this).css({'cursor':'auto'});
    });

    $("#pages-list tr td input.position").hide();
    $("#pages-list tr td.handle").addClass('handler');
});
