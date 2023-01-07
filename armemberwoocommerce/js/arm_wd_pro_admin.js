/* manage product */
jQuery(document).on('change', '.arm_wd_plans', function(){
  var arm_plan_id = jQuery(this).val();
  if (jQuery(this).is(":checked")){
      jQuery('.arm_wd_plans_lbl_'+arm_plan_id).addClass('active');
      jQuery('#arm_discount_type_'+arm_plan_id).find('select').removeAttr('disabled');
      jQuery('#arm_discount_amt_'+arm_plan_id).find('input').removeAttr('disabled');
  } else {
      jQuery('.arm_wd_plans_lbl_'+arm_plan_id).removeClass('active');
      jQuery('#arm_discount_type_'+arm_plan_id).find('select').attr('disabled', 'disabled');
      jQuery('#arm_discount_amt_'+arm_plan_id).find('input').attr('disabled', 'disabled');
  }
});


jQuery(document).ready(function($){

    //Prepopulating our quick-edit post info
    var $inline_editor = inlineEditPost.edit;
    inlineEditPost.edit = function(id){

        //call old copy 
        $inline_editor.apply( this, arguments);

        //our custom functionality below
        var post_id = 0;
        if( typeof(id) == 'object'){
            post_id = parseInt(this.getId(id));
        }

        //if we have our post
        if(post_id != 0){

            //find our row
            $row = $('#edit-' + post_id);

            //post featured
            $plan_ids = $('#arm_plan_id_' + post_id).val().split(',');
            //$post_featured = $post_featured.split('')
            for (var i = 0; i < $plan_ids.length; i++) {
                $plan_id = $plan_ids[i];
                arm_plan_is_selected = $('#arm_wd_plan_'+$plan_id+'_'+post_id).text();
                arm_wd_discount_type = $('#arm_wd_discount_type_'+$plan_id+'_'+post_id).text();
                arm_wd_amount = $('#arm_wd_amount_'+$plan_id+'_'+post_id).text();

                if(arm_plan_is_selected == 'Yes') {
                    $row.find('.arm_wd_plans_'+$plan_id).attr('checked', true);
                    $row.find('.arm_wd_plans_lbl_'+$plan_id).addClass('active');
                    $row.find('#arm_discount_type_'+$plan_id).find('select').removeAttr('disabled');
                    $row.find('#arm_discount_amt_'+$plan_id).find('input').removeAttr('disabled');
                } else {
                    $row.find('.arm_wd_plans_'+$plan_id).attr('checked', false);
                    $row.find('.arm_wd_plans_lbl_'+$plan_id).removeClass('active');
                    $row.find('#arm_discount_type_'+$plan_id).find('select').attr('disabled', 'disabled');
                    $row.find('#arm_discount_amt_'+$plan_id).find('input').attr('disabled', 'disabled');
                }

                
                if(arm_wd_discount_type == 'per'){
                    $row.find('#arm_wd_discount_'+$plan_id+' option[value="per"]').attr('selected', 'selected');
                } else if(arm_wd_discount_type == 'fix'){
                    $row.find('#arm_wd_discount_'+$plan_id+' option[value="fix"]').attr('selected', 'selected');
                } else {
                    $row.find('#arm_wd_discount_'+$plan_id+' option[value="-"]').attr('selected', 'selected');
                }

                if(arm_wd_amount != '-') {
                  $row.find('#arm_wd_amount_'+$plan_id).val(arm_wd_amount);
                }
            }
        }
    }
});


