<?php

require_once("bibliography.php");
require_once("general.php");

function parseFootnotes($string) {
  $parts = explode("\\footnote", $string);

  $result = $parts[0];

  // each of these strings contains a footnote at the beginning
  foreach (array_slice($parts, 1) as $part) {
    $number = 0;
    foreach (str_split($part) as $i => $character) {
      if ($character == "{") $number++;

      if ($character == "}") {
        $number--;

        if ($number == 0) {
          $part[0] = "(";
          $part[$i] = ")";
          $result = $result . " " . $part;
          break;
        }
      }
    }
  }

  return $result;
}

function getMacros() {
  global $database;

  $sql = $database->prepare('SELECT name, value FROM macros');

  if ($sql->execute()) {
    $rows = $sql->fetchAll();

    $result = array();
    foreach ($rows as $row)
      $result[$row["name"]] = $row["value"];
    return $result;
  }

  return array();
}

function preprocessCode($code) {
  // remove irrelevant new lines at the end
  $code = trim($code);
  // escape stuff
  $code = htmlentities($code);

  // but links should work: tag links are made up from alphanumeric characters, slashes, dashes and underscores, while the LaTeX label contains only alphanumeric characters and dashes
  $code = preg_replace('/&lt;a href=&quot;\/([A-Za-z0-9\/\-]+)&quot;&gt;([A-Za-z0-9\-]+)&lt;\/a&gt;/', '<a href="' . href("") . '$1">$2</a>', $code);

  return $code;
}

