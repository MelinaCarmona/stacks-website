<?php

error_reporting(E_ALL);

// read configuration file
$config = parse_ini_file("config.ini");

// initialize the global database object
try {
  $database = new PDO("sqlite:" . $config["database"]);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

include("php/pages/index.php");

$page = new IndexPage($database, array());

?>
<!doctype html>
<html>
  <head>
    <title>Stacks Project<?php print $page->getTitle(); ?></title>
    <link rel='stylesheet' type='text/css' href='css/main.css'>
    <link rel='stylesheet' type='text/css' href='css/tag.css'>

    <link rel='icon' type='image/vnd.microsoft.icon' href='stacks.ico'> 
    <link rel='shortcut icon' type='image/vnd.microsoft.icon' href='stacks.ico'> 
    <meta charset='utf-8'>

    <?php print $page->getHead(); ?>
  </head>

  <body>
    <h1><a href='/'>The Stacks Project</a></h1>

    <ul id='menu'>
      <li><a href='#'>about</a>
      <li><a href='#'>tags explained</a>
      <li><a href='#'>tag lookup</a>
      <li><a href='#'>browse</a>
      <li><a href='#'>search</a>
      <li><a href='#'>bibliography</a>
      <li><a href='#'>recent comments</a>
      <li><a href='http://math.columbia.edu/~dejong/wordpress/'>blog</a>
    </ul>
    <br style='clear: both;'>

    <div id='main'>
      <?php print $page->getMain(); ?>
    </div>

    <div id='sidebar'>
      <?php print $page->getSidebar(); ?>
    </div>

    <br style='clear: both;'>

    <p id='backlink'>Back to the <a href='/'>main page</a>.
  </body>
</html>
