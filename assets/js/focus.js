if (document.getElementById("reloadButton")) {
// Use the most reliable way to prevent default for focus button
	// Stop the event from bubbling up
document.getElementById("focus").addEventListener("click", function(e) {
	e.stopPropagation();
	// Prevent the default action
	e.preventDefault();
	
	// Toggle focus mode
	toggleFocusMode();
	
	// Return false for extra measure
	return false;
}, false);

function toggleEdgeHighlight(edgeId) {
	if (!svgContainer) return;
	
	const edge = document.getElementById(edgeId);
	if (!edge) return;
	
	// Check if this edge is already highlighted
	if (highlightedEdges.has(edgeId)) {
		// Remove highlight
		highlightedEdges.delete(edgeId);
		
		// Reset edge style
		const edgePath = edge.querySelector("path");
		if (edgePath) {
			edgePath.style.stroke = "";
			edgePath.style.strokeWidth = "";
		}
		
		// Reset arrowhead
		const polygon = edge.querySelector("polygon");
		if (polygon) {
			polygon.style.fill = "";
			polygon.style.stroke = "";
		}
		
		// Reset edge text
		const textElements = edge.querySelectorAll("text");
		textElements.forEach(text => {
			text.style.fill = "";
			text.style.fontWeight = "";
		});
	} else {
		// Add highlight
		highlightedEdges.add(edgeId);
		
		// Highlight edge
		const edgePath = edge.querySelector("path");
		if (edgePath) {
			edgePath.style.stroke = "red";
			edgePath.style.strokeWidth = "3px";
		}
		
		// Highlight arrowhead
		const polygon = edge.querySelector("polygon");
		if (polygon) {
			polygon.style.fill = "red";
			polygon.style.stroke = "red";
		}
		
		// Highlight edge text
		const textElements = edge.querySelectorAll("text");
		textElements.forEach(text => {
			text.style.fill = "red";
			text.style.fontWeight = "bold";
		});
	}
}

function resetEdges() {
	if (!svgContainer) return;
	
	// Clear highlighted edges set
	highlightedEdges.clear();
	
	// Reset only edge paths
	svgContainer.querySelectorAll("g.edge path").forEach(path => {
		path.style.stroke = "";
		path.style.strokeWidth = "";
	});
	
	// Reset only arrowheads in edges
	svgContainer.querySelectorAll("g.edge polygon").forEach(polygon => {
		polygon.style.fill = "";
		polygon.style.stroke = "";
	});
	
	// Reset edge text (labels)
	svgContainer.querySelectorAll("g.edge text").forEach(text => {
		text.style.fill = "";
		text.style.fontWeight = "";
	});
}

function toggleFocusMode() {
	if (!svgContainer) return;

	if (isFocused) {
		// Exit focus mode
		resetEdges();
		restoreLinks();
		isFocused = false;
		document.getElementById("focus").textContent = "Highlight Paths";
		document.getElementById("focus").classList.remove("active");
		//document.getElementById("focus").classList.remove("btn-primary");
		document.getElementById("focus").classList.add("btn-default");
	} else {
		// Enter focus mode
		disableLinks();
		isFocused = true;
		document.getElementById("focus").textContent = "Remove Highlights";
		document.getElementById("focus").classList.add("active");
		document.getElementById("focus").classList.remove("btn-default");
		//document.getElementById("focus").classList.add("btn-primary");
	}
}

function disableLinks() {
	if (!svgContainer) return;
	
	// Block all node clicks to their URL destinations
	svgContainer.querySelectorAll("g.node a").forEach(link => {
		if (link.hasAttribute("xlink:href")) {
			originalLinks.set(link, link.getAttribute("xlink:href"));
			link.setAttribute("xlink:href", "javascript:void(0);");
		}
	});
}

function restoreLinks() {
	if (!svgContainer) return;
	
	// Restore original hrefs
	svgContainer.querySelectorAll("g.node a").forEach(link => {
		const originalHref = originalLinks.get(link);
		if (originalHref) {
			link.setAttribute("xlink:href", originalHref);
		}
	});
	
	// Clear stored links
	originalLinks.clear();
}

function highlightPathToNode(nodeId) {
	if (!svgContainer) return;
	
	// First reset all edges
	resetEdges();
	
	// Get the title content of the node to find its name
	const node = document.getElementById(nodeId);
	if (!node) return;
	
	const nodeTitle = node.querySelector("title");
	if (!nodeTitle) return;
	
	const targetNodeName = nodeTitle.textContent;
	
	// Track all nodes that are part of the path
	const visitedNodes = new Set([targetNodeName]);
	// Track all edges we\'ve processed to avoid duplicates
	const processedEdges = new Set();
	
	// Recursively find all nodes that lead to our target
	function findConnectedNodes(nodeName) {
		svgContainer.querySelectorAll("g.edge").forEach(edge => {
			// Skip edges we\'ve already processed
			if (processedEdges.has(edge.id)) return;
			
			const edgeTitle = edge.querySelector("title");
			if (!edgeTitle || !edgeTitle.textContent.includes("->")) return;
			
			const [sourceNode, destNode] = edgeTitle.textContent.split("->");
			
			// If this edge points to our node, highlight it regardless of whether we\'ve visited the source
			if (destNode.trim() === nodeName) {
				// Mark this edge as processed
				processedEdges.add(edge.id);
				
				// Add the source to our visited set
				const sourceNodeName = sourceNode.trim();
				visitedNodes.add(sourceNodeName);
				
				// Highlight this edge
				const edgePath = edge.querySelector("path");
				if (edgePath) {
					edgePath.style.stroke = "red";
					edgePath.style.strokeWidth = "3px";
				}
				
				// Highlight arrowhead
				const polygon = edge.querySelector("polygon");
				if (polygon) {
					polygon.style.fill = "red";
					polygon.style.stroke = "red";
				}
				
				// Highlight edge text (labels)
				const textElements = edge.querySelectorAll("text");
				textElements.forEach(text => {
					text.style.fill = "red";
					text.style.fontWeight = "bold";
				});
				
				// Recursively find nodes that lead to this source
				findConnectedNodes(sourceNodeName);
			}
		});
	}
	
	// Start the recursive search from our target node
	findConnectedNodes(targetNodeName);
}

}
