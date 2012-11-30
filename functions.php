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

function latex_to_html($text) {
 $text = str_replace("\'E", "&Eacute;", $text);
 $text = str_replace("\'e", "&eacute;", $text);
 // TODO more accents
 $text = str_replace("\"o", "&ouml;", $text);
 $text = str_replace("\`e", "&egrave;", $text);
 $text = str_replace("{\\v C}", "&#268;", $text);
 $text = str_replace("``", "\"", $text);
 $text = str_replace("''", "\"", $text);
 // this is to remedy a bug in import_titles
 $text = str_replace("\\v C}", "&#268;", $text);

 return $text;
}

function parse_preview($preview) {
  // remove irrelevant new lines at the end
  $preview = trim($preview);
  // escape stuff
  $preview = htmlentities($preview);
  // but links should work: tag links are made up from alphanumeric characters, slashes, dashes and underscores, while the LaTeX label contains only alphanumeric characters and dashes
  $preview = preg_replace('/&lt;a href=&quot;([A-Za-z0-9\/-_]+)&quot;&gt;([A-Za-z0-9\-]+)&lt;\/a&gt;/', '<a href="' . full_url('') . '$1">$2</a>', $preview);

  return $preview;
}

function parse_latex($tag, $code) {
  // get rid of things that should be HTML
  $code = parse_preview($code);

  // TODO interpunction \w\s should be extended to cover this, no .*

  // remove labels
  $code = preg_replace("/\\\label\{.*\}/", "", $code);

  // all big environments with their corresponding markup
  $code = str_replace("\\begin{center}\n", "<center>", $code);
  $code = str_replace("\\end{center}", "</center>", $code);

  $code = str_replace("\\begin{lemma}\n", "<strong>Lemma</strong> <em>", $code);
  $code = preg_replace("/\\\begin{lemma}\[(.*)\]/", "<strong>Lemma</strong> ($1)", $code);
  $code = str_replace("\\end{lemma}", "</em></p>", $code);
  
  $code = str_replace("\\begin{definition}\n", "<strong>Definition</strong> ", $code);
  $code = preg_replace("/\\\begin{definition}\[(.*)\]/", "<strong>Definition</strong> ($1)", $code);
  $code = str_replace("\\end{definition}", "</p>", $code);

  $code = str_replace("\\begin{remark}\n", "<strong>Remark</strong> ", $code);
  $code = preg_replace("/\\\begin{remark}\[(.*)\]/", "<strong>Remark</strong> ($1)", $code);
  $code = str_replace("\\end{remark}", "</p>", $code);

  $code = str_replace("\\begin{remarks}\n", "<strong>Remarks</strong> ", $code);
  $code = str_replace("\\end{remarks}\n", "</p>", $code);

  $code = str_replace("\\begin{quote}", "<blockquote>", $code);
  $code = str_replace("\\end{quote}", "</blockquote>", $code);

  $code = str_replace("\\begin{example}\n", "<strong>Example</strong> ", $code);
  $code = preg_replace("/\\\begin{example}\[(.*)\]/", "<strong>Example</strong> ($1)", $code);
  $code = str_replace("\\end{example}", "</p>", $code);

  $code = str_replace("\\begin{theorem}\n", "<strong>Theorem</strong> ", $code);
  $code = preg_replace("/\\\begin{theorem}\[(.*)\]/", "<strong>Theorem</strong> ($1)", $code);
  $code = str_replace("\\end{theorem}", "</p>", $code);

  $code = str_replace("\\begin{exercise}\n", "<strong>Exercise</strong> ", $code);
  $code = preg_replace("/\\\begin{exercise}\[(.*)\]/", "<strong>Exercise</strong> ($1)", $code);
  $code = str_replace("\\end{exercise}", "</p>", $code);

  $code = str_replace("\\begin{proposition}\n", "<strong>Proposition</strong> ", $code);
  $code = preg_replace("/\\\begin{proposition}\[(.*)\]/", "<strong>Proposition</strong> ($1)", $code);
  $code = str_replace("\\end{proposition}", "</p>", $code);

  $code = str_replace("\\begin{situation}\n", "<strong>Situation</strong> ", $code);
  $code = preg_replace("/\\\begin{situation}\[(.*)\]/", "<strong>Situation</strong> ($1)", $code);
  $code = str_replace("\\end{situation}", "</p>", $code);

  // proof environment
  $code = str_replace("\\begin{proof}\n", "<p><strong>Proof</strong> ", $code);
  $code = preg_replace("/\\\begin\{proof\}\[([^\]]*)\]/", "<p><strong>$1</strong> ", $code);
  $code = str_replace("\\end{proof}", "</p><p style='text-align: right;'>$\square$</p>", $code);

  // sections etc.
  $code = preg_replace("/\\\section\{([\w\s]*)\}/", "<h3>$1</h3>", $code);
  $code = preg_replace("/\\\subsection\{([\w\s]*)\}/", "<h4>$1</h4>", $code);

  // hyperlinks
  $code = preg_replace("/\\\href\{(.*)\}\{(.*)\}/", "<a href=\"$1\">$2</a>", $code);
  $code = preg_replace("/\\\href\{(.*)\}\{(.*)\}/", "<a href=\"$1\">$2</a>", $code);

  // emphasis
  $code = preg_replace("/\{\\\it ([\w\s]+)\}/", "<em>$1</em>", $code);
  $code = preg_replace("/\{\\\em ([\w\s]+)\}/", "<em>$1</em>", $code);
  $code = preg_replace("/\\\emph\{([\w\s]+)\}/", "<em>$1</em>", $code);

  // handle citations
  $code = preg_replace("/\\\cite\{([\w-]*)\}/", "[$1]", $code);
  $code = preg_replace("/\\\cite\[([\w \d\.-]*)\]\{([\w-]*)\}/", "[$2, $1]", $code);

  // filter \input{chapters}
  $code = str_replace("\\input{chapters}", "", $code);

  // fix special characters
  $code = latex_to_html($code);

  // enumerates etc.
  $code = str_replace("\\begin{enumerate}\n", "<ol>", $code);
  $code = str_replace("\\end{enumerate}\n", "</ol>", $code);
  $code = str_replace("\\begin{itemize}\n", "<ul>", $code);
  $code = str_replace("\\end{itemize}\n", "</ul>", $code);
  $code = preg_replace("/\\\begin{list}(.*)\n/", "<ul>", $code); // unfortunately I have to ignore information in here
  $code = str_replace("\\end{list}", "</ul>", $code);
  $code = preg_replace("/\\\item\[(.*)\]/", "<li>", $code);
  $code = str_replace("\\item", "<li>", $code);

  // let HTML be aware of paragraphs
  $code = str_replace("\n\n", "</p><p>", $code);
  $code = str_replace("\\smallskip", "", $code);

  // parse references
  //$code = preg_replace('/\\\ref\{(.*)\}/', "$1", $code);
  $references = array();
  
  preg_match_all('/\\\ref{<a href=\"(.*)\">(.*)<\/a>}/', $code, $references);
  for ($i = 0; $i < count($references[0]); ++$i) {
    $code = str_replace($references[0][$i], "<a href='" . $references[1][$i] . "'>" . get_id(substr($references[1][$i], -4, 4)) . "</a>", $code);
  }

  // remove \medskip and \noindent
  $code = str_replace("\\medskip", "", $code);
  $code = str_replace("\\noindent", "", $code);

  // fix macros
  $macros = array(
    // TODO check \mathop in output
    "\\lim" => "\mathop{\\rm lim}\\nolimits",
    "\\colim" => "\mathop{\\rm colim}\\nolimits",
    "\\Spec" => "\mathop{\\rm Spec}",
    "\\Hom" => "\mathop{\\rm Hom}\\nolimits",
    "\\SheafHom" => "\mathop{\mathcal{H}\!{\it om}}\nolimits",
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
      <li><a href="<?php print(full_url('recent-comments')); ?>">recent comments</a>
      <li><a href="http://math.columbia.edu/~dejong/wordpress/">blog</a>
    </ul>
    <br style="clear: both;">
<?php
}

function print_tag_code_and_preview($tag, $code) {
  print("<p id='tag-preview-code-" . $tag . "-link' style='float: right; font-size: .9em; margin-top: 0;'><a href='#tag-preview-output-" . $tag . "'>preview</a></p>");
  print("<pre class='tag-preview-code' id='tag-preview-code-" . $tag . "'>\n" . parse_preview($code) . "\n    </pre>\n");

  print("<p id='tag-preview-output-" . $tag . "-link' style='float: right; font-size: .9em; margin-top: 0;'><a href='#tag-preview-code-" . $tag . "'>code</a></p>");
  print("<blockquote class='tag-preview-output' id='tag-preview-output-" . $tag . "'>\n" . parse_latex($tag, $code) . "</blockquote>\n");

?>
  <script type="text/javascript">
    $(document).ready(function() {
      // hide preview
      $("#tag-preview-output-<?php print($tag); ?>").toggle();
      $("#tag-preview-output-<?php print($tag); ?>-link").toggle();
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
