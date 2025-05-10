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
	const modal = document.getElementById('recordingmodal');
	const overlay = document.getElementById('overlay');
	
	vizContainer.innerHTML = "";
	spinner.style.display = "flex";
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
									
									
									if (modal && overlay && !isFocused) {
										overlay.style.display = 'block';
										spinner.style.display = "flex";
										getRecording(titleText);
										
										setTimeout(() => {
											spinner.style.display = "none";
											modal.style.display = 'block';
										}, 750);
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
	const other = parts[2];
	const lang = parts[3];

	const formData = new URLSearchParams();
	formData.append('id', id);
	formData.append('lang', lang);

	fetch('ajax.php?module=dpviz&command=getrecording', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded'
		},
		body: formData
	})
	.then(response => {
    if (!response.ok) throw new Error("Failed to load recording info");
    return response.json();
  })
  .then(async data => {
		console.log("Display name:", data.displayname);
		console.log("Filename(s):", data.filename);

		

		

		

		const displayname = data.displayname;
		const audioList = document.getElementById('audioList');
		audioList.innerHTML = "";

		$('#recording-displayname').html(
			'<a href="config.php?display=recordings&action=edit&id=' + id + '" target="_blank" class="btn btn-default btn-lg">' +
			'<i class="fa fa-bullhorn"></i> Recording: ' + displayname + 
			' <i class="fa fa-external-link" aria-hidden="true"></i></a>'
		);
		
		if (!data.filename || data.filename.trim() === '') {
			throw new Error(`No files found for language: <strong>${lang}</strong>`);
		}
		
		const filenames = data.filename.split('&').filter(f => f.trim() !== '');
		if (filenames.length === 0) {
			throw new Error("Filename array is empty after parsing.");
		}
		
		for (const filename of filenames) {
			console.log("Fetching file:", filename);

			try {
				const response = await fetch('ajax.php?module=dpviz&command=getfile', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: `file=${encodeURIComponent(filename)}`
				});

				if (!response.ok) {
					throw new Error(`Could not fetch ${filename}`);
				}

				const blob = await response.blob();
				const headerFilename = response.headers.get('X-Filename');
				const audioUrl = URL.createObjectURL(blob);

				const container = document.createElement('div');
				container.classList.add('card', 'mb-4', 'custom-card-bg');

				const cardBody = document.createElement('div');
				cardBody.classList.add('card-body');

				const cardTitle = document.createElement('h5');
				cardTitle.classList.add('card-title', 'text-left');
				cardTitle.textContent = `Audio: ${headerFilename}.wav`;
				cardBody.appendChild(cardTitle);

				const audio = document.createElement('audio');
				audio.controls = true;
				audio.src = audioUrl;
				cardBody.appendChild(audio);

				container.appendChild(cardBody);
				audioList.appendChild(container);
			} catch (err) {
				const container = document.createElement('div');
				container.classList.add('recording-container', 'error');

				const label = document.createElement('div');
				label.classList.add('alert', 'alert-warning');
				label.innerHTML = `File: <strong>${filename}.wav</strong> could not be found. To generate the file, simply go to the recording, select the "convert to" wav option, and click submit.`;

				container.appendChild(label);
				audioList.appendChild(container);
			}
		}
	})
	.catch(err => {
		console.error("Fetch error:", err);

		const audioList = document.getElementById('audioList');

		const container = document.createElement('div');
		container.classList.add('recording-container', 'error');

		const label = document.createElement('div');
		label.classList.add('alert', 'alert-danger'); // use alert-danger for more critical errors
		label.innerHTML = `<strong>Error:</strong> ${err.message}`;

		container.appendChild(label);
		audioList.appendChild(container);
	});
}


document.addEventListener('play', function(e) {
  const audios = document.querySelectorAll('audio');
  audios.forEach(audio => {
    if (audio !== e.target) {
      audio.pause();
    }
  });
}, true);

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    const modal = document.getElementById('recordingmodal');
    if (modal && modal.style.display !== 'none') {
      closeModal();
    }
  }
});

function closeModal() {
  const modal = document.getElementById('recordingmodal');
  const overlay = document.getElementById('overlay');
  modal.style.display = 'none';
  overlay.style.display = 'none';

  // Stop and reset all audio elements in the document
  const allAudio = document.querySelectorAll('audio');
  allAudio.forEach(audio => {
    audio.pause();
    audio.currentTime = 0;
  });
}


 