/* manage category */
jQuery(document).on('change', '.arm_wd_plans', function(){
    var arm_wd_form = jQuery(this).parents('form');
    var arm_plan_id = jQuery(this).val();
    if (jQuery(this).is(":checked")){
        arm_wd_form.find('.arm_wd_plans_lbl_'+arm_plan_id).addClass('active');
        arm_wd_form.find('#arm_discount_type_'+arm_plan_id).find('select').removeAttr('disabled');
        arm_wd_form.find('#arm_discount_amt_'+arm_plan_id).find('input').removeAttr('disabled');
    } else {
        arm_wd_form.find('.arm_wd_plans_lbl_'+arm_plan_id).removeClass('active');
        arm_wd_form.find('#arm_discount_type_'+arm_plan_id).find('select').attr('disabled', 'disabled');
        arm_wd_form.find('#arm_discount_amt_'+arm_plan_id).find('input').attr('disabled', 'disabled');
    }
});


// jQuery(document).on('change', '#arm_wd_plans', function(){
//     var arm_wd_form = jQuery(this).parents('form');
//     var arm_plan_id = jQuery(this).val();
//     if (jQuery(this).is(":checked")){
//         arm_wd_form.find('#arm_discount_opt_'+arm_plan_id).show();
//     }
//     else{
//         arm_wd_form.find('#arm_discount_opt_'+arm_plan_id).hide();
//     }
// });

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
            $row = $('#edit-' + post_id+'.inline-edit-row');

            //post featured
            $plan_ids = $('#arm_plan_id_' + post_id).val().split(',');

            for (var i = 0; i < $plan_ids.length; i++) {
                $plan_id = $plan_ids[i];
                //plan_id = $plan_ids[i];
                arm_plan_is_selected = $('#arm_wd_plan_'+$plan_id+'_'+post_id).text();
                arm_wd_discount_type = $('#arm_wd_discount_type_'+$plan_id+'_'+post_id).text();
                arm_wd_amount = $('#arm_wd_amount_'+$plan_id+'_'+post_id).text();

                //console.log('plan_id=>'+$plan_id+' arm_plan_is_selected=>'+arm_plan_is_selected);

                if(arm_plan_is_selected == 'Yes') {
                    jQuery('.arm_wd_plans_'+$plan_id, '.inline-edit-row').attr('checked', true);
                    jQuery('.arm_wd_plans_lbl_'+$plan_id, '.inline-edit-row').addClass('active');
                    jQuery('#arm_discount_type_'+$plan_id, '.inline-edit-row').find('select').removeAttr('disabled');
                    jQuery('#arm_discount_amt_'+$plan_id, '.inline-edit-row').find('input').removeAttr('disabled');

                    //jQuery('#arm_discount_opt_'+$plan_id, '.inline-edit-row').removeClass('arm_wd_hidden');
                    // $row.find('.arm_wd_plans_'+$plan_id).attr('checked', true);
                    // $row.find('#arm_discount_opt_'+$plan_id).removeClass('arm_wd_hidden');
                } else {
                    jQuery('.arm_wd_plans_'+$plan_id, '.inline-edit-row').attr('checked', false);
                    jQuery('.arm_wd_plans_lbl_'+$plan_id, '.inline-edit-row').removeClass('active');
                    jQuery('#arm_discount_type_'+$plan_id, '.inline-edit-row').find('select').attr('disabled', 'disabled');
                    jQuery('#arm_discount_amt_'+$plan_id, '.inline-edit-row').find('input').attr('disabled', 'disabled');

                    //jQuery('#arm_discount_opt_'+$plan_id, '.inline-edit-row').addClass('arm_wd_hidden');
                    // $row.find('.arm_wd_plans_'+$plan_id).attr('checked', false);
                    // $row.find('#arm_discount_opt_'+$plan_id).addClass('arm_wd_hidden');
                }

                jQuery('#arm_wd_discount_'+$plan_id+' option', '.inline-edit-row').removeAttr('selected', 'selected');
                if(arm_wd_discount_type == 'per'){
                    jQuery('#arm_wd_discount_'+$plan_id+' option[value="per"]', '.inline-edit-row').attr('selected', 'selected');
                    // $row.find('#arm_wd_per_'+$plan_id).attr('checked', true);
                } else if(arm_wd_discount_type == 'fix'){
                    jQuery('#arm_wd_discount_'+$plan_id+' option[value="fix"]', '.inline-edit-row').attr('selected', 'selected');
                    // $row.find('#arm_wd_fix_'+$plan_id).attr('checked', true);
                } else {
                    jQuery('#arm_wd_discount_'+$plan_id+' option[value="-"]', '.inline-edit-row').attr('selected', 'selected');
                }

                if(arm_wd_amount != '-') {
                    jQuery('#arm_wd_amount_'+$plan_id, '.inline-edit-row').val(arm_wd_amount);
                    // $row.find('#arm_wd_amount_'+$plan_id).val(arm_wd_amount);
                } else {
                    jQuery('#arm_wd_amount_'+$plan_id, '.inline-edit-row').val('');
                }
            }
        }
    }
});




