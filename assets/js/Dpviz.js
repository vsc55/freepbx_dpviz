$(document).ready(function() {
	  //github update check
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

});

//Save Setting, then show Dial Plan tab.
$('#dpvizForm').submit(function(event) {
	event.preventDefault(); // Always prevent first

	var $form = $(this);
	var formData = $form.serialize(); // serialize() is okay here
	var processed = document.getElementById('processed')?.value || '';
	var ext = document.getElementById('ext')?.value || '';
	var cid = document.getElementById('cid')?.value || '';
	var jump = document.getElementById('jump')?.value || '';
	var pan = $form.find('input[name="panzoom"]:checked').val();

	$.ajax({
		type: 'POST',
		url: $form.attr('action'), // Use the form's action attribute
		data: formData,
		success: function(response) {
			var saveButton = document.getElementById("saveButton");
			var originalContent = saveButton.innerHTML;

			saveButton.innerHTML = '<i class="fa fa-check"></i> Saved!';
			
			setTimeout(function() {
				if (processed === 'yes') {
					generateVisualization(ext,cid,jump,pan);
				}
				saveButton.innerHTML = originalContent;
				$('.nav-tabs li[data-name="dpbox"] a').tab('show'); // Switch tab
			}, 1250);
			
		},
		error: function(error) {
			alert('Form submission failed: ' + error.statusText);
			document.getElementById('saveResponse').textContent = "Request failed.";
		}
	});
});

function generateVisualization(ext, cid, jump, pan) {
	
  $.ajax({
    url: 'ajax.php?module=dpviz&command=make',
    type: 'POST',
    data: {
      ext: ext,
      cid: cid,
      jump: jump,
    },
		
    dataType: 'json',
    success: function(response) {
      document.getElementById("floating-nav-bar").classList.remove("show");
      document.getElementById("vizContainer").innerHTML = "";
      $('#vizButtons').html(response.vizButtons);
      $('#vizContainer').html(response.vizHeader);

      if (response.gtext) {
				
        viz.renderSVGElement(response.gtext)
          .then(function(element) {
						isFocused = false;
            svgContainer = element;
            document.getElementById("vizContainer").appendChild(element);

            var svgElement = document.querySelector('#graph0');
            if (svgElement && pan === "1") {
							panzoom(svgElement);
						}

            element.querySelectorAll("g.node").forEach(node => {
              node.addEventListener("click", function(e) {
                if (isFocused) {
                  selectedNodeId = this.id;
                  highlightPathToNode(this.id);
                  e.preventDefault();
                  e.stopPropagation();
                  return false;
                }
              });
            });

            element.querySelectorAll("g.edge").forEach(edge => {
              edge.addEventListener("click", function(e) {
                if (isFocused) {
                  toggleEdgeHighlight(this.id);
                  e.preventDefault();
                  e.stopPropagation();
                  return false;
                }
              });
            });

          })
          .catch(error => {
            console.error('Viz.js render error:', error);
          });
      } else {
        console.error('No gtext found in response.');
      }
    },
    error: function(xhr, status, error) {
      console.error('AJAX Error:', status, error);
    }
  });
}


 