$(document).ready(function() {
    $('#check-update-btn').click(function() {
        $('#update-result').html('<div style="margin-top: 10px;">Checking...</div>');

        $.ajax({
            url: 'ajax.php?module=dpviz&command=check_update',
            method: 'POST',
            dataType: 'json',
            
            success: function(response) {
                if (response.status === 'success') {
                    if (response.up_to_date) {
                        $('#update-result').html('<div style="margin-top: 10px;">You are up to date.</div>');
                    } else {
                        $('#update-result').html(
                            '<a href="https://github.com/madgen78/dpviz/releases/latest" target="_blank" class="btn btn-default">' + response.latest + ' available! View on <i class="fa fa-github"></i> GitHub <i class="fa fa-external-link" aria-hidden="true"></i></a> ' +
                            'Current installed version: ' + response.current + ' '
                            
                        );
                    }
                } else {
                    $('#update-result').html('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                $('#update-result').html('AJAX error: ' + error);
            }
        });
    });
		
	document.querySelectorAll('g.node').forEach(node => {
		node.addEventListener('click', function(e) {
			if (e.ctrlKey || e.metaKey) {  // Support Ctrl on Windows/Linux, Command on Mac
				e.preventDefault();

				let titleElement = node.querySelector('title');
				if (titleElement) {
					let titleText = titleElement.textContent || titleElement.innerText;

					let form = document.createElement('form');
					form.method = 'POST';
					form.action = window.location.href;

					let input = document.createElement('input');
					input.type = 'hidden';
					input.name = 'clickedNodeTitle';
					input.value = titleText;
					form.appendChild(input);

					document.body.appendChild(form);
					form.submit();
				}
			}
		});
	});
});