<!doctype html>
<?php
  include('config.php');
  include('functions.php');

  try {
    $db = new PDO(get_database_location());
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  function section_exists($number) {
    assert(is_numeric($number));

    global $db;
    try {
      $sql = $db->prepare('SELECT COUNT(*) FROM sections WHERE number = :number');
      $sql->bindParam(':number', $number);
  
      if ($sql->execute())
        return intval($sql->fetchColumn()) > 0;
    }
    catch(PDOException $e) {
      echo $e->getMessage();
    }
  
    return false;
  }
  
  function get_chapter($chapter_id) {
    assert(section_exists($number));

    global $db;
    try {
      $sql = $db->prepare('SELECT title FROM sections WHERE number = :number');
      $sql->bindParam(':number', $chapter_id);
  
      if ($sql->execute())
        return $sql->fetchColumn();
    }
    catch(PDOException $e) {
      echo $e->getMessage();
    }
  
    return '';
  }

  // get all active tags from a given chapter
  function get_tags($chapter_id) {
    global $db;
    $tags = array();

    try {
      $sql = $db->prepare("SELECT tag, label, book_id, type, book_page, file, name FROM tags WHERE active = 'TRUE' AND book_id LIKE '" . $chapter_id . ".%' ORDER BY position");
      
      if ($sql->execute()) {
        // add all rows to an array
        while ($row = $sql->fetch())
          $tags[] = $row;
      }
    }
    catch(PDOException $e) {
      echo $e->getMessage();
    }

    return $tags;
  }

  function print_tag($tag) {
    print("<li><a href='" . full_url('tag/' . $tag['tag']) . "'>Tag <var>" . $tag['tag'] . "</var></a> references " . ucfirst($tag['type']) . " " . $tag['book_id']);
    // in these cases we can print a name
    if (($tag['type'] == 'section' or $tag['type'] == 'subsection') or (!in_array($tag['type'], array('item', 'equation')) and !empty($tag['name'])))
      print(": " . $tag['name']);
  }

  function print_tags($chapter_id) {
    $tags = get_tags($chapter_id);

    // start global list
    print("<ul>");

    // keep track of how many <ul>'s where issued
    $depth = 0;
    // are we printing a list of equations?
    $equation_mode = false;
    // have we just issued a new sub(section)?
    $section_issued = false;
    
    foreach ($tags as $tag) {
      // we have finished a list of equations not directly contained in a (sub)section
      if ($tag['type'] != 'equation' and !$section_issued and $equation_mode) {
        $equation_mode = false;
        print("</ul>");
        $depth--;
      }

      // just issued a section, if an equation occurs immediately after this do not start a new list
      if ($tag['type'] == 'section' or $tag['type'] == 'subsection')
        $section_issued = true;

      switch ($tag['type']) {
        case 'section':
          // do not close the container <ul>
          print(str_repeat("</ul>\n", $depth - 1));
          $depth = 2;
          print_tag($tag);
          print("<ul>");
          break;

        case 'subsection':
          // subsections mustn't close the section, therefore -2
          print(str_repeat("</ul>\n", $depth - 2));
          $depth = 3;
          print_tag($tag);
          print("<ul>");
          break;

        case 'equation':
          // start new list because the equations belong to something like a Lemma
          if (!$section_issued and !$equation_mode) {
            print("<ul>");
            $depth++;
          }
          print_tag($tag);
          break;

        default:
          $section_issued = false;
          print_tag($tag);
          break;
      }

      // let it be known whether we just issued an equation or not
      $equation_mode = ($tag['type'] == 'equation');
    }

    // end all pending lists, 
    print(str_repeat("</ul>", $depth));
  }
?>
<html>
  <head>
    <title>Stacks Project -- Overview</title>
    <link rel="stylesheet" type="text/css" href="<?php print(full_url('style.css')); ?>">
    <link rel="icon" type="image/vnd.microsoft.icon" href="<?php print(full_url('stacks.ico')); ?>"> 
    <meta charset="utf-8">

    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
    <script type="text/javascript" src="<?php print(full_url('jquery-treeview/jquery.treeview.js')); ?>"></script>

    <link rel="stylesheet" href="<?php print(full_url('jquery-treeview/jquery.treeview.css')); ?>" />

    <style type="text/css">
      p#chapter-back,
      p#chapter-forward {
        margin: 0;
      }

      p#chapter-forward {
        text-align: right;
      }
    </style>
  </head>

  <body>
    <h1><a href="<?php print(full_url('')); ?>">The Stacks Project</a></h1>

    <div id="treeview">    
<?php
  if (isset($_GET['number']) and is_numeric($_GET['number'])) {
    if (section_exists($_GET['number'])) {
      print("<h2>Tree view for Chapter " . $_GET['number'] . ": " . get_chapter($_GET['number']) . "</h2>");
      if (section_exists(intval($_GET['number']) - 1))
        print("<p id='chapter-back'><a href='" . full_url('chapter/' . (intval($_GET['number']) - 1)) . "'>&lt;&lt; Chapter " . (intval($_GET['number']) - 1) . ": " . get_chapter(intval($_GET['number']) - 1) . "</a>");
      if (section_exists(intval($_GET['number']) + 1))
        print("<p id='chapter-forward'><a href='" . full_url('chapter/' . (intval($_GET['number']) + 1)) . "'>Chapter " . (intval($_GET['number']) + 1) . ": " . get_chapter(intval($_GET['number']) + 1) . " &gt;&gt;</a>");

      print_tags($_GET['number']);
    }
    else {
      print("<h2>Tree view for a non-existing chapter</h2>");
      print("<p>This chapter does not exist.</p>");
    }
  }
  else {
    print("<h2>Error</h2>");
    print("<p>The input that was provided (i.e. <code>" . htmlentities($_GET['number']) . "</code>) is not correct, it should be a positive integer.</p>");
  }
?>
    </div>
    
    <script type="text/javascript">
      $(document).ready(function() {
          // remove all empty lists
         $("#treeview ul").each(
            function() {
              var element = $(this);
              if (element.children().length == 0) {
                element.remove();
              }
            }
          ); 
          // initialize treeview
          $("#treeview").treeview( { collapsed: true, } )
      });
    </script>

    <p id="backlink">Back to the <a href="<?php print(full_url('')); ?>">main page</a>.
  </body>
</html>
