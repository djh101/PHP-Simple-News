<?php
class News {
	function News(&$db,$readonly=true,$author="",$authid=-1,$idmatch=false,$path_ajax=""){
		$error = "";
		$news = array('id'=>-1,'timestamp'=>0,'title'=>"",'body'=>"",'author'=>"");
		$path_ajax = preg_replace("/\\/$/","",$path_ajax)."/";
		try {
			/* THROW EXCEPTION IF SESSIONS ARE DISABLED */
			if(version_compare(phpversion(),"5.4.0",">") && session_status() != 2){
				throw new Exception("Sessions are not enabled or a session has not been started.");
			}
			
			$_SESSION['news_author'] = $author;
			$_SESSION['news_authid'] = $authid;
			$_SESSION['news_readonly'] = $readonly;
			$_SESSION['news_idmatch'] = $idmatch;
			
			$table = "news";
			/* CREATE NEWS TABLE */
			$db->exec("CREATE TABLE IF NOT EXISTS $table(id INT UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP, author VARCHAR(20), authid INT NOT NULL DEFAULT -1, title VARCHAR(100) NOT NULL, body VARCHAR(2000) NOT NULL) CHARACTER SET utf8 COLLATE utf8_general_ci");
			/* INSERT WELCOME POST */
			if($db->query("SELECT * FROM $table")->rowCount() == 0){
				$q = $db->prepare("INSERT IGNORE INTO $table (title,body,author,authid) VALUES (:title,:body,:author,:authid)");
				$q->bindValue(':title',"PHP Simple News");
				$q->bindValue(':body',"&lt;p&gt;You have just installed PHP Simple News. This is your first news post. If you are the administrator, you can edit this news post by clicking on it or by clicking &apos;edit&apos; or you can create a new news post by clicking &apos;new&apos;. Clicking &apos;delete&apos; will delete this post. After you are done editing, click save to publish your post. If no edit buttons are visible and you cannot edit this post, first check to make sure your browser supports contenteditable and then check to make sure your script is configured properly.&lt;p/&gt;");
				$q->bindValue(':author',"PHP Simple News");
				$q->bindValue(':authid',-1);
				$q->execute();
				unset($q);
			}

			$news = $db->query("SELECT * FROM $table ORDER BY timestamp DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
			$news['title'] = html_entity_decode($news['title']);
			$news['body'] = html_entity_decode($news['body']);
		} catch(Exception $e){
			$error = $e->getMessage();
		}
?>
		<script>
			var edit = "edit";
			var raw_edit = false;
			var news_id = "<?php echo $news['id']; ?>";
			var news_title;
			var news_body;
			var xmlhttp;
			
			function ajax(vars){
				if(window.XMLHttpRequest) xmlhttp=new XMLHttpRequest();
				else xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
				xmlhttp.onreadystatechange=function(){
					if(xmlhttp.readyState==4 && xmlhttp.status==200){
						var getvars = xmlhttp.responseXML;
						if(getvars.getElementsByTagName('error')[0] != null){
							document.getElementById('news_error').innerHTML = "Error: "+getvars.getElementsByTagName('error')[0].childNodes[0].nodeValue;
						} else {
							news_id = getvars.getElementsByTagName('id')[0].childNodes[0].nodeValue;
							news_title = encodeURIComponent(getvars.getElementsByTagName('title')[0].childNodes[0].nodeValue);
							news_body = encodeURIComponent(getvars.getElementsByTagName('body')[0].childNodes[0].nodeValue);
							document.getElementById('news_error').innerHTML = "";
							document.getElementById('news_title').innerHTML = getvars.getElementsByTagName('title')[0].childNodes[0].nodeValue;
							document.getElementById('news_body').innerHTML = getvars.getElementsByTagName('body')[0].childNodes[0].nodeValue;
							document.getElementById('news_author').firstChild.innerHTML = getvars.getElementsByTagName('author')[0].childNodes[0].nodeValue;
							getvars.getElementsByTagName('author')[0].childNodes[0].nodeValue == "" ? document.getElementById('news_author').style.display = "none" : document.getElementById('news_author').style.display = "block";
							document.getElementById('news_date').innerHTML = getvars.getElementsByTagName('date')[0].childNodes[0].nodeValue;
						}
					} else if(xmlhttp.status==404){
						document.getElementById('news_error').innerHTML = "Error: Cannot find news_ajax.php";
					}
				}
				xmlhttp.open("POST","<?php echo $path_ajax; ?>news_ajax.php",true);
				xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
				xmlhttp.send(vars);
			}
			/* CREATE NEW NEWS POST */
			function news_new(){
				document.getElementById('news_title').innerHTML = "";
				document.getElementById('news_body').innerHTML = "";
				document.getElementById('news_body_raw').value = "";
				document.getElementById('news_title').focus();
				edit = "new";
				return false;
			}
			/* EDIT CURRENT NEWS POST */
			function news_edit(){
				document.getElementById('news_title').innerHTML = decodeURIComponent(news_title);
				document.getElementById('news_body').innerHTML = decodeURIComponent(news_body);
				document.getElementById('news_body_raw').value = decodeURIComponent(news_body);
				document.getElementById('news_author').firstChild.innerHTML = news_author;
				if(raw_edit){
					document.getElementById('news_body').style.display = "none";
					document.getElementById('news_body_raw').style.display ="block";
					document.getElementById('news_body_raw').focus();
				} else {
					document.getElementById('news_body').focus();
				}
				edit = "edit";
				return false;
			}
			/* SAVE NEWS POST CURRENTLY BEING EDITED */
			function news_save(){
				news_title = encodeURIComponent(document.getElementById('news_title').innerHTML);
				news_body = !raw_edit ? encodeURIComponent(document.getElementById('news_body').innerHTML) : encodeURIComponent(document.getElementById('news_body_raw').value);
				
				ajax("id="+news_id+"&title="+news_title+"&body="+news_body+"&method="+edit);
				
				document.getElementById('news_title').blur();
				document.getElementById('news_body').blur();
				document.getElementById('news_body_raw').blur();
				if(raw_edit){
					document.getElementById('news_body').style.display == "block";
					document.getElementById('news_body_raw').style.display == "none";
				}
				return false;
			}
			/* DELETE CURRENT NEWS POST */
			function news_delete(){
				if(confirm("Delete this post?")){
					ajax("id="+news_id+"&method=delete");
				}
				return false;
			}
			/* SWITCH TO/FROM RAW EDITING MODE */
			function news_raw(){
				if(document.getElementById('news_body').style.display == "none"){
					raw_edit = false;
					document.getElementById('news_body').style.display = "block";
					document.getElementById('news_body').innerHTML = document.getElementById('news_body_raw').value;
					document.getElementById('news_raw').innerHTML = "Raw";
					document.getElementById('news_body').focus();
					document.getElementById('news_body_raw').style.display = "none";
				} else {
					raw_edit = true;
					document.getElementById('news_body_raw').style.display = "block";
					document.getElementById('news_body_raw').value = document.getElementById('news_body').innerHTML;
					document.getElementById('news_raw').innerHTML = "Visual";
					document.getElementById('news_body_raw').focus();
					document.getElementById('news_body').style.display = "none";
				}
				return false;
			}
			/* CHANGE BUTTONS ON FOCUS/BLUR */
			function news_focus(){
				if(document.getElementById('news_title') == document.activeElement || document.getElementById('news_body') == document.activeElement || document.getElementById('news_body_raw') == document.activeElement){
					document.getElementById('news_edit').style.display = "none";
					document.getElementById('news_save').style.display = "inline";
					document.getElementById('news_raw').style.display = "inline";
					if(raw_edit && document.getElementById('news_body_raw').style.display == "none"){
						document.getElementById('news_body_raw').style.display = "block";
						document.getElementById('news_body_raw').focus();
						document.getElementById('news_body').style.display = "none";
					}
				} else {
					document.getElementById('news_edit').style.display = "inline";
					document.getElementById('news_save').style.display = "none";
					document.getElementById('news_raw').style.display = "none";
					if(raw_edit && document.getElementById('news_body').style.display == "none"){
						document.getElementById('news_body').style.display = "block";
						document.getElementById('news_body').innerHTML = document.getElementById('news_body_raw').value;
						document.getElementById('news_body_raw').style.display = "none";
					}
				}
			}
		</script>
		<section id="news">
			<h1>News &amp; Announcements</h1>
			<div>
				<article>
					<div id="news_header">
						<!-- TITLE -->
						<span id="news_title"<?php if($readonly === false){ ?> contenteditable="true" onfocus="news_focus();" onblur="news_focus();"<?php } ?>><?php echo $news['title']; ?></span>
						<!-- DATE -->
						<span id="news_date"><?php echo date("F j, Y",strtotime($news['timestamp'])); ?></span>
					</div>
					<!-- BODY -->
					<div id="news_body"<?php if($readonly === false){ ?> contenteditable="true" onfocus="news_focus();" onblur="news_focus();"<?php } ?>><?php echo $news['body']; ?></div>
					<textarea id="news_body_raw" style="display: none;"<?php if($readonly === false){ ?>onfocus="news_focus();" onblur="news_focus();"<?php } else { ?>readonly<?php } ?>><p><?php echo $news['body']; ?></p></textarea>
					<div id="news_author" style="<?php if(empty($news['author'])) echo "display: none;"; ?>"><span><?php echo $news['author']; ?></span></div>
				</article>
				<!-- EDIT BUTTONS -->
				<?php if($readonly === false){ ?>
				<div id="news_error" style="display: none;"><?php echo $error; ?></div>
				<div id="news_links" style="display: none;">
					<a href="javascript:void(0)" onmousedown="return news_new();">New</a> 
					<a href="javascript:void(0)" onmousedown="return news_edit();" id="news_edit">Edit</a>
					<a href="javascript:void(0)" onmousedown="return news_save();" id="news_save" style="display: none;">Save</a>
					<a href="javascript:void(0)" onmousedown="return news_raw();" id="news_raw" style="display: none;">Raw</a>
					<a href="javascript:void(0)" onmousedown="return news_delete();">Delete</a> 
				</div>
				<!-- DISPLAY EDITING BUTTONS ONLY IF CONTENTEDITABLE IS SUPPORTED -->
				<script>
					if(document.createElement('div').contentEditable){
						document.getElementById('news_links').style.display = "block";
						document.getElementById('news_error').style.display = "block";
						news_title = document.getElementById('news_title').innerHTML;
						news_body = document.getElementById('news_body').innerHTML;
					}
				</script>
				<?php } ?>
			</div>
		</section>
<?php
	}
}
