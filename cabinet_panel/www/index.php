<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Refresh" content="50" /> 
<script type="text/javascript">
function getDate()
{
    var date = new Date();
	
    document.getElementById('timedisplay1').innerHTML = 
	date.getFullYear() 
	+ '.' 
	+ ('0' + (date.getMonth() + 1)).slice(-2) 
	+ '.' 
	+ ('0' + date.getDate()).slice(-2)
	+ ' '
	+ ('0' + date.getHours()).slice(-2)
	+ ':'
	+ ('0' + date.getMinutes()).slice(-2)
	+ ':'
	+ ('0' + date.getSeconds()).slice(-2)
	;
}
setInterval(getDate, 990);
</script>

<style type="text/css">
body {
    background: url(logo.png); /* Фоновый рисунок */
   }
.outer {
    width: 1280px;
    height: 760px;
    background-color: rgba(255, 255, 255, 0.9);
    //text-align: center;
    //vertical-align: middle;

    //background-color: #ffc;
}

.inner {
    //display: inline-block;
    background-color: rgba(255, 255, 255, 0.5);
    //background-color: #fcc;
}

.demo {
    font-size: 3vw; /* 3% of viewport width */
}

h1 {
    font-size: 210%; /* Размер шрифта в процентах */ 
}
   
</style>
<link href="/nocss/styles.css" type="text/css"  data-template-style="true"  rel="stylesheet" />
<link href="/nocss/main.css" type="text/css"  data-template-style="true"  rel="stylesheet" />
<link href="css/test.css" type="text/css"  data-template-style="true"  rel="stylesheet" />

<title>rasp</title>

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

//-------------------
//получаем номер кабинета или из запроса, или из базы по ip
$client=$_SERVER['REMOTE_ADDR'];
$place=get_place($conn, $client);

if($debug)
	echo "client=$client place=$place<br>\n";

if (strlen(trim($place))==0)
{
	print
	"<table width=\"100%\" align=\"center\">
		<tr>
			<td>
				Place is not set for $client
			</td>
			<td>
				<h1 id=\"timedisplay\" style=\"text-align:right\"></h1>
			</td>			
		</tr>
	</table>";
	exit;
}
//-------------------
//получаем инфу о принимающем враче
$rows=get_doc_planning($conn, $place);

$numRows = count($rows);
//echo "<p>$numRows Row" . ($numRows == 1 ? "" : "s") . " Returned </p>";

if($numRows>0)
{	
	if($debug){
		print '<table cellspacing="0" cellpadding="1" border="1" align="center"
		width="100%" >
		<tbody>';
			
		// add the table headers
		foreach ($rows[0] as $key => $useless){
			print "<th>$key</th>";
		}
		/* Retrieve each row as an associative array and display the results.*/
		foreach ($rows as $row)
		{
			echo '<tr>';
			foreach($row as $field){
				$text=$field;
					
				if($text=='')
					$text ='&nbsp';
				
				echo '<td><h1>' . $text . '</h1></td>';

			}
			print "</tr> \n";
		}
		print '	</tbody>
		</table>';
	}
	
	$info=$rows[0];
	
/*	$info['DCODE'];
	$info['WDATE'];
	$info['BEGHOUR'];
	$info['BEGMIN'];
	$info['LPAD'];
	$info['ENDHOUR'];
	$info['ENDMIN'];
	$info['SHINTERV'];
	$info['DOC_NAME'];
	$info['CABINET'];
	$info['SPEC'];
	$info['ID'];
	$info['URL'];
	$info['INFO'];
	*/
	
	$dcode=$info['DCODE'];
	$place=$info['SPEC'];
	
	if(strlen($info['ENDHOUR'])>0 &&
		strlen($info['ENDMIN'])>0)
		$time_end_string='Окончание приема '.$info['ENDHOUR'].':'.$info['ENDMIN'];
}
else 
{
	if($debug)
		echo "No rows returned for $place.";
	$time_end_string='';
	print
	"<table width=\"100%\" align=\"center\">
		<tr>
			<td>
				<h1 style=\"text-align:left\">Кабинет $place</h1>
			</td>
			<td>
				<h1 id=\"timedisplay\" style=\"text-align:right\"></h1>
			</td>			
		</tr>
	</table>";	
}
	
//---------------
if($numRows>0)
{
	print
	"
	<table>
    <td class=\"outer\">
        <div class=\"inner\">
	<table width=\"100%\" border=\"1\">
		<tr>
			<td>
				<h1 style=\"text-align:left\">Кабинет $place</h1>
				<h2>$time_end_string</h2>
			</td>
			<td>
				<h1 id=\"timedisplay\" style=\"text-align:right\"></h1>
			</td>
		</tr>
	</table>
	";	
	
	$url=$info['URL'];
	$doc_info=$info['INFO'];
	if(strlen(trim($doc_info))){
		//thats ok, do nothing
		;
	}
	else if(strlen(trim($url))){
		//no info in database, will try to scrap from url
		//echo $url;echo '<br>';

		//$doc_info=scrap_url($url);		
		$doc_info=doc_scrap($conn, $info, false);
	}
	
	if(empty($info['INFO'])&& strlen($doc_info)){
		//update doc_info in db
		db_update_doc_info($conn, $info['ID'], $doc_info);
	}
	
	//окончательная инфа о враче
	if(strlen($doc_info))
		echo $doc_info;
	else
		print '<h1>'.$info['DOC_NAME'].'<h1>';		
}

?>
        </div>
    </td>
</table>
</div>
</body>
<!-- jpg not remove this line -->
</html>
