$(document).ready(function() {
	
	// Makes sure the dataTransfer information is sent when we
	// Drop the item in the drop box.
	jQuery.event.props.push('dataTransfer');
	
	var z = -40;
	// The number of images to display
	var maxFiles = 5;
	var errMessage = 0;
	
	// Get all of the data URIs and put them in an array
	var dataArray = [];
	
	// Bind the drop event to the dropzone.
	$('#drop-files').bind('drop', function(e) {
			
		// Stop the default action, which is to redirect the page
		// To the dropped file
		
		var files = e.dataTransfer.files;
		$("#loading_img").css({'display' : 'block'});
		
		
		// For each file
		$.each(files, function(index, file) {
						
			
			
			// Check length of the total image elements
			
			if($('#dropped-files > .image').length < maxFiles) {
				// Change position of the upload button so it is centered
				var imageWidths = ((220 + (40 * $('#dropped-files > .image').length)) / 2) - 20;
				
			}
			
			// Start a new instance of FileReader
			var fileReader = new FileReader();
				
				// When the filereader loads initiate a function
				fileReader.onload = (function(file) {
					
					return function(e) { 
						
						// Push the data URI into an array
						var task_id = document.getElementById('task_id').value;
						dataArray.push({name : file.name, value : this.result, id : task_id});
						var totalPercent = 100 / dataArray.length;
		var x = 0;
		var y = 0;
		
		
		$.each(dataArray, function(index, file) {	
			$.post('http://10.10.10.48/projects/chris/cj_admin/themes/cj_admin/drop/upload.php', dataArray[index], function(data) {
				if(dataArray[index] == '')
				var fileName = dataArray[index].name;
				++x;
				
				
				
				// Show a message showing the file URL.
				var dataSplit = data.split(':');
				$("#loading_img").css({'display' : 'none'});
				if(dataSplit[1] == 'uploaded successfully') {
					$('#dropped-files').append('<li>'+file.name+'<span onclick="delete_file('+dataSplit[0]+')"><img src="../../../themes/cj_admin/images/delete-btn.png" alt="" /></span></li>');
				
					// Add things to local storage 
					if(window.localStorage.length == 0) {
						y = 0;
					} else {
						y = window.localStorage.length;
					}
					
					window.localStorage.setItem(y, realData);
				
				} 
				
			});
			dataArray = [];
		});
						// Move each image 40 more pixels across
						z = z+40;
						var image = this.result;
						
						
						// Just some grammatical adjustments
						
						
						
					}; 
					
				})(files[index]);
				
			// For data URI purposes
			fileReader.readAsDataURL(file);
	
		});
		
	
	});
	
	function restartFiles() {
	
		// This is to set the loading bar back to its default state
		$('#loading-bar .loading-color').css({'width' : '0%'});
		$('#loading').css({'display' : 'none'});
		$('#loading-content').html(' ');
		// --------------------------------------------------------
		
		// We need to remove all the images and li elements as
		// appropriate. We'll also make the upload button disappear
		
		$('#upload-button').hide();
		$('#dropped-files > .image').remove();
		$('#extra-files #file-list li').remove();
		$('#extra-files').hide();
		$('#uploaded-holder').hide();
	
		// And finally, empty the array/set z to -40
		dataArray.length = 0;
		z = -40;
		
		return false;
	}
	
	$('#drop-files').bind('drop', function(e) {
		
		
		
		
		return false;
	});
	
	
	
	$('#dropped-files #delete').click(restartFiles);
	
	// Append the localstorage the the uploaded files section
	if(window.localStorage.length > 0) {
		$('#uploaded-files').show();
		for (var t = 0; t < window.localStorage.length; t++) {
			var key = window.localStorage.key(t);
			var value = window.localStorage[key];
			// Append the list items
			if(value != undefined || value != '') {
				$('#uploaded-files').append(value);
			}
		}
	} else {
		$('#uploaded-files').hide();
	}
});