/*

jQuery(document).on('click', '.editinline', function(){
    var tr_id = jQuery(this).parents('tr').attr('id').split('-');     
    var post_id = tr_id[1];
    var tr_edit = jQuery('#edit_'+post_id);
    var arm_wd_no_of_plans = jQuery( '#arm_wd_no_of_plans_'+post_id ).val();
    
    //console.log('repute => '+arm_wd_no_of_plans);
    for (var plan = 1; plan <= arm_wd_no_of_plans; plan++) {
        var plan_id = jQuery( '#arm_wd_plan_id_'+post_id+'_'+plan).val();
        var is_plan_selected = jQuery( '#arm_wd_plan_checked_'+post_id+'_'+plan_id).val();
    
        //console.log(plan+' -- post_id=>'+post_id+' || plan id=>'+plan_id+' || is_plan_selected=>'+is_plan_selected+' --- arm_wd_plan_checked_'+post_id+'_'+plan);
        if(is_plan_selected == 1){
            var arm_wd_discount_type = jQuery( '#arm_wd_discount_type_'+post_id+'_'+plan_id).val();
            var arm_wd_amount = jQuery( '#arm_wd_amount_'+post_id+'_'+plan_id).val();
            console.log(plan+' -- post_id=>'+post_id+' || plan id=>'+plan_id+' || is_plan_selected=>'+is_plan_selected+' || arm_wd_discount_type=>'+arm_wd_discount_type+' || arm_wd_amount=>'+arm_wd_amount);
            console.log(tr_edit.find('#arm_wd_plans [value="'+plan_id+'"]'));
            jQuery('#edit_'+post_id).find('#arm_wd_plans [value="'+plan_id+'"]').attr("checked", "checked");
            jQuery('#edit_'+post_id).find('#arm_wd_per_type_'+plan_id+' [value="'+arm_wd_discount_type+'"]').attr("checked", "checked");
            jQuery('#edit_'+post_id).find('#arm_wd_amount'+plan_id+'').val(arm_wd_amount);
        }
        else 
        {
            console.log(tr_edit.find('#arm_wd_plans [value="'+plan_id+'"]'));
            jQuery('#edit_'+post_id).find('#arm_wd_plans [value="'+plan_id+'"]').attr("checked", false);
            jQuery('#edit_'+post_id).find('#arm_wd_per_type_'+plan_id+' [value="'+arm_wd_discount_type+'"]').attr("checked", false);
            jQuery('#edit_'+post_id).find('#arm_wd_amount'+plan_id+'').val('');
        }
    }
    // var start_date = jQuery('.start-date', '#'+tag_id).text();
    // var end_date = jQuery('.end-date', '#'+tag_id).text();
    // jQuery(':input[name="start-date"]', '.inline-edit-row').val(start_date);
    // jQuery(':input[name="end-date"]', '.inline-edit-row').val(end_date);
});





/*
(function($) {

    // we create a copy of the WP inline edit post function
    var $wp_inline_edit = inlineEditPost.edit;
    // and then we overwrite the function with our own code
    inlineEditPost.edit = function( id ) {

      // "call" the original WP edit function
      // we don't want to leave WordPress hanging
      $wp_inline_edit.apply( this, arguments );

      // now we take care of our business

      // get the post ID
      var $post_id = 0;
      if ( typeof( id ) == 'object' )
         $post_id = parseInt( this.getId( id ) );

      if ( $post_id > 0 ) {

         // define the edit row
         var $edit_row = $( '#edit-' + $post_id );

         // get the release date
	 var $release_date = $( '#release_date-' + $post_id ).text();

	 // populate the release date
	 $edit_row.find( 'input[name="release_date"]' ).val( $release_date );

      }

   };

})(jQuery); 
/*
jQuery(document).load(function(){
    jQuery('.editinline').on('click', function(){
      console.log('reputelog');
        var tag_id = jQuery(this).parents('tr').attr('id');     
        console.log("reputelog tag id = "+tag_id);
        var start_date = jQuery('.start-date', '#'+tag_id).text();
        var end_date = jQuery('.end-date', '#'+tag_id).text();
        jQuery(':input[name="start-date"]', '.inline-edit-row').val(start_date);
        jQuery(':input[name="end-date"]', '.inline-edit-row').val(end_date);
        return false;
    });
});

/*
jQuery( '#bulk_edit' ).on( 'click', function() {
   alert("reputelog = >  bulk alert " );
   // define the bulk edit row
   var $bulk_row = $( '#bulk-edit' );

   // get the selected post ids that are being edited
   var $post_ids = new Array();
   $bulk_row.find( '#bulk-titles' ).children().each( function() {
      $post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
   });

   // get the release date
   var $release_date = $bulk_row.find( 'input[name="release_date"]' ).val();

   alert("reputelog = > "+$release_date  );

   // save the data
   $.ajax({
      url: ajaxurl, // this is a variable that WordPress has already defined for us
      type: 'POST',
      async: false,
      cache: false,
      data: {
         action: 'rachel_carden_save_bulk_edit', // this is the name of our WP AJAX function that we'll set up next
         post_ids: $post_ids, // and these are the 2 parameters we're passing to our function
         release_date: $release_date
      }
   });

});


*/