function convertLaTeX($tag, $file, $code) {
  // get rid of things that should be HTML
  $code = preprocessCode($code);

  // this is the regex for all (sufficiently nice) text that can occur in things like \emph
  $regex = "[\p{L}\p{Nd}\?@\s$,.:()'N&#;\-\\\\$]+";

  // fix special characters (&quot; should be " for \"e)
  $code = parseAccents(str_replace("&quot;", "\"", $code));

  // all big environments with their corresponding markup
  $environments = array(
    "lemma"       => array("name" => "Lemma",       "type" => "plain"),
    "definition"  => array("name" => "Definition",  "type" => "definition"),
    "remark"      => array("name" => "Remark",      "type" => "remark"),
    "remarks"     => array("name" => "Remarks",     "type" => "remark"),
    "example"     => array("name" => "Example",     "type" => "definition"),
    "theorem"     => array("name" => "Theorem",     "type" => "plain"),
    "exercise"    => array("name" => "Exercise",    "type" => "definition"),
    "situation"   => array("name" => "Situation",   "type" => "definition"),
    "proposition" => array("name" => "Proposition", "type" => "plain")
  );

  foreach ($environments as $environment => $information) {
    $count = preg_match_all("/\\\begin\{" . $environment . "\}\n\\\label\{([\w\*\-]*)\}/", $code, $matches);
    for ($i = 0; $i < $count; $i++) {
      $label = $file . '-' . $matches[1][$i];
      
      // check whether the label exists in the database, if not we cannot supply either a link or a number unfortunately
      if (labelExists($label))
        $code = str_replace($matches[0][$i], "<div class='" . $information["type"] . "'><p><a class='environment-identification' href='" . getTagWithLabel($label) . "'>" . $information["name"] . " " . getIDWithLabel($label) . ".</a>", $code);
      else
        $code = str_replace($matches[0][$i], "<div class='" . $information["type"] . "'><p><span class='environment-identification'>" . $information["name"] . ".</span>", $code);
    }

    // do the same for named environments
    $count = preg_match_all("/\\\begin\{" . $environment . "\}\[(" . $regex . ")\]\n\\\label\{([\w\-]*)\}/u", $code, $matches);
    for ($i = 0; $i < $count; $i++) {
      $label = $file . '-' . $matches[2][$i];
      
      // check whether the label exists in the database, if not we cannot supply either a link or a number unfortunately
      if (labelExists($label))
        $code = str_replace($matches[0][$i], "<div class='" . $information["type"] . "'><p><a class='environment-identification' href='" . getTagWithLabel($label) . "'>" . $information["name"] . " " . getIDWithLabel($label) . " <span class='named'>(" . $matches[1][$i] . ")</span>.</a>", $code);
      else
        $code = str_replace($matches[0][$i], "<div class='" . $information["type"] . "'><p><span class='environment-identification'>" . $information["name"] . " <span class='named'>(" . $matches[1][$i] . ")</span>.</span>", $code);
    }

    $code = str_replace("\\end{" . $environment . "}", "</div>", $code);
  }

  $count = preg_match_all("/\\\begin\{equation\}\n\\\label\{([\w\-]+)\}\n/", $code, $matches);
  for ($i = 0; $i < $count; $i++) {
    $label = $file . '-' . $matches[1][$i];

    // check whether the label exists in the database, if not we cannot supply an equation number unfortunately
    if (labelExists($label))
      $code = str_replace($matches[0][$i], "\\begin{equation}\n\\tag{" . getIDWithLabel($label) . "}\n", $code);
    else
      $code = str_replace($matches[0][$i], "\\begin{equation}\n", $code);
  }

  // sections etc.
  $count = preg_match_all("/\\\section\{(" . $regex . ")\}\n\\\label\{([\w\-]+)\}/u", $code, $matches);
  for ($i = 0; $i < $count; $i++) {
    $label = $file . '-' . $matches[2][$i];

    // check whether the label exists in the database, if not we cannot supply either a link or a number unfortunately
    if (labelExists($label))
      $code = str_replace($matches[0][$i], "<h3>" . getIDWithLabel($label) . ". " . $matches[1][$i] . "</h3>", $code);
    else
      $code = str_replace($matches[0][$i], "<h3>" . $matches[1][$i] . "</h3>", $code);
  }

  $count = preg_match_all("/\\\subsection\{(" . $regex . ")\}\n\\\label\{([\w-]+)\}/u", $code, $matches);
  for ($i = 0; $i < $count; $i++) {
    $label = $file . '-' . $matches[2][$i];
    $code = str_replace($matches[0][$i], "<h4><a class='environment-identification' href='" . getTagWithLabel($label) . "'>" . getIDWithLabel($label) . ". " . $matches[1][$i] . "</a></h4>", $code);
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
  $code = str_replace("\\end{proof}", "<span style='float: right;'>$\square$</span>", $code);

  // hyperlinks
  $code = preg_replace("/\\\href\{(.*)\}\{(" . $regex . ")\}/u", "<a href=\"$1\">$2</a>", $code);
  $code = preg_replace("/\\\url\{(.*)\}/", "<a href=\"$1\">$1</a>", $code);

  // emphasis
  $code = preg_replace("/\{\\\it (" . $regex . ")\}/u", "<em>$1</em>", $code);
  $code = preg_replace("/\{\\\bf (" . $regex . ")\}/u", "<strong>$1</strong>", $code);
  $code = preg_replace("/\{\\\em (" . $regex . ")\}/u", "<em>$1</em>", $code);
  $code = preg_replace("/\\\emph\{(" . $regex . ")\}/u", "<em>$1</em>", $code);

  // footnotes
  $code = parseFootnotes($code);
  $code = preg_replace("/\\\\footnote\{(" . $regex . ")\}/u", " ($1)", $code);


  // handle citations
  $count = preg_match_all("/\\\cite\{([\.\w,\-\_]*)\}/", $code, $matches);
  for ($i = 0; $i < $count; $i++) {
    $keys = explode(",", $matches[1][$i]);
    $matchings = explode(",", $matches[0][$i]);
    foreach ($keys as $index => $key) {
      $item = getBibliographyItem($key);
      $code = str_replace($matchings[$index], '[<a title="' . parseTeX($item['author']) . ', ' . parseTeX($item['title']) . '" href="' . href('bibliography/' . $key) . '">' . $key . "</a>]", $code);
    }
  }
  $count = preg_match_all("/\\\cite\[(" . $regex . ")\]\{([\w-]*)\}/", $code, $matches);
  for ($i = 0; $i < $count; $i++) {
    $item = getBibliographyItem($matches[2][$i]);
    $code = str_replace($matches[0][$i], '[<a title="' . parseTeX($item['author']) . ', ' . parseTeX($item['title']) . '" href="' . href('bibliography/' . $matches[2][$i]) . '">' . $matches[2][$i] . "</a>, " . $matches[1][$i] . "]", $code);
  }
  // TODO the use of the parseTeX routine should be checked

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
  $code = str_replace("\n\n", "<p>", $code);
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
    if ($line == "$$")
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
        $line = str_replace($matches[0][$j], getID(substr($matches[1][$j], -4)), $line);
      }
    }
  }
  $code = implode("\n", $lines);
  
  $count = preg_match_all('/\\\ref{&lt;a href=\"([\w\/]+)\"&gt;([\w-]+)&lt;\/a&gt;}/', $code, $references);
  for ($i = 0; $i < $count; ++$i) {
    $code = str_replace($references[0][$i], "<a href='" . href($references[1][$i]) . "'>" . getID(substr($references[1][$i], -4, 4)) . "</a>", $code);
  }

  // fix macros
  $macros = getMacros();
  $code = str_replace(array_keys($macros), array_values($macros), $code);

  return $code;
}

