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