/*
jQuery(document).ready(function(){
    jQuery('.editinline').on('click', function(){
        var tag_id = jQuery(this).parents('tr').attr('id').split('-');     
        var term_id = tag_id[1];

         //if we have our post
        if(term_id != 0){

            //find our row
            var row = jQuery('#edit-' + term_id);

            //post featured
            var plan_ids = jQuery('#arm_plan_id_' + term_id).val().split(',');
            //$post_featured = $post_featured.split('')
            for (var i = 0; i < plan_ids.length; i++) {
                var plan_id = plan_ids[i];
                arm_plan_is_selected = jQuery('#arm_wd_plan_'+plan_id+'_'+term_id).text();
                arm_wd_discount_type = jQuery('#arm_wd_discount_type_'+plan_id+'_'+term_id).text();
                arm_wd_amount = jQuery('#arm_wd_amount_'+plan_id+'_'+term_id).text();

                //console.log('plan_id=>'+plan_id+' selected=>'+arm_plan_is_selected+' arm_wd_discount_type=>'+arm_wd_discount_type+' arm_wd_amount=>'+arm_wd_amount);

                console.log('plan_id=>'+plan_id+' selected=>'+arm_plan_is_selected );
                if(arm_plan_is_selected == 'Yes') {
                    console.log('inside if');
                    console.log(row.find('.arm_wd_plans_'+plan_id));
                    jQuery('.arm_wd_plans_'+plan_id, '.inline-edit-row').attr('checked', true);
                    jQuery('#arm_discount_opt_'+plan_id, '.inline-edit-row').removeClass('arm_wd_hidden');
                    // row.find('.arm_wd_plans_'+plan_id).attr('checked', true);
                    // row.find('#arm_discount_opt_'+plan_id).removeClass('arm_wd_hidden');
                } else {
                    console.log('inside else');
                    jQuery('.arm_wd_plans_'+plan_id, '.inline-edit-row').attr('checked', false);
                    jQuery('#arm_discount_opt_'+plan_id, '.inline-edit-row').addClass('arm_wd_hidden');
                    // row.find('.arm_wd_plans_'+plan_id).attr('checked', false);
                    // row.find('#arm_discount_opt_'+plan_id).addClass('arm_wd_hidden');
                }

                if(arm_wd_discount_type == 'per'){
                    jQuery('#arm_wd_per_'+plan_id, '.inline-edit-row').attr('checked', true);
                    //row.find('#arm_wd_per_'+plan_id).attr('checked', true);
                }else{
                  jQuery('#arm_wd_per_'+plan_id, '.inline-edit-row').attr('checked', true);
                    //row.find('#arm_wd_fix_'+plan_id).attr('checked', true);
                }

                if(arm_wd_amount != '-') {
                    jQuery('#arm_wd_amount_'+plan_id, '.inline-edit-row').val(arm_wd_amount);
                    //row.find('#arm_wd_amount_'+plan_id).val(arm_wd_amount);
                }
            }
        }        
    });
});

/*
jQuery(document).ready(function($){

    //Prepopulating our quick-edit post info
    var $inline_editor = inlineEditPost.edit;
    inlineEditPost.edit = function(id){

        //call old copy 
        $inline_editor.apply( this, arguments);

        console.log('reputelog');
        //our custom functionality below
        var post_id = 0;
        if( typeof(id) == 'object'){
            post_id = parseInt(this.getId(id));
        }

        console.log('reputelog'+post_id);

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
                    $row.find('#arm_discount_opt_'+$plan_id).removeClass('arm_wd_hidden');
                } else {
                    $row.find('.arm_wd_plans_'+$plan_id).attr('checked', false);
                    $row.find('#arm_discount_opt_'+$plan_id).addClass('arm_wd_hidden');
                }

                if(arm_wd_discount_type == 'per'){
                    $row.find('#arm_wd_per_'+$plan_id).attr('checked', true);
                }else{
                    $row.find('#arm_wd_fix_'+$plan_id).attr('checked', true);
                }

                if(arm_wd_amount != '-') {
                  $row.find('#arm_wd_amount_'+$plan_id).val(arm_wd_amount);
                }
            }
        }
    }
});

*/