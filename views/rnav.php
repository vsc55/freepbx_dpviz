<?php
$options=options_gets();
$panzoom= isset($options[0]['panzoom']) ? $options[0]['panzoom'] : '1';
$destinationColumn= isset($options[0]['destination']) ? $options[0]['destination'] : '0';

?>
<div id="toolbar-all">
<h1>Inbound Routes</h1>
</div>
<table id="dpviz-side" data-escape="true" data-url="ajax.php?module=core&amp;command=getJSON&amp;jdata=allDID" data-cache="true" data-toolbar="#toolbar-all" data-toggle="table" data-search="true" data-show-columns="true"
    data-show-refresh="true" class="table" data-cookie="true" data-cookie-id-table="dpviz-rnav-side">
	<thead>
		<tr>			
			<th data-field="extension" data-formatter="bootnavvizFormatter" data-sortable="true" data-switchable="false"><?php echo _("DID / CID")?></th>
			<th data-field="description" data-sortable="true"><?php echo _("Description")?></th>
			<?php if ($destinationColumn==1) { ?>
      <th data-field="destination" data-formatter="DIDdestFormatter" data-sortable="true" data-visible="false"><?php echo _("Destination")?></th>
      <?php } ?>
		</tr>
	</thead>
</table>


<?php if ($destinationColumn==1) { ?>
<script type="text/javascript">
var destinations = <?php echo json_encode(FreePBX::Modules()->getDestinations())?>;

function DIDdestFormatter(value){
	if(value === null || value.length == 0){
		return _("No Destination");
	}else{
		if(typeof destinations[value] !== "undefined") {
			var prefix = destinations[value].name;
			if(typeof destinations[value].category !== "undefined"){
				prefix = destinations[value].category;
			}
			return prefix + ": " + destinations[value].description;
			
		} else {
			return value;
		}
	}
}
</script>
<?php } ?>

<script type="text/javascript">
$("#dpviz-side").on('click-row.bs.table', function(e, row, elem) {
	//e.preventDefault();
    var extension = decodeURIComponent(row['extension']);
    var cid = decodeURIComponent(row['cidnum']);
		var jump= '';
		var pan='<?php echo $panzoom; ?>';

    generateVisualization(extension, cid, '', pan);
});

function bootnavvizFormatter(value, row) {
    var extension = decodeURIComponent(row['extension']).trim() || "ANY";
    var cidnum = decodeURIComponent(row['cidnum']).trim();
    
    // Return only the extension if cidnum is empty
    return cidnum ? extension + ' / ' + cidnum : extension;
}


//load side bar if svgContainer is empty
document.addEventListener("DOMContentLoaded", function () {
    if (!svgContainer){
        // Wait for the element to exist before modifying it
        let checkExist = setInterval(function () {
            let navbar = document.getElementById("floating-nav-bar");
            if (navbar) {
                navbar.classList.add("show");
                clearInterval(checkExist);
            }
        }, 100); // Check every 100ms
    }
});
</script>
