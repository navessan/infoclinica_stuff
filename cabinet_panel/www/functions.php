<?php

function db_connect($config){
	$type = $config['DB_TYPE'];
	$hostname = $config['DB_HOST'];
	$database = $config['DB_DATABASE'];
	$username = $config['DB_USERNAME'];
	$password = $config['DB_PASSWORD'];
	$charset = $config['DB_CHARSET'];
	$port = $config['DB_PORT'];
	
	if($type=="sqlsrv")
		$dsn = "$type:server=$hostname;database=$database";
	else if($type=="firebird")
		$dsn = "$type:dbname=$hostname/$port:$database;charset=$charset;";
	else 	
		$dsn = "$type:host=$hostname;database=$database;charset=$charset;";

	$opt = array(
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			//PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM
	);

	try {
		$conn = new PDO($dsn, $username, $password, $opt);
	}
	catch(PDOException $e) {
		die($e->getMessage());
	}
	return $conn;
}

//получаем номер кабинета или из запроса, или из базы по ip
function get_place($conn, $client){
	if (isset($_REQUEST['place'])){
		// номер кабинета указан в запросе
		$place=sanitize_search_string($_REQUEST['place']);
		return $place;
	}

	$client=sanitize_search_string($client);
	if(!strlen($client))
		return '';
	
	if(!$conn)
		return '';	
	//определяем номер кабинета по ip-адресу клиента
		
	$sql='select place from z_kiosk_adr
		where ipaddr=:client';
	$r=array('client'=>$client);
	
	try{
		$stmt = $conn->prepare($sql);
		$stmt -> execute($r);
		$rows=$stmt->fetchAll();
	}
	catch(PDOException $e) {
		echo "FATAL:". $e->getMessage()."\n";
		return '';
	}
	//print_r($rows);
	
	$numRows = count($rows);
	//echo "<p>$numRows Row" . ($numRows == 1 ? "" : "s") . " Returned </p>";

	if($numRows)
		$place=$rows[0]['PLACE'];
	else
		$place='';

	return $place;
}

function get_doc_planning($conn, $place){
	if(!$conn)
		return null;
	
	/* Set up and execute the query. */
	$sql="
	SELECT first 1
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
	  ,z.id
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
	CAST(WDATE AS DATE) = cast('Now' as date)
	and (lpad(ds.BEGHOUR,2,'0')||':'||lpad(ds.BEGMIN,2,'0'))<=current_time
	and (lpad(ds.ENDHOUR,2,'0')||':'||lpad(ds.ENDMIN,2,'0'))>=current_time
	";
	
	$place=sanitize_search_string($place);
	if (strlen(trim($place))>0){
		$sql.="and r.rnum like '$place' \n";
	}
	
	$sql.="order by wdate,r.rnum, beghour,begmin";
	//echo "sql=$sql";

	try{
		$stmt = $conn->query($sql);
		$rows = $stmt->fetchAll();
	}
	catch(PDOException $e) {
		echo "FATAL:". $e->getMessage()."\n";
		return null;
	}
	
	return $rows;
}

function db_update_doc_info($conn, $id, $doc_info){
	if(!$conn || !$id)
		return;
	
	//$doc_info=sanitize_search_string($doc_info);	//html tags here in db!
		
	$sql='update z_kiosk_doctors set
		info=:doc_info
		where id=:id
		';
	$r=array('doc_info'=>$doc_info
				,'id'=>$id
			);
	
	try{
		$stmt = $conn->prepare($sql);
		$stmt -> execute($r);
		//$rows=$stmt->fetchAll();
	}
	catch(PDOException $e) {
		echo "FATAL:". $e->getMessage()."\n";
	}
}

function scrap_url($url, $debug=false){
	if(!strlen($url))
		return '';
	
	//$classname='item cabinet__item';
	$classname='grid-row';
	$replace_domain='http://domain.ru/';
	
	$url=$replace_domain.$url;

	//$html = file_get_contents($url);
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $html = curl_exec($ch);
	
	if(!$html){
		echo curl_error ($ch);
	}
	
    curl_close($ch);
	
	if(!strlen($html))
		return '';
	
	$doc = new DOMDocument();
	libxml_use_internal_errors(true);
	$doc->loadHTML($html); // loads your HTML
	$xpath = new DOMXPath($doc);
	// returns a list
	$nlist = $xpath->query("//div[@class='".$classname."']");

	if(!count($nlist))
		return '';
		
	//print_r($nlist[0]->nodeValue);
	$node=$nlist[0];
	
	//-----------------
	//replace img src
	replace_img_src($node, $replace_domain);
	
	//result from other page
	$res=DOMinnerHTML($node);
	
	return $res;
}

