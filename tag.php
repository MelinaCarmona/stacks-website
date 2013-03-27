<?php
  header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<?php
  error_reporting(E_ALL);

  include('config.php');
  include('functions.php'); include('php-markdown-extra-math/markdown.php');

  // initialize the global database object
  try {
    $db = new PDO(get_database_location());
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  /*
   * database handling
   */

  // perform required database handling to get the comments belonging to a certain tag
  function get_comments($tag) {
    assert(is_valid_tag($tag));

    global $db;
    $comments = array();

    try {
      $sql = $db->prepare('SELECT id, tag, author, date, comment, site FROM comments WHERE tag = :tag ORDER BY date');
      $sql->bindParam(':tag', $tag);

      if ($sql->execute()) {
        while ($row = $sql->fetch())
          array_push($comments, $row);
      }
    }
    catch(PDOException $e) {
      echo $e->getMessage();

      return array();
    }

    return $comments;
  }

  // check whether a section exists using its number from the Stacks project (i.e. something like 16.3)
  function section_exists($id) {
    // if $id is empty we don't check: $id was generated by splitting a full chapter.section.subsection.result reference, but if only a chapter is present $id is empty
    if (empty($id))
      return true;

    global $db;

    try {
      $sql = $db->prepare('SELECT COUNT(*) FROM sections WHERE number = :id');
      $sql->bindParam(':id', $id);

      if ($sql->execute())
        return intval($sql->fetchColumn()) > 0;
    }
    catch(PDOException $e) {
      echo $e->getMessage();
    }
  
    return false;
  }

  // perform required database handling to get the information about a section belonging to a certain number
  function get_section($id) {
    assert(section_exists($id));

    global $db;

    try {
      $sql = $db->prepare('SELECT number, title, filename FROM sections WHERE number = :id');
      $sql->bindParam(':id', $id);

      if ($sql->execute())
        return $sql->fetch();
    }
    catch(PDOException $e) {
      echo $e->getMessage();
    }
  }

  // perform required database handling to get the title that belongs to a filename
  function get_title_from_filename($filename) {
    global $db;

    try {
      $sql = $db->prepare('SELECT number, title FROM sections WHERE filename = :filename AND number NOT LIKE "%.%"');
      $sql->bindParam(':filename', $filename);

      if ($sql->execute())
        return $sql->fetch();
    }
    catch(PDOException $e) {
      echo $e->getMessage();
    }
  }

  function get_title($tag) {
    global $db;

    try {
      $sql = $db->prepare('SELECT name FROM tags WHERE tag = :tag');
      $sql->bindParam(':tag', $tag);

      if ($sql->execute())
        return $sql->fetchColumn(0);
    }
    catch(PDOException $e) {
      echo $e->getMessage();
    }
  }

  /*
   * various functions
   */

  // convert the tag format to an integer between 0 and 1679616
  function tag_to_integer($tag) {
    assert(is_valid_tag($tag));

    $result = 0;
    for ($i = 0; $i < strlen($tag); $i++)
      $result += ((ord($tag[$i]) < 58) ? ord($tag[$i]) - 48 : ord($tag[$i]) - 55) * pow(36, 3 - $i);

    return $result;
  }

  // convert an integer between 0 and 1679616 to the tag format
  function integer_to_tag($value) {
    assert(is_int($value) and 0 <= $value and $value <= 1679616);

    $tag = '';

    for ($i = 0; $i < 4; $i++) {
      $tag .= ($value % 36 < 10) ? chr(($value % 36) + 48) : chr(($value % 36) + 55);
      $value = (int) ($value / 36);
    }

    $tag = strrev($tag);
    assert(is_valid_tag($tag));

    return $tag;
  }

  /*
   * output functions
   */

  function print_captcha() {
    print("<p>In order to prevent bots from posting comments, we would like you to prove that you are human. You can do this by <em>filling in the name of the current tag</em> in the following box. So in case this is tag <var>0321</var> you just have to write <var>0321</var>. This <abbr title='Completely Automated Public Turing test to tell Computers and Humans Apart'>captcha</abbr> seems more appropriate than the usual illegible gibberish, right?</p>\n");
?>
      <label for="check">Tag:</label>
      <input type="text" name="check" id="check"><br>
<?php
  }

  function print_comment_input($tag) {
?>
    <h2 id="comment-input-section-h2" style="cursor: pointer;">Add a comment on tag <var><?php print(htmlspecialchars($_GET['tag'])); ?></var></h2>
    <script type="text/javascript">
      $(document).ready(function() {
        $('div#comment-input-section').toggle();
        $('h2#comment-input-section-h2').append("<span style='float: right;'>&gt;&gt;&gt;</span>");
      });

      $('h2#comment-input-section-h2').click(function() {
        $('div#comment-input-section').toggle();

        // change <<< into >>> and vice versa
        if ($('div#comment-input-section').is(':visible')) {
          $('h2#comment-input-section-h2 span').text('<<<');
        }
        else {
          $('h2#comment-input-section-h2 span').text('>>>');
        }
      });
    </script>
    <div id="comment-input-section">
    <p>Your email address will not be published. Required fields are marked.
  
    <p>In your comment you can use <a href="<?php print(full_url('markdown')); ?>">Markdown</a> and LaTeX style mathematics (enclose it like <code>$\pi$</code>). A preview option is available if you wish to see how it works out (just click on the eye in the lower-right corner).
  
    <form name="comment" id="comment-form" action="<?php print(full_url('post.php')); ?>" method="post">
      <label for="name">Name<sup>*</sup>:</label>
      <input type="text" name="name" id="name"><br>
  
      <label for="mail">E-mail<sup>*</sup>:</label>
      <input type="text" name="email" id="mail"><br>
  
      <label for="site">Site:</label>
      <input type="text" name="site" id="site"><br>
  
      <label>Comment:</label> <span id="epiceditor-status"></span>
      <textarea name="comment" id="comment-textarea" cols="80" rows="10"></textarea>
      <div id="epiceditor"></div>
      <script type='text/javascript'>
        // Chromium (and Chrome too I presume) adds a bogus character when a space follows after a line break (or something like that)
        // remove this by hand for now TODO fix EpicEditor
        function sanitize(s) {
          var output = '';
          for (c in s) {
            if (s.charCodeAt(c) != 160) output += s[c]
            else output += " ";
          }
         
          return output;
        }

        var fullscreenNotice = false;

        var editor = new EpicEditor(options).load(function() {
            // TODO find out why this must be a callback in the loader, editor.on('load', ...) doesn't seem to be working?!
            // hide textarea, EpicEditor will take over
            document.getElementById('comment-textarea').style.display = 'none';
            // when the form is submitted copy the contents from EpicEditor to textarea
            document.getElementById('comment-form').onsubmit = function() {
              document.getElementById('comment-textarea').value = sanitize(editor.exportFile());
            };

            // add a notice on how to get out the fullscreen mode
            var wrapper = this.getElement('wrapper');
            var button = wrapper.getElementsByClassName('epiceditor-fullscreen-btn')[0];
            button.onclick = function() {
              if (!fullscreenNotice) {
                alert('To get out the fullscreen mode, press Escape.');
                fullscreenNotice = true;
              }
            }

            // inform the user he is in preview mode
            document.getElementById('epiceditor-status').innerHTML = '(editing)';
        });

        function preview(iframe) {
          var mathjax = iframe.contentWindow.MathJax;
  
          mathjax.Hub.Config({
            tex2jax: {inlineMath: [['$','$'], ['\\(','\\)']]}
          });
  
          var previewer = iframe.contentDocument.getElementById('epiceditor-preview');
          mathjax.Hub.Queue(mathjax.Hub.Typeset(previewer));
        }
  
        editor.on('preview', function() {
            var iframe = editor.getElement('previewerIframe');
  
            if (iframe.contentDocument.getElementById('previewer-mathjax') == null) {
              var script = iframe.contentDocument.createElement('script');
              script.type = 'text/javascript';
              script.src = 'http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS_HTML';
              script.setAttribute('id', 'previewer-mathjax');
              iframe.contentDocument.head.appendChild(script);
            }

            // inform the user he is in preview mode
            document.getElementById('epiceditor-status').innerHTML = '(previewing)';

            // wait a little for MathJax to initialize
            // TODO might this be possible through a callback?
            if (iframe.contentWindow.MathJax == null) {
              setTimeout(function() { preview(iframe) }, 500);
            }
            else {
              preview(iframe);
            };
        });

        editor.on('edit', function() {
            // inform the user he is in preview mode
            document.getElementById('epiceditor-status').innerHTML = '(editing)';
        });
        
      </script>

      <?php print_captcha(); ?>
  
      <input type="hidden" name="tag" value="<?php print($tag); ?>">
  
      <input type="submit" id="comment-submit" value="Post comment">
    </form>
    </div>
<?php
  }

  function parse_references($string) {
    // look for \ref before MathJax can and see if they point to existing tags
    $references = array();
  
    preg_match_all('/\\\ref{[\w-]*}/', $string, $references);
    foreach ($references[0] as $reference) {
      // get the label or tag we're referring to, nothing more
      $target = substr($reference, 5, -1);
  
      // we're referring to a tag
      if (is_valid_tag($target)) {
        // regardless of whether the tag exists we insert the link, the user is responsible for meaningful content
        $string = str_replace($reference, '[`' . $target . '`](' . full_url('tag/' . $target) . ')', $string);
      }
      // the user might be referring to a label
      else {
        // might it be that he is referring to a "local" label, i.e. in the same chapter as the tag?
        if (!label_exists($target)) {
          $label = get_label(strtoupper($_GET['tag']));
          $parts = explode('-', $label);
          // let's try it with the current chapter in front of the label
          $target = $parts[0] . '-' . $target;
        }
  
        // the label (potentially modified) exists in the database (and it is active), so the user is probably referring to it
        // if he declared a \label{} in his string with this particular label value he's out of luck
        if (label_exists($target)) {
          $tag = get_tag_referring_to($target);
          $string = str_replace($reference, '[`' . $tag . '`](' . full_url('tag/' . $tag) . ')', $string);
        }
      }
    }
  
    return $string;
  }


  // do all necessary parsing of comments
  function parse_comment($comment) {
    // parse \ref{}, but only when the line is not inside a code fragment
    $lines = explode("\n", $comment);
    foreach ($lines as &$line) {
      // check whether the line is a code fragment or not
      if (substr($line, 0, 4) != '    ')
        $line = parse_references($line);
    }
    $comment = implode($lines, "\n");

    // fix underscores (all underscores in math mode will be escaped
    $result = '';
    $mathmode = false;
    foreach (str_split($comment) as $position => $character) {
      // match math mode (\begin{equation}\end{equation} goes fine mysteriously)
      if ($character == "$") {
        // handle $$ correctly
        if ($position + 1 < strlen($comment) && $comment[$position + 1] != "$")
          $mathmode = !$mathmode;
      }

      // replace unescaped underscores in math mode, the accessed position always exists because we had to enter math mode first
      if ($mathmode && $character == "_" && $comment[$position - 1] != "\\")
        $result .= "\\_";
      else
        $result .= $character;
    }
    $comment = $result;
    // remove <>&"'
    $comment = htmlspecialchars($comment);
    // duplicate double backslashes
    $comment = str_replace("\\\\", "\\\\\\\\", $comment);
    // apply Markdown (i.e. we get an almost finished HTML string)
    $comment = Markdown($comment);
    // Google Chrome somehow adds this character so let's remove it
    $comment = str_replace("\xA0", ' ', $comment);
    // Firefox liked to throw in some &nbsp;'s, but I believe this particular fix is redundant now
    $comment = str_replace("&nbsp;", ' ', $comment);

    return $comment;
  }

  function print_comment($comment) {
    print("    <div class='comment' id='comment-" . $comment['id'] . "'>\n");
    $date = date_create($comment['date'], timezone_open('GMT'));
    print("      <a href='#comment-" . $comment['id'] . "'>Comment #" . $comment['id'] . "</a> by <cite class='comment-author'>" . htmlspecialchars($comment['author']) . "</cite> ");
    if (!empty($comment['site'])) {
      print(" (<a href='" . htmlspecialchars($comment['site']) . "'>site</a>)\n");
    }
    print("on " . date_format($date, 'F j, Y \a\t g:i a e') . "\n");
    print("      <blockquote>" . parse_comment($comment['comment']) . "</blockquote>\n");
    print("    </div>\n\n");
  }

  function print_comments($tag) {
    $comments = get_comments($tag);
?>
    <h2 id="comments-section-h2" style="cursor: pointer;">Comments (<?php print(count($comments)); ?>)</h2>
    <script type="text/javascript">
      $(document).ready(function() { 
        if (<?php print(count($comments)); ?> == 0) {
          $('div#comments-section').toggle();
          $('h2#comments-section-h2').append("<span style='float: right;'>&gt;&gt;&gt;</span>");
        }
        else {
          $('h2#comments-section-h2').append("<span style='float: right;'>&lt;&lt;&lt;</span>");
        }
      });
      
      $('h2#comments-section-h2').click(function() {
        $('div#comments-section').toggle();

        // change <<< into >>> and vice versa
        if ($('div#comments-section').is(':visible')) {
          $('h2#comments-section-h2 span').text('<<<');
        }
        else {
          $('h2#comments-section-h2 span').text('>>>');
        }
      });
    </script>
    <div id="comments-section">
<?php
    if (count($comments) == 0) {
      print("    <p>There are no comments yet for this tag.</p>\n\n");
    }
    else {
      foreach ($comments as $comment) {
        print_comment($comment);
      }
    }
?>
    </div>
<?php
  }

  function print_sectional_navigation($book_id) {
    // print next/previous section navigation
    global $db;

    $position = get_position_with_id($book_id);

    $sql = $db->prepare('SELECT sections.number, sections.title, tags.tag FROM sections, tags WHERE tags.position < :position AND tags.type = "section" AND sections.number LIKE "%.%" AND tags.book_id = sections.number ORDER BY tags.position DESC LIMIT 1');
    $sql->bindParam(':position', $position);

    if ($sql->execute()) {
      while ($row = $sql->fetch()) {
        print "<p style='font-size: .9em;' id='navigate-back'><a title='" . $row['title'] . "' href='" . full_url('tag/' . $row['tag']) . "'>&lt;&lt; Section <var>" . $row['number'] . "</var></a>";
      }
    }

    $sql = $db->prepare('SELECT sections.number, sections.title, tags.tag FROM sections, tags WHERE tags.position > :position AND tags.type = "section" AND tags.book_id = sections.number AND sections.number LIKE "%.%" ORDER BY tags.position LIMIT 1');
    $sql->bindParam(':position', $position);

    if ($sql->execute()) {
      while ($row = $sql->fetch()) {
        print "<p style='font-size: .9em;' id='navigate-forward'><a title='" . $row['title'] . "' href='" . full_url('tag/' . $row['tag']) . "'>Section <var>" . $row['number'] . " &gt;&gt;</var></a>";
      }
    }
  }

  function print_tag($tag) {
    $results = get_tag($tag);
    
    print("    <h2>Tag: <var>" . $tag . "</var></h2>\n");

    $parts = explode('.', $results['book_id']);
    # the identification of the result relative to the local section
    $relative_id = implode('.', array_slice($parts, 1));
    # the identification of the (sub)section of the result
    $section_id = implode('.', array_slice($parts, 0, 2));
    # the id of the chapter, the first part of the full identification
    $chapter_id = $parts[0];

    // navigational code, this is a duplicate of the navigation code at the bottom of a tag
    $results['position'] = intval($results['position']);
    if (position_exists($results['position'] - 1)) {
      $previous_tag = get_tag_at($results['position'] - 1);
      print "<p style='font-size: .9em;' id='navigate-back'><a title='" . $previous_tag['label'] . "' href='" . full_url('tag/' . $previous_tag['tag']) . "'>&lt;&lt; Previous tag <var>" . $previous_tag['tag'] . "</var></a>";
    }
    // print empty navigation for layout purposes
    else
      print "<p style='font-size: .9em;' id='navigate-back'>&nbsp;</p>";

    if (position_exists($results['position'] + 1)) {
      $next_tag = get_tag_at($results['position'] + 1);
      print "<p style='font-size: .9em;' id='navigate-forward'><a title='" . $next_tag['label'] . "' href='" . full_url('tag/' . $next_tag['tag']) . "'>Next tag <var>" . $next_tag['tag'] . " &gt;&gt;</var></a>";
    }
    else
      print "<p style='font-size: .9em;' id='navigate-forward'>&nbsp;</p>";

    if ($results['type'] == 'section')
      print_sectional_navigation($results['book_id']);

    // get all information about the current section and chapter
    if (!section_exists($section_id) or !section_exists($chapter_id)) {
      print("    <p>This tag has label <var>" . $results['label'] . "</var> but there is something wrong in the database because it doesn't belong to a correct section of the project.\n");
      return;
    }
    $section_information = get_section($section_id);
    $chapter_information = get_section($chapter_id);

    // general information
    // if the type of a tag is 'item' or 'equation' it will have a name, but we're not interested in it
    if (empty($results['name']) or $results['type'] == 'item' or $results['type'] == 'equation') {
      print("    <p>This tag has label <var>" . $results['label'] . "</var> and it points to\n");
    }
    else {
      print("    <p>This tag has label <var>" . $results['label'] . "</var>, it is called <strong>" . latex_to_html($results['name']) . "</strong> in the Stacks project and it points to\n");
    }

    // information about the location of the tag in the Stacks project
    print("    <ul>\n");
    // all types except 'item' and section-phantom labels can be handled in the same vein
    if ($results['type'] == 'item') {
      // if the type is 'item' there is no specific information about the location
      $title = get_title_from_filename($results['file']);
      print("      <li><a href='" . full_url('download/' . $results['file'] . ".pdf#" . $tag) . "'>" . ucfirst($results['type']) . " " . $results['book_id'] . " on page " . $results['chapter_page'] . "</a> of Chapter " . $title['number'] . ": " . latex_to_html($title['title']) . "\n");
      print("      <li><a href='" . full_url('download/book.pdf#' . $tag) . "'>" . ucfirst($results['type']) . " " . $results['book_id'] . " on page " . $results['book_page'] . "</a> of the book version\n");
    }
    elseif (substr($results['label'], -15) == 'section-phantom') {
      // section-phantom labels contain no relevant information unfortunately and just refer to the chapter
      $title = get_title_from_filename($results['file']);
      print("      <li><a href='" . full_url('download/' . $results['file'] . ".pdf#" . $tag) . "'>The start of the chapter</a> in  <a href='" . full_url('chapter/' . $chapter_id) . "'>Chapter " . $chapter_id . ": " . latex_to_html($chapter_information['title']) . "</a>");
      print("      <li><a href='" . full_url('download/book.pdf#' . $tag) . "'>" . ucfirst($results['type']) . " " . $results['book_id'] . " on page " . $results['book_page'] . "</a> of the book version\n");
    }
    else {
      // the tag refers to a result in a chapter, not contained in a (sub)section, i.e. don't display that information
      if ($section_id == $chapter_id or $results['type'] == 'section') {
        print("      <li><a href='" . full_url('download/' . $chapter_information['filename'] . ".pdf#" . $tag) . "'>" . ucfirst($results['type']) . " " . $relative_id . " on page " . $results['chapter_page'] . "</a> of <a href='" . full_url('chapter/' . $chapter_id) . "'>Chapter " . $chapter_id . ": " . latex_to_html($chapter_information['title']) . "</a>\n");
      }
      else {
        $section_tag = get_tag_with_id($section_id);
        print("      <li><a href='" . full_url('download/' . $chapter_information['filename'] . ".pdf#" . $tag) . "'>" . ucfirst($results['type']) . " " . $relative_id . " on page " . $results['chapter_page'] . "</a> of <a href='" . full_url('tag/' . $section_tag['tag']) . "'>Section " . implode('.', array_slice(explode('.', $section_id), 1)) . ": " . latex_to_html($section_information['title']) . "</a>, in <a href='" . full_url('chapter/' . $chapter_id) . "'>Chapter " . $chapter_id . ": " . latex_to_html($chapter_information['title']) . "</a>\n");
      }

      print("      <li><a href='" . full_url('download/book.pdf#' . $tag) . "'>". ucfirst($results['type']) . " " . $results['book_id'] . " on page " . $results['book_page'] . "</a> of the book version\n");
    }
    print("    </ul>\n\n");

    // if the type of a tag is 'item' or 'equation' we refer to the tag it is a part of
    if ($results['type'] == 'item' or $results['type'] == 'equation') {
      $parent_tag = get_parent_tag($results['position']);
      print("and it belongs to <a href='" . full_url('tag/' . $parent_tag['tag']) . "'>" . ucfirst($parent_tag['type']) . " " . $parent_tag['book_id'] . '</a>');
    }

    // output LaTeX code
    if(empty($results['value'])) {
      print("    <p>There is no LaTeX code associated to this tag.\n");
    }
    else {
      print("    <p>The corresponding content:");
      print_tag_code_and_preview($tag, $results['file'], $results['value']);
    }

    if ($results['type'] == 'section')
      print_sectional_navigation($results['book_id']);
    else {
      // navigational code
      $results['position'] = intval($results['position']);
      if (position_exists($results['position'] - 1)) {
        $previous_tag = get_tag_at($results['position'] - 1);
        print "<p style='font-size: .9em;' id='navigate-back'><a title='" . $previous_tag['label'] . "' href='" . full_url('tag/' . $previous_tag['tag']) . "'>&lt;&lt; Previous tag <var>" . $previous_tag['tag'] . "</var></a>";
      }
      // print empty navigation for layout purposes
      else
        print "<p style='font-size: .9em;' id='navigate-back'>&nbsp;</p>";

      if (position_exists($results['position'] + 1)) {
        $next_tag = get_tag_at($results['position'] + 1);
        print "<p style='font-size: .9em;' id='navigate-forward'><a title='" . $next_tag['label'] . "' href='" . full_url('tag/' . $next_tag['tag']) . "'>Next tag <var>" . $next_tag['tag'] . " &gt;&gt;</var></a>";
      }
      else
        print "<p style='font-size: .9em;' id='navigate-forward'>&nbsp;</p>";
    }
  }

  function print_tag_lookup() {
?>
    <h2>Look for a tag</h2>

    <form action="<?php print(full_url('tag_search.php')); ?>" method="post">
      <label>Tag: <input type="text" name="tag"></label>
      <input type="submit" value="locate">
    </form>

    <p>For more information we refer to the <a href="<?php print(full_url('tags')); ?>">tags explained</a> page.
<?php
  }

  function print_inactive_tag($tag) {
    print("    <h2>Inactive tag: <var>" . $tag . "</var></h2>\n");
    print("    <p>The tag you requested did at some point in time belong to the Stacks project, but it was removed.\n");
    print_tag_lookup();
  }

  function print_missing_tag($tag) {
    print("    <h2>Missing tag: <var>" . $tag . "</var></h2>\n");
    print("    <p>The tag you requested does not exist.\n");
    print_tag_lookup();
  }
?>
<html>
  <head>
<?php
  if (isset($_GET['tag']) and is_valid_tag($_GET['tag'])) {
    $title = get_title($_GET['tag']);
    if ($title != '')
      print("    <title>Stacks Project -- Tag " . $_GET['tag'] . ": " . latex_to_html(get_title($_GET['tag'])) . "</title>\n");
    else
      print("    <title>Stacks Project -- Tag " . $_GET['tag'] . "</title>\n");
  }
  else
    print("    <title>Stacks Project -- Tag lookup</title>\n");
?>
    <link rel="stylesheet" type="text/css" href="<?php print(full_url('style.css')); ?>">
    <link rel="stylesheet" type="text/css" href="<?php print(full_url('css/stacks-preview.css')); ?>">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="icon" type="image/vnd.microsoft.icon" href="<?php print(full_url('stacks.ico')); ?>"> 
    <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="<?php print(full_url('stacks.ico')); ?>"> 
    <meta charset="utf-8">

    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>

    <style type="text/css">
      p#navigate-forward,
      p#navigate-back {
        margin-bottom: 1em;
      }
    </style>

    <script type="text/javascript" src="/MathJax/MathJax.js?config=default"></script>
    <script type="text/x-mathjax-config">
      MathJax.Hub.Config({
        extensions: ["tex2jax.js", "fp.js"],
        tex2jax: {inlineMath: [['$','$'], ['\\(','\\)']]},
        TeX: {extensions: ["xypic.js", "AMSmath.js", "AMSsymbols.js"], TagSide: "left"},
        "HTML-CSS": { scale: 85 }
      });
    </script>

    <script type="text/javascript" src="<?php print(full_url('EpicEditor/epiceditor/js/epiceditor.js')); ?>"></script>
    <script type="text/javascript">
      /** Stacks Flavored Markdown
       * In order to accomodate some of the specifics of the Stacks project we preprocess text. This entails
       * 1) double backslashes are converted to quadruple backslashes to ensure proper LaTeX parsing
       * 2) interpreting \ref{}
       */
      function sfm(text) {
        // all double backslashed should be doubled to quadruple backslashes to ensure proper LaTeX results
        text = text.replace(/\\\\/g, "\\\\\\\\");
        // \ref{0000} can point to the correct URL (all others have to be (ab)used by MathJax)
        var lines = text.split(/\r?\n/);
        for (var i = 0; i < lines.length; i++) {
          if (lines[i].substring(0, 4) != '    ')
            lines[i] = lines[i].replace(/\\ref\{(\w{4})\}/g, "[$1](http://math.columbia.edu<?php print(full_url('tag/$1')); ?>)");
        }
        text = lines.join("\n");

        // fix underscores (all underscores in math mode will be escaped
        var result = '';
        var mathmode = false;
        for (c in text) {
          // match math mode (\begin{equation}\end{equation} goes fine mysteriously)
          if (text[c] == "$") {
            // handle $$ correctly
            if (window.parseInt(c) + 1 < text.length && text[window.parseInt(c) + 1] != "$")
              mathmode = !mathmode;
          }

          // replace unescaped underscores in math mode, the accessed position always exists because we had to enter math mode first
          if (mathmode && text[c] == "_" && text[window.parseInt(c) - 1] != "\\")
            result += "\\_";
          else
            result += text[c];
        }

        return marked(result);
      }
      var options = {
        basePath: '<?php print(full_url('EpicEditor/epiceditor')); ?>',
        file: {
          name: '<?php print(htmlspecialchars($_GET['tag'])); ?>',
          defaultContent: 'You can type your comment here, use the preview option to see what it will look like.',
        },
        theme: {
          editor: '/themes/editor/stacks-editor.css',
          preview: '/themes/preview/stacks-preview.css',
        },
        parser : sfm,
        shortcut : {
          modifier : 0,
        } 
      }
    </script>
  </head>
  <body>
    <h1><a href="<?php print(full_url('')); ?>">The Stacks Project</a></h1>
    <?php print_navigation(); ?>

