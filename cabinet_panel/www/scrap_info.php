<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>


<title>rasp scrap</title>

<meta content="text/html; charset=utf-8" name="Content">
<meta http-equiv="Content-Type"
	content="text/html; charset=utf-8">
<meta name="keywords"
	content="web, database, gui">
<meta name="description"
	content="web database gui">

</head>
<body>
<div id="page-wrapper">
<table>
    <td class="outer">
        <div class="inner">
<!--<p style="text-align: center;"><img src="logo.png" alt="" /></p>-->

<?php

$debug=0;
/* display ALL errors */
error_reporting(E_ALL);

/* Include configuration */
include("config.php");
include_once("functions.php");

if (isset($_REQUEST['phpinfo']))
{
	phpinfo();
	die( "exit!" );
}
if (isset($_REQUEST['debug']))
{
	$debug=1;
}

//-------------------
$conn=db_connect($config);

//----------------------
{
	$sql="
	select first 100
	id
	,fio
	,url
	--,z.info
	from z_kiosk_doctors as z
	where char_length(url)>0  
	";

	$sql.="order by fio";
	
	$select_query=true;	
}

//echo "sql=$sql";
$numRows=0;

if(strlen($sql)){
	try{
		$stmt = $conn->prepare($sql);
		$affected_rows = $stmt -> execute();
		
		if($select_query)
			$rows = $stmt->fetchAll();
	}
	catch(PDOException $e) {
		echo "FATAL:". $e->getMessage()."\n";
	}

	if($select_query){
		$numRows = count($rows);
		echo "<p>$numRows Row" . ($numRows == 1 ? "" : "s") . " Returned </p>";		
	}else{
		echo "<p>$affected_rows Row" . ($numRows == 1 ? "" : "s") . " affected </p>";
	}
}

if($numRows>0)
{	
	print '<table cellspacing="0" cellpadding="1" border="1" align="center"
	width="100%" >
	<tbody>';
		
	$metadata=array();
	$i=0;
	// add the table headers
	foreach ($rows[0] as $key => $useless){
		print "<th>$key</th>";
	}
	
	/* Retrieve each row as an associative array and display the results.*/
	foreach ($rows as $row)
	{
		echo '<tr>';
		//print_r($row);
		
		foreach($row as $field)
		{
			echo '<td>';
			$text=$field;
			
			if($text=='')
				$text ='&nbsp';
			echo $text;
			echo '</td>';
		}
		echo '<tr><td colspan="3">';
		$doc_info = doc_scrap($conn, $row, true);
		if(strlen($doc_info)){
			//update doc_info in db
			db_update_doc_info($conn, $row['ID'], $doc_info);
		}
		//echo $doc_info;
		echo '</td></tr>';
		print "</tr> \n";
	}
	print '	</tbody>
	</table>';
}

?>
        </div>
    </td>
</table>
</div>
</body>
<!-- jpg not remove this line -->
</html>
