// populate term dropdown when taxonomy is selected
// can't use .change() because the form itself is built via ajax

jQuery(document).on('change', '.taxonomy-select', function(e) {
	var taxSelect = jQuery(this).attr('id');
	var termSelect = taxSelect.replace('-tax', '-term');
	jQuery.ajax({
         type: "GET",
         url: Berkeley_Term_Posts_Widget_taxonomyTerms.ajaxurl,
         dataType: 'html',
         data: ({ action: 'ajax-taxonomy-terms', tax_slug: jQuery(this).val() }),
         success: function(data){
            jQuery('#'+termSelect).html(data);	
         }
     });
	
});