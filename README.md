PHP-Simple-News
===============
http://parasolarchives.com/scripts/php_simple_news

Simple News is a simple PHP/Javascript/HTML5 application used to create and edit news posts. Simple News can be used immediately upon installation or can be styled and customized as desired. Simple News does not include login functionality and must be configured with a form of administrative authentication.

Setup (index.php is included as an example page):
1. Make sure you are connected to a mySQL database.
2. Include news.php in the page where you would like to display a news widget.
3. Create a new instance of News() where you would like your news widget to be placed (note, you can only have one widget on a page at a time).
4. Open news_ajax.php. You must manually create a PDO variable, $db, here to connect to your database.
6. Link the page to news.css.

Open the page containing your news widget. A new database table will be created and configured and your news widget should now be fully functional. If $readonly is set to false and your browser supports contenteditable, you should be able to edit the news post (or create a new one). Below is a list of attributes associated with the News class. The only required parameter is $db.

	PDO $db The database used by the poll system.
	boolean $readonly Determines whether edeting capabilities are enabled (false) or disabled (true). Default is true.
	string $author The name of the post author. Default is empty.
	int $authid The id of the post author (used for $idmatch checking). Default is -1 (post can be edited publicly).
	boolean $idmatch Determines whether a post can be modified by anyone (false) or only by the author who created it (true). Default is false.
	string $path_ajax Sets the path to the news_ajax.php file. Default is the same directory as the main page.


For support questions and feedback, visit http://parasolarchives.com/contact/
