<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>

<script type="text/javascript">
function getDate()
{
    var date = new Date();
	
    document.getElementById('timedisplay').innerHTML = 
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
 /*   width: 1280px;
    height: 720px;
*/    background-color: rgba(255, 255, 255, 0.9);
    text-align: center;
    vertical-align: middle;

    //background-color: #ffc;
}

.inner {
    display: inline-block;
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
<link href="nocss/styles.css" type="text/css"  data-template-style="true"  rel="stylesheet" />
<link href="nocss/main.css" type="text/css"  data-template-style="true"  rel="stylesheet" />
<link href="css/test.css" type="text/css"  data-template-style="true"  rel="stylesheet" />

<title>rasp edit</title>

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
	echo '<a href="?schedule">schedule</a><br><br>';
	echo '<a href="?doctors">doctors</a><br><br>';
	echo '<a href="?kabinets">kabinets</a><br><br>';
	echo '<a href="?rebuild_doctors">copy doctors from schedule</a><br><br>';
	echo '<a href="?rebuild_kabinets">copy kabinets</a><br><br>';
	echo '<a href="?scrap_docinfo">scrap_docinfo</a><br><br>';
	echo '<a href="?reload_page_all">reload page on all kiosks</a><br><br>';

print_r ($_REQUEST);
echo '<br>';
$sql='';	

if (isset($_REQUEST['schedule'])){
	$sql="SELECT first 50
	 ds.DCODE
	--  ,ds.CHAIR
	  ,CAST(WDATE AS DATE) as wdate
	  ,BEGHOUR
	  ,lpad(BEGMIN,2,'0') as BEGMIN
	  ,ENDHOUR
	  ,lpad(ENDMIN,2,'0') as ENDMIN
	  ,ds.SHINTERV
	--  ,ds.FILIAL
	  ,d.fullname DOC_NAME
	  ,r.rnum CABINET
	  ,r.rname SPEC
	--  ,rdesc.rdtext
	  ,z.url
	  ,z.info	  
	FROM DOCTSHEDULE as ds
	join doctor d on ds.dcode=d.dcode
	join chairs ch on ds.chair =ch.chid
	left join rooms r on r.rid = ch.rid
	INNER JOIN FILIALS on FILIALS.FILID = Ds.FILIAL
	-- left join recdescription as rdesc on rdesc.recid = d.dcode and rdesc.rectype = 93
	left join z_kiosk_doctors as z on (z.dcode=d.dcode or z.fio=d.fullname)
	WHERE
	CAST(WDATE AS DATE) =cast('Now' as date)
	";
	
	$place='';
	if (isset($_REQUEST['place'])){
		// номер кабинета указан в запросе
		$place=sanitize_search_string($_REQUEST['place']);
	}
	
	if (strlen(trim($place))>0){
		$sql.="and r.rnum like '$place' \n";
	}
	$sql.="order by wdate,r.rnum, beghour,begmin";
	
	$select_query=true;
}
//-----------------------------
else if (isset($_REQUEST['doctors'])){
	$fields_array=array(
		'DOC_NAME'=>array(
				"name"=>'Врач'
				,"type"=>"text"
				,"html"=>""
				,"visible"=>1),
		'URL'=>array(
				"name"=>''
				,"type"=>"text"
				,"html"=>""
				,"visible"=>1
				,"form"=>"edit"
				,"row_id"=>"ID"),				
		'INFO'=>array(
				"name"=>''
				,"type"=>"text"
				,"html"=>" style=\"font-size: smaller\""
				,"visible"=>1)
	);

	$sql="
	select
		d.dcode
	,d.fullname DOC_NAME
	,z.id
	,z.fio
	,z.url
	,z.info
	from z_kiosk_doctors as z
	left join doctor d on (z.dcode=d.dcode or z.fio=d.fullname)  
	";

	$sql.="order by doc_name";
	
	$select_query=true;	
}
//-----------------------------
else if (isset($_REQUEST['kabinets'])||
		 isset($_REQUEST['reload_page_all'])){
	$fields_array=array(
		'ID'=>array(
				"name"=>''
				,"type"=>"int"
				,"html"=>""
				,"visible"=>0),
		'PLACE'=>array(
				"name"=>'кабинет'
				,"type"=>"text"
				,"html"=>""
				,"visible"=>1),
		'IPADDR'=>array(
				"name"=>'IP'
				,"type"=>"text"
				,"html"=>""
				,"visible"=>1
				,"form"=>"edit"				
				,"row_id"=>"ID"),
		'RNAME'=>array(
				"name"=>'В инфоклинике'
				,"type"=>"text"
				,"html"=>" style=\"font-size: smaller\""
				,"visible"=>1)
	);

	$sql="
	select
	r.rname
	,z.id
	,z.place
	,z.ipaddr
	from z_kiosk_adr as z
	left join rooms r on r.rnum = z.place
	";

	$sql.="order by place";
	
	$select_query=true;
}
//-----------------------------
else if (isset($_REQUEST['rebuild_doctors'])){
	$sql="
	insert into z_kiosk_doctors (dcode,fio)
	SELECT distinct
     d.DCODE
	,d.fullname DOC_NAME
    FROM DOCTSHEDULE as ds
    join doctor d on ds.dcode=d.dcode
    join chairs ch on ds.chair =ch.chid
    left join rooms r on r.rid = ch.rid
    INNER JOIN FILIALS on FILIALS.FILID = Ds.FILIAL
    WHERE
    CAST(WDATE AS DATE) >'Now'
    and d.dcode not in (select dcode from z_kiosk_doctors)
	and d.fullname not in (select fio from z_kiosk_doctors)
    order by d.fullname
	";
	
	$select_query=false;
}
//-----------------------------
else if (isset($_REQUEST['rebuild_kabinets'])){
	$sql="
	insert into z_kiosk_adr (place)
	SELECT distinct
      r.rnum CABINET
    FROM DOCTSHEDULE as ds
    join doctor d on ds.dcode=d.dcode
    join chairs ch on ds.chair =ch.chid
    left join rooms r on r.rid = ch.rid
    INNER JOIN FILIALS on FILIALS.FILID = Ds.FILIAL
    WHERE
    CAST(WDATE AS DATE) >'Now'
    and r.rnum not in (select place from z_kiosk_adr)
    order by r.rnum
	";
	
	$select_query=false;
}
//-----------------------------
else if (isset($_REQUEST['editplace']) ||
		 isset($_REQUEST['newplace']) ){			
	echo $_REQUEST['ID'];
	echo '<br>';
	echo $_REQUEST['PLACE'];
	echo '<br>';
	echo $_REQUEST['IPADDR'];
	echo '<br>';
	
	$id=sanitize_search_string($_REQUEST['ID']);	
	$place=sanitize_search_string($_REQUEST['PLACE']);
	$ipaddr=sanitize_search_string($_REQUEST['IPADDR']);
	
	if(isset($_REQUEST['editplace'])){
		if(!strlen(trim($id))){
			echo "id is not set";
			return;
		}
		$sql='update z_kiosk_adr set
			ipaddr=:ipaddr
			where id=:id
			';
		$r=array('id'=>$id
			 ,'ipaddr'=>$ipaddr
		);
	}
	else if(isset($_REQUEST['newplace'])){
		if(!strlen(trim($place))){
			echo "place is not set";
			return;
		}
		$sql='insert into z_kiosk_adr (ipaddr,place) 
			values (:ipaddr, :place)
			';
		$r=array('ipaddr'=>$ipaddr
			 ,'place'=>$place
		);
	}
	
	//echo "sql=$sql";
	
	try{
		$stmt = $conn->prepare($sql);
		$stmt -> execute($r);
		//$rows=$stmt->fetchAll();
	}
	catch(PDOException $e) {
		echo "FATAL:". $e->getMessage()."\n";
	}
	
	return;
}
//-----------------------------
else if (isset($_REQUEST['editplace11']) ||
		 isset($_REQUEST['newplace11']) ){			
	echo $_REQUEST['ID'];
	echo '<br>';
	echo $_REQUEST['PLACE'];
	echo '<br>';
	echo $_REQUEST['IPADDR'];
	echo '<br>';
	
	$id=sanitize_search_string($_REQUEST['ID']);	
	$place=sanitize_search_string($_REQUEST['PLACE']);
	$ipaddr=sanitize_search_string($_REQUEST['IPADDR']);
	
	if(isset($_REQUEST['editplace'])){
		if(!strlen(trim($id))){
			echo "id is not set";
			return;
		}
		$sql='update z_kiosk_adr set
			ipaddr=:ipaddr
			where id=:id
			';
		$r=array('id'=>$id
			 ,'ipaddr'=>$ipaddr
		);
	}
	else if(isset($_REQUEST['newplace'])){
		if(!strlen(trim($place))){
			echo "place is not set";
			return;
		}
		$sql='insert into z_kiosk_adr (ipaddr,place) 
			values (:ipaddr, :place)
			';
		$r=array('ipaddr'=>$ipaddr
			 ,'place'=>$place
		);
	}
	
	//echo "sql=$sql";
	
	try{
		$stmt = $conn->prepare($sql);
		$stmt -> execute($r);
		//$rows=$stmt->fetchAll();
	}
	catch(PDOException $e) {
		echo "FATAL:". $e->getMessage()."\n";
	}
	
	return;
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

if (isset($_REQUEST['kabinets'])){
	//TODO refactor here
	echo '<form action="?newplace" method="post">';
	echo "new place: <input type=\"text\" name=\"PLACE\" value=\"\">";					
	echo "ipaddr: <input type=\"text\" name=\"IPADDR\" value=\"\">";					
	echo '<input type="submit" value="insert" class="btn">';
	echo '</form>';
}

if($numRows>0)
{
	if (isset($_REQUEST['kabinets']))
		$tabletype="kabinets";
	else if (isset($_REQUEST['doctors']))
		$tabletype="doctors";
	else 
		$tabletype="";
	
	if(strlen($tabletype))
		process_table($rows, $tabletype, $fields_array);
	
	if(isset($_REQUEST['reload_page_all']))
		reload_kiosk_all($rows);
}

if (isset($_REQUEST['kabinets'])){
	//TODO refactor here
	echo '<form action="?newplace" method="post">';
	echo "new place: <input type=\"text\" name=\"PLACE\" value=\"\">";					
	echo "ipaddr: <input type=\"text\" name=\"IPADDR\" value=\"\">";					
	echo '<input type="submit" value="insert" class="btn">';
	echo '</form>';
}

function process_table($rows, $tabletype, $fields_array){
	print '<table cellspacing="0" cellpadding="1" border="1" align="center"
	width="100%" >
	<tbody>';
		
	$metadata=array();
	$i=0;
	// add the table headers
	foreach ($rows[0] as $key => $useless){
		//print "<th>$key</th>";
		$metadata[$i]['Name']=$key;
		$i++;
	}
	
	//print_r($metadata);
	$column_name="";

	//internal column names
	echo '<tr>';
	for ($i=0;$i < count($metadata);$i++)
	{
		$meta = $metadata[$i];
		//print_r($meta);
		$column_name=strtoupper($meta['Name']);
		
		if(get_column_visibility($column_name)==1)
			echo '<td>' . $meta['Name'] . '</td>';
	}
	echo '</tr>';

	//human readable column names
	echo '<tr>';
	for ($i=0;$i < count($metadata);$i++)
	{
		$meta = $metadata[$i];
		$column_name=strtoupper($meta['Name']);
		//print_r($meta);
		$header=get_column_username($column_name,"&nbsp");
		
		if(get_column_visibility($column_name)==1)
			echo '<td'.get_column_style($column_name).'><h3>' . $header . '</h3></td>';
	}
	echo '</tr>';


	/* Retrieve each row as an associative array and display the results.*/
	foreach ($rows as $row)
	{
		$rowColor='White';
		echo '<tr>';
		//echo '<tr>';
		
		//print_r($row);
		
		for ($i=0;$i < count($row);$i++)
		{
			$column_name=$metadata[$i]['Name'];
					
			if(get_column_visibility($column_name)==1)
			{					
				$field=$row[$column_name];
				$text='';
					
				if (gettype($field)=="object" && (get_class($field)=="DateTime"))
				{
					$text = $field->format('Y-m-d');
					if($text=='1899-12-30')
						$text="&nbsp";
				}
				else
					$text = trim($field);

				echo '<td'.get_column_style($column_name).'>';
				
				if (isset($fields_array[$column_name]['form'])&&
						$fields_array[$column_name]['form']=="edit"){
							
					$column_id=$fields_array[$column_name]['row_id'];
					$id=$row[$column_id];
					
					if ($tabletype=='kabinets')
						echo '<form action="?editplace" method="post">';
					else if ($tabletype=='doctors')
						echo '<form action="?editdoctor" method="post">';
					
					echo "<input type=\"hidden\" name=\"$column_id\" value=\"$id\" />";
					echo "<input type=\"text\" name=\"$column_name\" value=\"$text\">";					
					echo '<input type="submit" value="update" class="btn">';
					echo '</form>';
					
				}else{
					if($text=='')
						$text ='&nbsp';
					echo $text;
				}
				echo '</td>';
			}
		}
		print "</a></tr> \n";
	}
	print '	</tbody>
	</table>';
}

function reload_kiosk($host){
	//echo ":APPURL http://kiosk/rasp2" | nc -u 192.168.7.46 41234
	
	$url="http://kiosk/rasp2";
	$msg=":APPURL ".$url."\n";
	$port=41234;
	
	$len = strlen($msg);
	
	$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	if ($socket === FALSE) {
		echo ("Unable to create socket<br>\n");
		return;
	}
	
    $res=socket_sendto($socket, $msg, $len, 0, $host, $port);
	if ($res === FALSE) {
		echo ("Unable to establish session with port $port on $host<br>\n");
	}
    socket_close($socket);
	if($res)
		return 1;
	else
		return 0;
}

function reload_kiosk_all($rows){
	$cnt=0;
	foreach ($rows as $row){
		$ip=$row['IPADDR'];
		echo $ip."<br>";
		if (filter_var($ip, FILTER_VALIDATE_IP)) {
			$cnt+=reload_kiosk($ip);
		} else {
			echo("$ip is not a valid IP address"."<br>\n");
		}
	}
	echo "sended $cnt <br>\n";
}

?>
        </div>
    </td>
</table>
</div>
</body>
<!-- jpg not remove this line -->
</html>