// get the enclosing section for every type of item (even the ones without a book_id)
function getEnclosingSection($position) {
  global $database;
  
  $sql = $database->prepare("SELECT tag, book_id, name, type FROM tags WHERE position <= :position AND type = 'section' ORDER BY position DESC LIMIT 1");
  $sql->bindParam(":position", $position);

  if ($sql->execute())
    return $sql->fetch();
}

function getEnclosingChapter($position) {
  global $database;
  
  $sql = $database->prepare("SELECT tag, book_id, name, type FROM tags WHERE position <= :position AND type = 'section' AND label LIKE '%phantom' ORDER BY position DESC LIMIT 1");
  $sql->bindParam(":position", $position);

  if ($sql->execute())
    return $sql->fetch();
}

function getEnclosingTag($position) {
  assert(positionExists($position));
  global $database;

  $sql = $database->prepare("SELECT tag, type, book_id FROM tags WHERE position < :position AND active = 'TRUE' AND type != 'item' AND TYPE != 'equation' ORDER BY position DESC LIMIT 1");
  $sql->bindParam(":position", $position);

  if ($sql->execute())
    return $sql->fetch();

  // TODO this should do more
  return "ZZZZ";
}

function getID($tag) {
  assert(isValidTag($tag));
  global $database;

  $sql = $database->prepare('SELECT book_id FROM tags WHERE tag = :tag');
  $sql->bindParam(':tag', $tag);

  if ($sql->execute())
    return $sql->fetchColumn();

  return "";
}

function getIDWithLabel($label) {
  assert(labelExists($label));
  global $database;

  $sql = $database->prepare('SELECT book_id FROM tags WHERE label = :label AND active = "TRUE"');
  $sql->bindParam(':label', $label);

  if ($sql->execute())
    return $sql->fetchColumn();

  return "ZZZZ";
}

function getTag($tag) {
  assert(isValidTag($tag));
  global $database;

  $sql = $database->prepare('SELECT tag, label, file, chapter_page, book_page, book_id, value, name, type, position FROM tags WHERE tag = :tag');
  $sql->bindParam(':tag', $tag);

  if ($sql->execute()) {
    // return first (= only) row of the result
    while ($row = $sql->fetch()) return $row;
  }
  return null;
}

function getTagAtPosition($position) {
  assert(positionExists($position));
  global $database;

  $sql = $database->prepare("SELECT tag, label FROM tags WHERE position = :position AND active = 'TRUE'");
  $sql->bindParam(":position", $position);

  if ($sql->execute())
    return $sql->fetch();

  // TODO more
  return "ZZZZ";
}

function getTagWithLabel($label) {
  assert(labelExists($label));
  global $database;

  $sql = $database->prepare('SELECT tag FROM tags WHERE label = :label AND active = "TRUE"');
  $sql->bindParam(':label', $label);

  if ($sql->execute())
    return $sql->fetchColumn();

  return "ZZZZ";
}

function isValidTag($tag) {
  return preg_match_all('/^[[:alnum:]]{4}$/', $tag, $matches) === 1;
}

function labelExists($label) {
  global $database;

  $sql = $database->prepare('SELECT COUNT(*) FROM tags WHERE label = :label');
  $sql->bindParam(':label', $label);

  if ($sql->execute())
    return intval($sql->fetchColumn()) > 0;

  return false;
}

function positionExists($position) {
  global $database;

  $sql = $database->prepare("SELECT COUNT(*) FROM tags WHERE position = :position AND active = 'TRUE'");
  $sql->bindParam(":position", $position);

  if ($sql->execute())
    return intval($sql->fetchColumn()) > 0;

  return false;
}

function tagExists($tag) {
  assert(isValidTag($tag));
  global $database;

  $sql = $database->prepare("SELECT COUNT(*) FROM tags WHERE tag = :tag");
  $sql->bindParam(":tag", $tag);

  if ($sql->execute())
    return intval($sql->fetchColumn()) > 0;

  return false;
}

function tagIsActive($tag) {
  assert(isValidTag($tag));
  global $database;

  $sql = $database->prepare("SELECT active FROM tags WHERE tag = :tag");
  $sql->bindParam(":tag", $tag);

  if ($sql->execute())
    return $sql->fetchColumn() == "TRUE";

  return false;
}

?>
