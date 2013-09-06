<?php
header('Content-type: text/xml');
session_start();

#define('DBHOSTNAME','localhost');
#define('DBUSERNAME','user');
#define('DBPASSWORD','password');
#define('DBNAME','database');
#$db = new PDO("mysql:dbname=".DBNAME."; host=".DBHOSTNAME, DBUSERNAME, DBPASSWORD);

$readonly = $_SESSION['news_readonly'] === false ? false : true;
?>
<data>
<?php
/* CHECK IF ATTEMPT IS MADE TO EDIT DATA */
if(isset($_POST['id']) && isset($_POST['method']) && ($_POST['method'] == "new" || $_POST['method'] == "edit" || $_POST['method'] == "delete")){
	/* CHECK IF USER IS AN ADMINISTRATOR */
	if($readonly === false){
		try {
			/* SET NEWS VARIABLES */
			$id = (int)preg_replace("/[^0-9]/","",$_POST['id']);
			
			$author = isset($_SESSION['news_author']) ? $_SESSION['news_author'] : "";
			$authid = isset($_SESSION['news_authid']) ? $_SESSION['news_authid'] : -1;
			$originalAuthid = $db->query("SELECT authid FROM news WHERE id='$id'")->fetch(PDO::FETCH_OBJ)->authid;
			$idmatch = $_SESSION['news_idmatch'] === true ? true : false;
			
			if($_POST['method'] != "delete"){
				/* SET POST DATA VARIABLES */
				$date = date("Y-m-d H:i:s",time());
				$title = isset($_POST['title']) ? $_POST['title'] : "";
				$body = isset($_POST['body']) ? $_POST['body'] : "";
				$title = preg_replace(array("%<.*>%"),array(""),$title); //REMOVE HTML FROM TITLE
				$body = preg_replace(array("%<div>%","%</div>%"),array("<p>","</p>"),$body); //IN CONTENTEDITABLE DIVS, LINEBREAKS ARE CONVERTED TO <DIV></DIV>. WE ARE GOING TO REPLACE THESE WITH PARAGRAPHS.
				if(!preg_match("%^<p>%",$body) && !preg_match("%</p>$%",$body)) $body = "<p>".$body."</p>"; //<P> TAGS ARE ADDED AT THE BEGINNING AND END OF THE NEWS BODY IF THEY DONT ALREADY EXIST (THIS PREVENTS DUPLICATION)
				$title = htmlentities($title);
				$body = htmlentities($body);
				
				if(empty($title) || empty($body)){
					throw new Exception("Title and body cannot be empty.");
				}
			}
			
			if($_POST['method'] == "new"){
				$q = $db->prepare("INSERT INTO news (timestamp,title,body,author,authid) VALUES (:timestamp,:title,:body,:author,:authid)");
				$q->execute(array(':timestamp'=>$date,':title'=>$title,':body'=>$body,':author'=>$author,':authid'=>$authid));
				unset($q);
			} else if($_POST['method'] == "edit"){
				if($idmatch === false || empty($originalAuthid) || $authid == $originalAuthid){
					$q = $db->prepare("UPDATE news SET title=:title,body=:body WHERE id=:id");
					$q->execute(array(':id'=>$id,':title'=>$title,':body'=>$body));
					unset($q);
				} else {
					throw new Exception("You do not have permission to edit this post.");
				}
			} else if($_POST['method'] == "delete"){
				if($idmatch === false || empty($originalAuthid) || $authid == $originalAuthid){
					$db->query("DELETE FROM news WHERE id='$id'");
				} else {
					throw new Exception("You do not have permission to delete this post.");
				}
			}
			
			/* RETRIEVE LATEST NEWS POST */
			$q = $db->query("SELECT * FROM news ORDER BY timestamp DESC LIMIT 1");
			if($q->rowCount() > 0) $news = $q->fetch(PDO::FETCH_ASSOC);
			else $news = array('id'=>-1,'timestamp'=>0,'title'=>"",'body'=>"",'author'=>"");
			echo '<id>'.$news['id'].'</id>'."\r\n";
			echo '<date>'.date('F j, Y',strtotime($news['timestamp'])).'</date>'."\r\n";
			echo '<title>'.$news['title'].'</title>'."\r\n";
			echo '<body>'.$news['body'].'</body>'."\r\n";
			echo '<author>'.$news['author'].'</author>'."\r\n";
			unset($q);
		} catch(Exception $e){
			echo '<error>'.$e->getMessage().'</error>'."\r\n";
		}
	} else {
		echo '<error>Administrative features are currently unavailable.</error>'."\r\n";
	}
} else {
	echo '<error>There was a problem with the request data.'.$pm.'</error>'."\r\n";
}
?>
</data>
