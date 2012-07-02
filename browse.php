<!doctype html>
<?php
  include('config.php');
  include('functions.php');

  try {
    $db = new PDO('sqlite:stacks.sqlite');
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  // print a row of the table containing a chapter
  function print_chapter($chapter, $filename) {
?>
      <tr> 
        <td></td> 
        <td><?php print($chapter); ?></td> 
<?php
    if ($chapter == 'Auto generated index')
      print("        <td></td>\n");
    else
      print("        <td><a href=\"" . full_url('tex/' . $filename . '.pdf') . "\"><code>tex</code></a></td>\n");
?>
        <td><a href="<?php print(full_url('tex/' . $filename . '.pdf')); ?>"><code>pdf</code></a></td> 
        <td><a href="<?php print(full_url('tex/' . $filename . '.dvi')); ?>"><code>dvi</code></a></td> 
      </tr> 
<?php
  }

  // print a row of the table containing a part
  function print_part($part) {
?>
      <tr> 
        <td><?php print($part); ?></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
      </tr>
<?php
  }

  function print_table() {
?>
    <table id="browse"> 
      <tr> 
        <th>Part</th> 
        <th>Chapter</th> 
        <th>TeX</th> 
        <th>pdf</th> 
        <th>dvi</th> 
      </tr> 
<?php
    global $db;

    // mapping the first chapter of each part to the title of the part
    $parts = array('Introduction' => 'Preliminaries', 'Schemes' => 'Schemes', 'Algebraic Spaces' => 'Algebraic Spaces', 'Stacks' => 'Algebraic Stacks', 'Coding Style' => 'Miscellany');

    try {
      $sql = $db->prepare('SELECT number, title, filename FROM sections WHERE number NOT LIKE "%.%" ORDER BY CAST(number AS INTEGER)');
      if ($sql->execute()) {
        while ($row = $sql->fetch()) {
          // check wheter it's the first chapter, insert row with part if necessary
          if (array_key_exists($row['title'], $parts)) {
            print_part($parts[$row['title']]);
          }

          // change LaTeX escaping to HTML escaping
          $row['title'] = str_replace("\'E", "&Eacute;", $row['title']);
          print_chapter($row['title'], $row['filename']);
        }
      }
    }
    catch(PDOException $e) {
      echo $e->getMessage();
    }
?>
    </table>
<?php
  }

?>
<html>
  <head>
    <title>Stacks Project -- Browse</title>
    <link rel="stylesheet" type="text/css" href="<?php print(full_url('style.css')); ?>">
    <link rel="icon" type="image/vnd.microsoft.icon" href="<?php print(full_url('stacks.ico')); ?>"> 
  </head>

  <body>
    <h1><a href="<?php print(full_url('')); ?>">The Stacks Project</a></h1>

    <h2>Browse chapters</h2>
<?php
  print_table();
?>

    <p id="backlink">Back to the <a href="<?php print(full_url('')); ?>">main page</a>.
  </body>
</html>