function doc_scrap($conn, $doctor, $debug=false){
	if(!$doctor)
		return;
	
	$url=$doctor['URL'];
	$id=$doctor['ID'];
	
	if(!strlen($url))
		return null;

	//$classname='item cabinet__item';
	$classname='grid-row';
	$remote_domain='http://domain.ru/';
	
	$url=$remote_domain.$url;
	
	$html=load_url($url);
	
	$doc = new DOMDocument();
	libxml_use_internal_errors(true);
	$doc->loadHTML($html); // loads your HTML
	$xpath = new DOMXPath($doc);
	// returns a list
	$nlist = $xpath->query("//div[@class='".$classname."']");

	if(!count($nlist)){
		if($debug)
			echo "class=$classname not found<br>";
		return '';
	}
		
	//print_r($nlist[0]->nodeValue);
	$node=$nlist[0];
	
	if(!is_a($node,'DOMElement')){
		if($debug)
			echo "no DOMElement<br>";
		return '';
	}
	//-----------------
	//replace img src
	$images = $node->getElementsByTagName('img');

	$i=0;
	$local_prefix='img/';
	
	foreach ($images as $image) {
		$r_path=$image->getAttribute('src');
		//echo $r_path;
		
		$ext=pathinfo($r_path)['extension'];
		
		$l_path=$local_prefix.$id;		
		if($i==0)
			$l_path=$l_path.'.'.$ext;
		else
			$l_path=$l_path.'-'.$i.'.'.$ext;
		
		//saving file local
		if ($debug)
			echo "saving file $r_path to $l_path <br>";
		downloadUrlToFile($remote_domain.$r_path, $l_path);
		
		$image->setAttribute('src', $l_path);
		$i++;
	}
	
	$res=DOMinnerHTML($node);	
	return $res;	
}

function DOMinnerHTML(DOMNode $element) 
{ 
    $innerHTML = ""; 
    $children  = $element->childNodes;

    foreach ($children as $child) 
    { 
        $innerHTML .= $element->ownerDocument->saveHTML($child);
    }

    return $innerHTML; 
}

function load_url($url){
	//$html = file_get_contents($url);
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $data = curl_exec($ch);
	
	if(!$data){
		echo curl_error ($ch);
	}
	
    curl_close($ch);
	
	return $data;
}

function downloadUrlToFile($url, $outFileName)
{   
    if(is_file($url)) {
        copy($url, $outFileName); 
    } else {
		$fp=fopen($outFileName, 'w');
        $options = array(
          CURLOPT_FILE    => $fp,
          CURLOPT_TIMEOUT =>  30, // set this to 8 hours so we dont timeout on big files
          CURLOPT_URL     => $url
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        curl_close($ch);
		fclose($fp);
    }
}

function get_column_visibility($name, $default = 1)
{
	global $fields_array;
	
	$name=strtoupper($name);

	if (isset($fields_array[$name]['visible']))
		return $visible_flag=$fields_array[$name]['visible'];

	else
		return $default;
}
function get_column_username($name, $default = '')
{
	global $fields_array;

	if (isset($fields_array[$name]['name']))
		return $visible_flag=$fields_array[$name]['name'];

	else
		return $default;
}

function get_column_style($name, $default = '')
{
	global $fields_array;
	
	$name=strtoupper($name);

	if (isset($fields_array[$name]['html']))
		return $visible_flag=$fields_array[$name]['html'];

	else
		return $default;
}

/* sanitize_search_string - cleans up a search string submitted by the user to be passed
     to the database. NOTE: some of the code for this function came from the phpBB project.
   @arg $string - the original raw search string
   @returns - the sanitized search string */
function sanitize_search_string($string) {
	static $drop_char_match =   array('^', '$', '<', '>', '`', '\'', '"', '|', ',', '?', '~', '+', '[', ']', '{', '}', '#', ';', '!', '=');
	static $drop_char_replace = array(' ', ' ', ' ', ' ',  '',   '', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ');

	/* Replace line endings by a space */
	$string = preg_replace('/[\n\r]/is', ' ', $string);
	/* HTML entities like &nbsp; */
	$string = preg_replace('/\b&[a-z]+;\b/', ' ', $string);
	/* Remove URL's */
	$string = preg_replace('/\b[a-z0-9]+:\/\/[a-z0-9\.\-]+(\/[a-z0-9\?\.%_\-\+=&\/]+)?/', ' ', $string);

	/* Filter out strange characters like ^, $, &, change "it's" to "its" */
	for($i = 0; $i < count($drop_char_match); $i++) {
		$string =  str_replace($drop_char_match[$i], $drop_char_replace[$i], $string);
	}

	$string = str_replace('*', ' ', $string);

	return $string;
}

?>