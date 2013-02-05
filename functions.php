<?php

function is_valid_tag($tag) {
  return (bool) preg_match_all('/^[[:alnum:]]{4}$/', $tag, $matches) == 1;
}

function tag_exists($tag) {
  assert(is_valid_tag($tag));

  global $db;
  try {
    $sql = $db->prepare('SELECT COUNT(*) FROM tags WHERE tag = :tag');
    $sql->bindParam(':tag', $tag);

    if ($sql->execute())
      return intval($sql->fetchColumn()) > 0;
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  return false;
}

function label_exists($label) {
  global $db;
  try {
    $sql = $db->prepare('SELECT COUNT(*) FROM tags WHERE label = :label');
    $sql->bindParam(':label', $label);

    if ($sql->execute())
      return intval($sql->fetchColumn()) > 0;
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  return false;
}

function position_exists($position) {
  global $db;
  try {
    $sql = $db->prepare('SELECT COUNT(*) FROM tags WHERE position = :position AND active = "TRUE"');
    $sql->bindParam(':position', $position);

    if ($sql->execute())
      return intval($sql->fetchColumn()) > 0;
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  return false;
}

function bibliography_item_exists($name) {
  global $db;
  try {
    $sql = $db->prepare('SELECT COUNT(*) FROM bibliography_items WHERE name = :name');
    $sql->bindParam(':name', $name);

    if ($sql->execute())
      return intval($sql->fetchColumn()) > 0;
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  return false;
}

function tag_is_active($tag) {
  assert(is_valid_tag($tag));

  global $db;
  try {
    $sql = $db->prepare('SELECT active FROM tags WHERE tag = :tag');
    $sql->bindParam(':tag', $tag);

    if ($sql->execute())
      return $sql->fetchColumn() == 'TRUE';
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  return false;
}

function get_label($tag) {
  assert(is_valid_tag($tag));

  global $db;
  try {
    $sql = $db->prepare('SELECT label FROM tags WHERE tag = :tag');
    $sql->bindParam(':tag', $tag);

    if ($sql->execute())
      return $sql->fetchColumn();
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  return "";
}

function get_id($tag) {
  assert(is_valid_tag($tag));

  global $db;
  try {
    $sql = $db->prepare('SELECT book_id FROM tags WHERE tag = :tag');
    $sql->bindParam(':tag', $tag);

    if ($sql->execute())
      return $sql->fetchColumn();
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  return "";
}

function get_tag($tag) {
  assert(is_valid_tag($tag));

  global $db;
  try {
    $sql = $db->prepare('SELECT tag, label, file, chapter_page, book_page, book_id, value, name, type, position FROM tags WHERE tag = :tag');
    $sql->bindParam(':tag', $tag);

    if ($sql->execute()) {
      // return first (= only) row of the result
      while ($row = $sql->fetch()) return $row;
    }
    return null;
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }
}

function get_bibliography_item($name) {
  assert(bibliography_item_exists($name));

  global $db;
  try {
    $sql = $db->prepare('SELECT bibliography_items.type, bibliography_values.key, bibliography_values.value FROM bibliography_items, bibliography_values WHERE bibliography_items.name = :name AND bibliography_items.name = bibliography_values.name');
    $sql->bindParam(':name', $name);

    if ($sql->execute()) {
      $rows = $sql->fetchAll();

      // this output is a mess, sanitize it
      $result = array();
      foreach ($rows as $row) {
        $result['type'] = $row['type'];
        $result[$row['key']] = $row['value'];
      }

      return $result;
    }
    return null;
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }
}

function get_bibliography_items() {
  global $db;
  try {
    $sql = $db->prepare('SELECT bibliography_items.name, bibliography_items.type, bibliography_values.key, bibliography_values.value FROM bibliography_items, bibliography_values WHERE bibliography_items.name = bibliography_values.name');

    if ($sql->execute()) {
      $rows = $sql->fetchAll();

      // this output is a mess, sanitize it
      $result = array();
      foreach ($rows as $row) {
        $result[$row['name']]['type'] = $row['type'];
        $result[$row['name']][$row['key']] = $row['value'];
      }

      return $result;
    }
    return null;
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }
}

function get_position_with_id($id) {
  global $db;
  try {
    $sql = $db->prepare('SELECT position FROM tags WHERE book_id = :id AND active = "TRUE"');
    $sql->bindParam(':id', $id);

    if ($sql->execute()) {
      $row = $sql->fetch();
      return $row['position'];
    }
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  return "ZZZZ";
}

function get_tag_at($position) {
  assert(position_exists($position));

  global $db;
  try {
    $sql = $db->prepare('SELECT tag, label FROM tags WHERE position = :position AND active = "TRUE"');
    $sql->bindParam(':position', $position);

    if ($sql->execute())
      return $sql->fetch();
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  return "ZZZZ";
}

function get_tag_with_id($id) {
  global $db;
  try {
    $sql = $db->prepare('SELECT tag FROM tags WHERE book_id = :id AND active = "TRUE"');
    $sql->bindParam(':id', $id);

    if ($sql->execute())
      return $sql->fetch();
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  return "ZZZZ";
}

function get_tag_referring_to($label) {
  assert(label_exists($label));

  global $db;
  try {
    $sql = $db->prepare('SELECT tag FROM tags WHERE label = :label AND active = "TRUE"');
    $sql->bindParam(':label', $label);

    if ($sql->execute())
      return $sql->fetchColumn();
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  return "ZZZZ";
}

function get_id_referring_to($label) {
  assert(label_exists($label));

  global $db;
  try {
    $sql = $db->prepare('SELECT book_id FROM tags WHERE label = :label AND active = "TRUE"');
    $sql->bindParam(':label', $label);

    if ($sql->execute())
      return $sql->fetchColumn();
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  return "ZZZZ";
}

function latex_to_html($text) {
  $text = preg_replace("/\{?\\\'\{?a\}?/", "&aacute;", $text);
  $text = preg_replace("/\{?\\\`\{?a\}?/", "&agrave;", $text);
  $text = preg_replace("/\{?\\\\\"\{?a\}?/", "&auml;", $text);
  $text = preg_replace("/\{\\\\u\{a\}\}?/", "&#259;", $text);
  $text = preg_replace("/\{\\\\v\{a\}\}?/", "&#462;", $text);

  $text = preg_replace("/\{?\\\c\{?c\}?\}?/", '&ccedil;', $text);

  $text = preg_replace("/\{?\\\'\{?E\}?/", "&Eacute;", $text);
  $text = preg_replace("/\{\\\'e\}|\\\'e?/", "&eacute;", $text);
  $text = preg_replace("/\{?\\\`\{?e\}?/", "&egrave;", $text);
  $text = preg_replace("/\{?\\\`\{?E\}?/", "&Egrave;", $text);
  $text = preg_replace("/\{?\\\\\^\{?e\}?/", "&ecirc;", $text);
  $text = preg_replace("/\{?\\\\\"\{?e\}?/", "&euml;", $text);

  $text = preg_replace("/\{?\\\c\{?t\}?\}?/", '&tcedil;', $text);

  $text = preg_replace("/\{?\\\\\"\{?o\}?/", "&ouml;", $text);

  $text = preg_replace("/\{?\\\\\"\{?u\}?/", "&uuml;", $text);
  $text = preg_replace("/\{?\\\`\{?u\}?/", "&ugrave;", $text);

  $text = str_replace("{\\v C}", "&#268;", $text);
  $text = str_replace("\\u C", "&#268;", $text);
  $text = str_replace("``", "\"", $text);
  $text = str_replace("''", "\"", $text);
  $text = str_replace("\\ ", "&nbsp;", $text);
  // this is to remedy a bug in import_titles
  $text = str_replace("\\v C}", "&#268;", $text);

  return $text;
}

// call latex_to_html first and then do some bibliography-specific parsing (url's, removing {} around math etc.)
function parse_value($value) {
  $value = latex_to_html($value);

  $value = preg_replace("/\\\url\{(.*)\}/", '<a href="$1">$1</a>', $value);
  $value = preg_replace("/\{\\\itshape(.*)\}/", '$1', $value);
  $value = str_replace("\\bf ", '', $value);

  $parts = explode('$', $value);
  for ($i = 0; $i < count($parts); $i++) {
    // not in math mode, i.e. remove all {}
    if ($i % 2 == 0) {
      $parts[$i] = str_replace('{', '', $parts[$i]);
      $parts[$i] = str_replace('}', '', $parts[$i]);
    }
  }
  $value = implode('$', $parts);

  $value = str_replace("--", "&ndash;", $value);

  return $value;
}

function parse_preview($preview) {
  // remove irrelevant new lines at the end
  $preview = trim($preview);
  // escape stuff
  $preview = htmlentities($preview);

  // but links should work: tag links are made up from alphanumeric characters, slashes, dashes and underscores, while the LaTeX label contains only alphanumeric characters and dashes
  $preview = preg_replace('/&lt;a href=&quot;([A-Za-z0-9\/\-]+)&quot;&gt;([A-Za-z0-9\-]+)&lt;\/a&gt;/', '<a href="' . full_url('') . '$1">$2</a>', $preview);

  return $preview;
}

function parse_latex($tag, $file, $code) {
  // get rid of things that should be HTML
  $code = parse_preview($code);

  // this is the regex for all (sufficiently nice) text that can occur in things like \emph
  $regex = "[\p{L}\p{Nd}@\s$,.:()'&#;\-\\\\$]+";

  // fix special characters (&quot; should be " for \"e)
  $code = latex_to_html(str_replace("&quot;", "\"", $code));

  // all big environments with their corresponding markup
  $environments = array(
    "lemma" => array("Lemma", true),
    "definition" => array("Definition", false),
    "remark" => array("Remark", false),
    "remarks" => array("Remarks", false),
    "example" => array("Example", false),
    "theorem" => array("Theorem", true),
    "exercise" => array("Exercise", false),
    "situation" => array("Situation", false),
    "proposition" => array("Proposition", true)
  );

  foreach ($environments as $environment => $information) {
    $count = preg_match_all("/\\\begin\{" . $environment . "\}\n\\\label\{([\w\-]*)\}/", $code, $matches);
    for ($i = 0; $i < $count; $i++) {
      $label = $file . '-' . $matches[1][$i];
      
      // check whether the label exists in the database, if not we cannot supply either a link or a number unfortunately
      if (label_exists($label))
        $code = str_replace($matches[0][$i], "<strong><a class='environment-link' href='" . get_tag_referring_to($label) . "'>" . $information[0] . " " . get_id_referring_to($label) . ".</a></strong>" . ($information[1] ? '<em>' : ''), $code);
      else
        $code = str_replace($matches[0][$i], "<strong>" . $information[0] . ".</strong>" . ($information[1] ? '<em>' : ''), $code);
    }

    $count = preg_match_all("/\\\begin\{" . $environment . "\}\[(" . $regex . ")\]\n\\\label\{([\w\-]*)\}/u", $code, $matches);
    for ($i = 0; $i < $count; $i++) {
      $label = $file . '-' . $matches[2][$i];
      
      // check whether the label exists in the database, if not we cannot supply either a link or a number unfortunately
      if (label_exists($label))
        $code = str_replace($matches[0][$i], "<a class='environment-link' href='" . get_tag_referring_to($label) . "'><strong>" . $information[0] . " " . get_id_referring_to($label) . "</strong> (" . $matches[1][$i] . ")<strong>.</strong></a>" . ($information[1] ? '<em>' : ''), $code);
      else
        $code = str_replace($matches[0][$i], "<strong>" . $information[0] . "</strong> (" . $matches[1][$i] . ")<strong>.</strong></a>" . ($information[1] ? '<em>' : ''), $code);
    }

    $code = str_replace("\\end{" . $environment . "}", ($information[1] ? '</em>' : '') . "</p>", $code);
  }

  $count = preg_match_all("/\\\begin\{equation\}\n\\\label\{([\w\-]+)\}\n/", $code, $matches);
  for ($i = 0; $i < $count; $i++) {
    $label = $file . '-' . $matches[1][$i];

    // check whether the label exists in the database, if not we cannot supply an equation number unfortunately
    if (label_exists($label))
      $code = str_replace($matches[0][$i], "\\begin{equation}\n\\tag{" . get_id_referring_to($label) . "}\n", $code);
    else
      $code = str_replace($matches[0][$i], "\\begin{equation}\n", $code);
  }

  // sections etc.
  $count = preg_match_all("/\\\section\{(" . $regex . ")\}\n\\\label\{([\w\-]+)\}/u", $code, $matches);
  for ($i = 0; $i < $count; $i++) {
    $label = $file . '-' . $matches[2][$i];

    // check whether the label exists in the database, if not we cannot supply either a link or a number unfortunately
    if (label_exists($label))
      $code = str_replace($matches[0][$i], "<h3>" . get_id_referring_to($label) . ". " . $matches[1][$i] . "</h3>", $code);
    else
      $code = str_replace($matches[0][$i], "<h3>" . $matches[1][$i] . "</h3>", $code);
  }

  $count = preg_match_all("/\\\subsection\{(" . $regex . ")\}\n\\\label\{([\w-]+)\}/u", $code, $matches);
  for ($i = 0; $i < $count; $i++) {
    $label = $file . '-' . $matches[2][$i];
    $code = str_replace($matches[0][$i], "<h4><a class='environment-link' href='" . get_tag_referring_to($label) . "'>" . get_id_referring_to($label) . ". " . $matches[1][$i] . "</a></h4>", $code);
  }

  // remove remaining labels
  $code = preg_replace("/\\\label\{[\w\-]+\}\n?/", "", $code);
  

  // remove \linebreak commands
  $code = preg_replace("/\\\linebreak(\[\d?\])?/", "", $code);

  // lines starting with % (tag 03NV for instance) should be removed
  $code = preg_replace("/\%[\w.]+/", "", $code);

  // these do not fit into the system above
  $code = str_replace("\\begin{center}\n", "<center>", $code);
  $code = str_replace("\\end{center}", "</center>", $code);
  
  $code = str_replace("\\begin{quote}", "<blockquote>", $code);
  $code = str_replace("\\end{quote}", "</blockquote>", $code);

  // proof environment
  $code = str_replace("\\begin{proof}\n", "<p><strong>Proof.</strong> ", $code);
  $code = preg_replace("/\\\begin\{proof\}\[(" . $regex . ")\]/u", "<p><strong>$1</strong> ", $code);
  $code = str_replace("\\end{proof}", "<span style='float: right;'>$\square$</span></p>", $code);

  // hyperlinks
  $code = preg_replace("/\\\href\{(.*)\}\{(" . $regex . ")\}/u", "<a href=\"$1\">$2</a>", $code);
  $code = preg_replace("/\\\url\{(.*)\}/", "<a href=\"$1\">$1</a>", $code);

  // emphasis
  $code = preg_replace("/\{\\\it (" . $regex . ")\}/u", "<em>$1</em>", $code);
  $code = preg_replace("/\{\\\bf (" . $regex . ")\}/u", "<strong>$1</strong>", $code);
  $code = preg_replace("/\{\\\em (" . $regex . ")\}/u", "<em>$1</em>", $code);
  $code = preg_replace("/\\\emph\{(" . $regex . ")\}/u", "<em>$1</em>", $code);

  // footnotes
  $code = preg_replace("/\\\\footnote\{(" . $regex . ")\}/u", " ($1)", $code);

  // handle citations
  $count = preg_match_all("/\\\cite\{([\.\w\-\_]*)\}/", $code, $matches);
  for ($i = 0; $i < $count; $i++) {
    $item = get_bibliography_item($matches[1][$i]);
    $code = str_replace($matches[0][$i], '[<a title="' . parse_value($item['author']) . ', ' . parse_value($item['title']) . '" href="' . full_url('bibliography/' . $matches[1][$i]) . '">' . $matches[1][$i] . "</a>]", $code);
  }
  $count = preg_match_all("/\\\cite\[(" . $regex . ")\]\{([\w-]*)\}/", $code, $matches);
  for ($i = 0; $i < $count; $i++) {
    $item = get_bibliography_item($matches[2][$i]);
    $code = str_replace($matches[0][$i], '[<a title="' . parse_value($item['author']) . ', ' . parse_value($item['title']) . '" href="' . full_url('bibliography/' . $matches[2][$i]) . '">' . $matches[2][$i] . "</a>, " . $matches[1][$i] . "]", $code);
  }

  // filter \input{chapters}
  $code = str_replace("\\input{chapters}", "", $code);

  // enumerates etc.
  $code = str_replace("\\begin{enumerate}\n", "<ol>", $code);
  $code = str_replace("\\end{enumerate}", "</ol>", $code);
  $code = str_replace("\\begin{itemize}\n", "<ul>", $code);
  $code = str_replace("\\end{itemize}", "</ul>", $code);
  $code = preg_replace("/\\\begin{list}(.*)\n/", "<ul>", $code); // unfortunately I have to ignore information in here
  $code = str_replace("\\end{list}", "</ul>", $code);
  $code = preg_replace("/\\\item\[(" . $regex . ")\]/u", "<li>", $code);
  $code = str_replace("\\item", "<li>", $code);

  // let HTML be aware of paragraphs
  $code = str_replace("\n\n", "</p><p>", $code);
  $code = str_replace("\\smallskip", "", $code);
  $code = str_replace("\\medskip", "", $code);
  $code = str_replace("\\noindent", "", $code);

  // parse references
  //$code = preg_replace('/\\\ref\{(.*)\}/', "$1", $code);
  $references = array();

  // don't escape in math mode because XyJax doesn't like that, and fix URLs too
  $lines = explode("\n", $code);
  $math_mode = false;
  foreach ($lines as &$line) {
    // $$ is a toggle
    if ($line == '$$')
      $math_mode = !$math_mode;
    $environments = array('equation', 'align', 'align*', 'eqnarray', 'eqnarray*');
    foreach ($environments as $environment) {
      if ($line == '\begin{' . $environment . '}') $math_mode = true;
      if ($line == '\end{' . $environment . '}') $math_mode = false;
    }

    if ($math_mode) {
      $line = str_replace('&gt;', '>', $line);
      $line = str_replace('&lt;', '<', $line);
      $line = str_replace('&amp;', '&', $line);
      
      $count = preg_match_all('/\\\ref{<a href=\"([\w\/]+)\">([\w-]+)<\/a>}/', $line, $matches);
      for ($j = 0; $j < $count; $j++) {
        $line = str_replace($matches[0][$j], get_id(substr($matches[1][$j], -4)), $line);
      }
    }
  }
  $code = implode("\n", $lines);
  
  $count = preg_match_all('/\\\ref{<a href=\"([\w\/]+)\">([\w-]+)<\/a>}/', $code, $references);
  for ($i = 0; $i < $count; ++$i) {
    $code = str_replace($references[0][$i], "<a href='" . $references[1][$i] . "'>" . get_id(substr($references[1][$i], -4, 4)) . "</a>", $code);
  }


  // fix macros
  $macros = array(
    // TODO check \mathop in output
    "\\lim" => "\mathop{\\rm lim}\\nolimits",
    "\\colim" => "\mathop{\\rm colim}\\nolimits",
    "\\Spec" => "\mathop{\\rm Spec}",
    "\\Hom" => "\mathop{\\rm Hom}\\nolimits",
    "\\SheafHom" => "\mathop{\mathcal{H}\!{\it om}}\\nolimits",
    "\\Sch" => "\\textit{Sch}",
    "\\Mor" => "\mathop{\\rm Mor}\\nolimits",
    "\\Ob" => "\mathop{\\rm Ob}\\nolimits",
    "\\Sh" => "\mathop{\\textit{Sh}}\\nolimits");
  $code = str_replace(array_keys($macros), array_values($macros), $code);

  return $code;
}

function print_navigation() {
?>
    <ul id="navigation">
      <li><a href="<?php print(full_url('')); ?>">index</a>
      <li><a href="<?php print(full_url('about')); ?>">about</a>
      <li><a href="<?php print(full_url('tags')); ?>">tags explained</a>
      <li><a href="<?php print(full_url('tag')); ?>">tag lookup</a>
      <li><a href="<?php print(full_url('browse')); ?>">browse</a>
      <li><a href="<?php print(full_url('search')); ?>">search</a>
      <li><a href="<?php print(full_url('bibliography')); ?>">bibliography</a>
      <li><a href="<?php print(full_url('recent-comments')); ?>">recent comments</a>
      <li><a href="http://math.columbia.edu/~dejong/wordpress/">blog</a>
    </ul>
    <br style="clear: both;">
<?php
}

function print_tag_code_and_preview($tag, $file, $code) {
  print("<p class='view-code-toggle' id='tag-preview-output-" . $tag . "-link'><a href='#tag-preview-code-" . $tag . "'>code</a></p>");
  print("<blockquote class='tag-preview-output' id='tag-preview-output-" . $tag . "'>\n" . parse_latex($tag, $file, $code) . "</blockquote>\n");

  print("<p class='view-code-toggle' id='tag-preview-code-" . $tag . "-link'><a href='#tag-preview-output-" . $tag . "'>view</a></p>");
  print("<pre class='tag-preview-code' id='tag-preview-code-" . $tag . "'>\n" . parse_preview($code) . "\n    </pre>\n");

?>
  <script type="text/javascript">
    $(document).ready(function() {
      // hide preview
      $("#tag-preview-code-<?php print($tag); ?>").toggle();
      $("#tag-preview-code-<?php print($tag); ?>-link").toggle();
    });

    function toggle_preview_output(e) {
      // prevent movement
      e.preventDefault();

      $("#tag-preview-output-<?php print($tag); ?>, #tag-preview-output-<?php print($tag); ?>-link").toggle();
      $("#tag-preview-code-<?php print($tag); ?>, #tag-preview-code-<?php print($tag); ?>-link").toggle();
    }
   
    $("#tag-preview-code-<?php print($tag); ?>-link a").click(toggle_preview_output);
    $("#tag-preview-output-<?php print($tag); ?>-link a").click(toggle_preview_output);

  </script>
<?php
}
?>
