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
	event.preventDefault(); 

	var $form = $(this);
	var formData = $form.serialize();
	var processed = document.getElementById('processed')?.value || '';
	var ext = document.getElementById('ext')?.value || '';
	var cid = document.getElementById('cid')?.value || '';
	var jump = document.getElementById('jump')?.value || '';
	var pan = $form.find('input[name="panzoom"]:checked').val();

	$.ajax({
		type: 'POST',
		url: $form.attr('action'),
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
	const vizContainer = document.getElementById("vizContainer");
	const spinner = document.getElementById("vizSpinner");
	vizContainer.innerHTML = "";  //clear contents
	spinner.style.display = "flex"; //show spinner
  $.ajax({
    url: 'ajax.php?module=dpviz&command=make',
    type: 'POST',
    data: JSON.stringify({
			ext: ext,
			cid: cid,
			jump: jump
		}),
		
    dataType: 'json',
    success: function(response) {
			
      document.getElementById("floating-nav-bar").classList.remove("show");
      $('#vizButtons').html(response.vizButtons);
      $('#vizContainer').html(response.vizHeader);
			
      if (response.gtext) {
				//console.log(response.gtext);
				let dot = response.gtext
					.replace(/\\n/g, '\n')
					.replace(/\\l/g, '\l');

				viz.renderSVGElement(dot)
					.then(function(element) {
						isFocused = false;
            svgContainer = element;
            vizContainer.appendChild(element);
						spinner.style.display = "none";  //hide spinner
            var svgElement = document.querySelector('#graph0');
            if (svgElement && pan === "1") {
							panzoom(svgElement, {
								zoomDoubleClickSpeed: 1, //disables double click to zoom
							});
						}
						
						// Ctrl/Command + click handler for Graphviz nodes
						element.querySelectorAll('g.node').forEach(node => {
							node.addEventListener('click', function (e) {
								const titleElement = node.querySelector('title');

								if (!titleElement) return;

								const titleText = titleElement.textContent || titleElement.innerText || "";

								// Check for "Play Recording:" pattern
								if (titleText.startsWith("play-system-recording")) {
									e.preventDefault();
									const modal = document.getElementById('recordingmodal');
									const overlay = document.getElementById('overlay');
									if (modal && overlay && !isFocused) {
										overlay.style.display = 'block';
										modal.style.display = 'block';
										getRecording(titleText);
									}
								}
								
								// Support Ctrl/Meta key for other actions
								if (e.ctrlKey || e.metaKey) {
									e.preventDefault();
								 generateVisualization(ext, cid, titleText, pan);
								}

							});
						});


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
			spinner.style.display = "none";  // Hide spinner

			const errorMsg = `
					<strong>AJAX Error:</strong><br>
					Status: ${status}<br>
					Error: ${error}<br>
					HTTP Status: ${xhr.status}<br>
					Response: ${xhr.responseText}
			`;

			$('#vizContainer').html(errorMsg);
			console.error('AJAX Error:', status, error);
		}
  });
}


function getRecording(titleid) {
	const parts = titleid.split(",");
	const id = parts[1];

	const formData = new URLSearchParams();
	formData.append('id', id);

	fetch('ajax.php?module=dpviz&command=getrecording', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded'
		},
		body: formData
	})
	.then(response => {
		if (!response.ok) throw new Error("Audio not found, has multiple parts, or unreadable.");

		const displayname = response.headers.get('X-Displayname');
		const filename = response.headers.get('X-Filename');
		
		if (displayname) {
			document.getElementById('recording-displayname').innerText = `Recording: ${displayname}`;
		}
		if (filename) {
			document.getElementById('recording-filename').innerText = `Filename: ${filename}.wav`;
		}
		

		return response.blob();
	})
	.then(blob => {
		const audioUrl = URL.createObjectURL(blob);
		const audioSource = document.getElementById('audioSource');
		const audioPlayer = document.getElementById('audioPlayer');

		audioSource.src = audioUrl;
		audioPlayer.style.display = 'block';
		audioPlayer.load();
	})
	.catch(error => {
		console.error("Error:", error);
		document.getElementById('audioPlayer').style.display = 'none';
		document.getElementById('recording-displayname').innerText = '';
		document.getElementById('recording-filename').innerText = error.message;
	});
}



function closeModal() {
	
	const modal = document.getElementById('recordingmodal');
	const overlay = document.getElementById('overlay');
  modal.style.display = 'none';
	overlay.style.display = 'none';

  // Stop and reset any audio inside the modal
  const audio = modal.querySelector('audio');
  if (audio) {
    audio.pause();
    audio.currentTime = 0;
  }
	
}


 