<?php
  if (!empty($_GET['tag'])) {
    $_GET['tag'] = strtoupper($_GET['tag']);

    if (is_valid_tag($_GET['tag'])) {
      // from here on it's safe to ignore the fact it is user input
      $tag = $_GET['tag'];

      if (tag_exists($tag)) {
        if (tag_is_active($tag)) {
          print_tag($tag);
          print_comments($tag);

          print_comment_input($tag);
        }
        else {
          print_inactive_tag($tag);
        }
      }
      else {
        print_missing_tag($tag);
      }
    }
    else {
      print("    <h2>Error</h2>\n");
      print("    <p>The tag you provided (i.e. <var>" . htmlspecialchars($_GET['tag']) . "</var>) is not in the correct format. See <a href=\"" . full_url('tags') . "\">tags explained</a> for an overview of the tag system and a description of the format. A summary: four characters, either digits or capital letters, e.g. <var>03DF</var>.\n");
      print("    <p>Perhaps you intended to search for the text <var>" . htmlspecialchars($_GET['tag']) . "</var> in the project? In case you did: <a href='" . full_url('search?keywords=' . $_GET['tag']) . "'>perform this search</a>.\n");
      print_tag_lookup();
    }
  }
  else {
    print_tag_lookup();
  }
?>
    <p id="backlink">Back to the <a href="<?php print(full_url('')); ?>">main page</a>.
  </body>
</html>
