// process the form
jQuery(document).on("submit", "[id^='del-gf-upload-']", function(event) {
		event.preventDefault();
		var result = confirm("Are you sure you want to delete '" + jQuery(this).find('input[name=filename]').val() + "'?" );
		if (result) {
				//Logic to delete the item
			jQuery(this).find(':submit').html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>');
			jQuery(this).find(':submit').addClass('disabled');
			 // get the form data
			// there are many ways to get this data using jQuery (you can use the class or id also)
			var formData = {
				'entrynum': 		jQuery(this).find('input[name=entrynum]').val(),
				'fieldid' : 		jQuery(this).find('input[name=fieldid]').val(),
				'filename': 		jQuery(this).find('input[name=filename]').val()
			};
			
			//console.log(formData);
			//console.log(jQuery(this).attr('action'));			
			jQuery.post(jQuery(this).attr('action'),
			{
				entrynum: formData['entrynum'],
				fieldid: formData['fieldid'],
				filename: formData['filename']
				
			},
			function(data, status){
				var dataarray = JSON.parse(data);
				if(!dataarray.success) {
					
					if (dataarray.errors.entrynum)
						jQuery("#output-upload-errors").html(dataarray.errors.entrynum);
										
					if (dataarray.errors.fieldid)
						jQuery("#output-upload-errors").html(dataarray.errors.fieldid);
										
					if (dataarray.errors.fieldname)
						jQuery("#output-upload-errors").html(dataarray.errors.fieldname);
										
					if (dataarray.errors.unremoved)
						jQuery("#output-upload-errors").html(dataarray.errors.unremoved);
										
					if (dataarray.errors.unupdated)
						jQuery("#output-upload-errors").html(dataarray.errors.unupdated);			
					
				} else {
					jQuery('#output-display-uploads').html(dataarray.output);
				}
			});
		}
});


jQuery(document).ready(function() {
	//event.stopPropagation();
	//event.preventDefault();
	
	var options = { 
		beforeSend: function() 
		{
			jQuery("#progress").show();
			//clear everything
			jQuery("#bar").width('0%');
			jQuery("#message").html("");
			jQuery("#percent").html("0%");
		},
		uploadProgress: function(event, position, total, percentComplete) 
		{
			jQuery("#bar").width(percentComplete+'%');
			jQuery("#percent").html(percentComplete+'%');

		
		},
		success: function() 
		{

		},
		complete: function(response) 
		{
			var parsed = JSON.parse(response.responseText);			
			if(!parsed.success) {
				
					if (parsed.errors.entrynum)
						jQuery("#output-upload-errors").html(parsed.errors.entrynum);
					if (parsed.errors.fieldid)
						jQuery("#output-upload-errors").html(parsed.errors.fieldid);
					if (parsed.errors.fieldname)
						jQuery("#output-upload-errors").html(parsed.errors.fieldname);
					if (parsed.errors.exists)
						jQuery("#output-upload-errors").html(parsed.errors.exists);
					if (parsed.errors.size)
						jQuery("#output-upload-errors").html(parsed.errors.size);											
					if (parsed.errors.type)
						jQuery("#output-upload-errors").html(parsed.errors.type);											
					if (parsed.errors.unupdated)
						jQuery("#output-upload-errors").html(parsed.errors.unupdated);			
					
					jQuery('#output-upload-errors').show();
			} else {
				jQuery('#output-display-uploads').html(parsed.output);

			}			
			//reset
			jQuery("#bar").width('0%');
			jQuery("#message").html("");
			jQuery("#percent").html("0%");
			jQuery("#fileToUpload").val("");
			jQuery("#form-file-upload").hide();
			jQuery("#progress").hide();
			jQuery("#reveal-file-upload").show();
		},
		error: function()
		{
			jQuery("#upload-form-message").html("<font color='red'> ERROR: unable to upload files</font>");

		}
	}; 

    jQuery("[class^='upload-form']").ajaxForm(options); 
	jQuery("#output-upload-errors").hide();
});


jQuery(document).on("click", "#reveal-file-upload", function(event) {
	jQuery("#form-file-upload").show();
	jQuery("#progress").show();
	jQuery("#reveal-file-upload").hide();
	jQuery("#output-upload-errors").hide();
});
