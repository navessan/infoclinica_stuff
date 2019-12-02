<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>


<title>doc parse</title>

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
	,info
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
	print "<th>ID</th>";
	print "<th>FIO</th>";
	print "<th>URL</th>";
	
	/* Retrieve each row as an associative array and display the results.*/
	foreach ($rows as $row)
	{
		echo '<tr>';
		//print_r($row);
		$doc_info=$row['INFO'];
		
		echo '<td>'.$row['ID'].'</td>';
		echo '<td>'.$row['FIO'].'</td>';
		echo '<td>'.$row['URL'].'</td>';
		echo '<td>docinfo length='.strlen($doc_info).'</td>';
		
		echo '<tr><td colspan="3">';
		
		if(strlen($doc_info)){
			echo $doc_info;
			echo '<br>';
			//update doc_info in db
			parse_doc_info($conn, $row['ID'], $doc_info);
		}
		//echo $doc_info;
		echo '</td></tr>';
		print "</tr> \n";
	}
	print '	</tbody>
	</table>';
}

function parse_doc_info($conn, $id, $doc_info){
	$classname='cats';
	
	//dirty hack for set charset
	$doc_info='<html><head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head><body>'
		.$doc_info;
	
	//echo $doc_info;
	
	$doc = new DOMDocument();
	libxml_use_internal_errors(true);
	$doc->loadHTML($doc_info); // loads your HTML
	$xpath = new DOMXPath($doc);
	// returns a list
	$nlist = $xpath->query("//div[@class='".$classname."']");

	if(!$nlist->length){
		if($debug)
			echo "class=$classname not found<br>";
		return '';
	}

	$parsed=array();
	
	foreach($nlist as $node){
		if(is_a($node,'DOMElement')){
			$text=trim($node->nodeValue);
			
			list($field, $value) = explode(':', $text);
			$field=trim($field);
			$value=trim($value);
			echo "$field:$value<br>";
			
			if($field=='Должность')
				$parsed['DOLGNOST']=$value;
			else if($field=='Образование')
				$parsed['OBRAZOVANIE']=$value;
			else if($field=='Специальность по диплому')
				$parsed['DIPLOM']=$value;
			else if($field=='Основная специализация')
				$parsed['OSN_SPEC']=$value;
			else if($field=='Стаж работы')
			$parsed['STAG']=$value;	
		}		
	}
	
	$nlist = $xpath->query("//p");
	
	if($nlist->length==2 &&
		is_a($nlist->item(0),'DOMElement') &&
		is_a($nlist->item(1),'DOMElement') &&
		trim($nlist->item(0)->nodeValue)=='Профессиональные и научные достижения:' 
		)
	{
		$parsed['DOSTIGENIA']=DOMinnerHTML($nlist->item(1));	
	}
	
	print_r($parsed);
	print "<br>\n";
}

?>
        </div>
    </td>
</table>
</div>
</body>
<!-- jpg not remove this line -->
</html>
