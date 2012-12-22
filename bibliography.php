<?php
  header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<?php
  error_reporting(E_ALL);

  include('config.php');
  include('functions.php');

  // initialize the global database object
  try {
    $db = new PDO(get_database_location());
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  function print_key_value($key, $value) {
    switch ($key) {
      case "url":
        print("<tr><td><i>" . $key . "</i></td><td><a href='" . $value . "'>" . $value . "</a></td></tr>");
        break;

      default:
        print("<tr><td><i>" . $key . "</i></td><td>" . parse_value($value) . "</td></tr>");
    }
  }

  function print_full_item($item) {
    print("<table>");
    // print these keys in this order
    $keys = array('author', 'title', 'year', 'type');
    foreach ($keys as $key)
      print_key_value($key, $item[$key]);

    foreach ($item as $key => $value) {
      if (!in_array($key, $keys))
        print_key_value($key, $value);
    }
    print("</table>");
  }

  function print_item($name, $item) {
    print("<li>" . parse_value($item['author']) . ", <a href='" . full_url('bibliography/' . $name) . "'>" . parse_value($item['title']) . '</a>');
  }
?>
<html>
  <head>
<?php
  if (isset($_GET['name']))
    print("    <title>Stacks Project -- Bibliography item " . htmlentities($_GET['name']) . "</title>\n");
  else
    print("    <title>Stacks Project -- Tag lookup</title>\n");
?>
    <link rel="stylesheet" type="text/css" href="<?php print(full_url('style.css')); ?>">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="icon" type="image/vnd.microsoft.icon" href="<?php print(full_url('stacks.ico')); ?>"> 
    <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="<?php print(full_url('stacks.ico')); ?>"> 

    <script type="text/javascript" src="/MathJax/MathJax.js?config=default"></script>
    <script type="text/x-mathjax-config">
      MathJax.Hub.Config({
        extensions: ["tex2jax.js", "fp.js"],
        tex2jax: {inlineMath: [['$','$'], ['\\(','\\)']]},
        TeX: {extensions: ["xypic.js", "AMSmath.js", "AMSsymbols.js"], TagSide: "left"},
        "HTML-CSS": { scale: 85 }
      });
    </script>

    <meta charset="utf-8">
  </head>
  <body>
    <h1><a href="<?php print(full_url('')); ?>">The Stacks Project</a></h1>
    <?php print_navigation(); ?>

<?php
  if (isset($_GET['name'])) {
    if (bibliography_item_exists($_GET['name'])) {
      print("<h2>Bibliography item: <code>" . $_GET['name'] . "</code></h2>");

      $item = get_bibliography_item($_GET['name']);
      print_full_item($item);

    }
    else {
      print("<h2>Error</h2>");
      print("<p>The name of the bibliography you are looking for (i.e. <var>" . htmlentities($_GET['name']) . "</var>) does not exist. You can try the overview at the <a href='" . full_url('bibliography') . "'>bibliography page</a>\n");
    }
    print("<p>Go to the <a href='" . full_url('bibliography') . "'>bibliography overview</a>.</p>");
  }
  else {
    $items = get_bibliography_items();
    print("<h2>Bibliography</h2>");
    print("<ul>");
    foreach ($items as $name => $item) {
      print_item($name, $item);

      print("<!--\n");
      print_r($item);
      print("\n-->");
    }
    print("</ul>");
  }
?>

    <p id="backlink">Back to the <a href="<?php print(full_url('')); ?>">main page</a>.
  </body>
</html>

