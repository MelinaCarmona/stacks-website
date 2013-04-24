<?php

require_once("php/page.php");
require_once("php/general.php");

class TagViewPage extends Page {
  private $tag;

  public function __construct($database, $tag) {
    $this->db = $database;

    try {
      $sql = $this->db->prepare("SELECT tag, name FROM tags WHERE tag = :tag");
      $sql->bindParam(":tag", $tag);

      if ($sql->execute())
        $this->tag = $sql->fetch();
      // else
      // TODO error handling
    }
    catch(PDOException $e) {
      echo $e->getMessage();
    }
  }

  public function getMain() {
    $value = "";
    $value .= "<h2>Tag <var>" . $this->tag["tag"] . "</var></h2>";
    $value .= $this->printView();

    $comments = $this->getComments(); // TODO initialize in constructor?
    $value .= "<h2 id='comments-header'>Comments (" . count($comments) . ")</h2>";
    $value .= "<div id='comments'>";
    foreach($comments as $comment)
      $this->printComment($comment);
    $value .= "</div>";

    $value .= "<h2 id='comment-input-header'>Add a comment on tag <var>" . $this->tag["tag"] . "</var></h2>";
    $value .= "<div id='comment-input'>";
    $value .= "<p>Your email address will not be published. Required fields are marked.</p>";
    $value .= "<p>In your comment you can use <a href='#'>Markdown</a> and LaTeX style mathematics (enclose it like <code>$\pi$</code>). A preview option is available if you wish to see how it works out (just click on the eye in the lower-right corner).</p>"; // TODO fix link
    $value .= "<noscript>Unfortunately JavaScript is disabled in your browser, so the comment preview function will not work.</noscript>";

    $value .= "<form name='comment' id='comment-form' action='#' method='post'>";
    $value .= "<label for='name'>Name<sup>*</sup>:</label>";
    $value .= "<input type='text' name='name' id='name'><br>";
    $value .= "<label for='mail'>E-mail<sup>*</sup>:</label>";
    $value .= "<input type='text' name='email' id='mail'><br>";
    $value .= "<label for='site'>Site:</label>";
    $value .= "<input type='text' name='site' id='site'><br>";
    $value .= "<label>Comment:</label> <span id='epiceditor-status'></span>";
    $value .= "<textarea name='comment' id='comment-textarea' cols='80' rows='10'></textarea>";
    $value .= "<div id='epiceditor'></div>";

    $value .= "<p>In order to prevent bots from posting comments, we would like you to prove that you are human. You can do this by <em>filling in the name of the current tag</em> in the following box. So in case this is tag&nbsp;<var>0321</var> you just have to write&nbsp;<var>0321</var>. This <abbr title='Completely Automated Public Turing test to tell Computers and Humans Apart'>captcha</abbr> seems more appropriate than the usual illegible gibberish, right?</p>";
    $value .= "<label for='check'>Tag:</label>";
    $value .= "<input type='text' name='check' id='check'><br>";
    $value .= "<input type='hidden' name='tag' value='03D9'>";
    $value .= "<input type='submit' id='comment-submit' value='Post comment'>";
    $value .= "</form>";
    $value .= "</div>";

    return $value;
  }
  public function getSidebar() {
    $value = "";

    $value .= "<h2>How can you cite this tag?</h2>";
    $value .= $this->printCitation();

    return $value;
  }
  public function getTitle() {
    if(!empty($this->tag["title"]))
      return " -- Tag " . $this->tag["tag"] . ": " . $this->tag["name"]; // TODO latex_to_html
    else
      return " -- Tag " . $this->tag["tag"];
  }

  // private functions
  private function getComments() {
    // TODO implement
    return array();
  }
  private function printCitation() {
    $value .= "<p>Use:";
    $value .= "<pre>\\cite[Tag " . $this->tag["tag"] . "]{stacks-project}</code></pre>";
    $value .= "or one of the following (click to see and copy the code)";
    $value .= "<ul id='citation-options'>";
    $value .= "<li><a href='javascript:copyToClipboard(\"\\cite[\\href{http://stacks.math.columbia.edu/tag/" . $this->tag["tag"] . "}{Tag " . $this->tag["tag"] . "}]{stacks-project}\")'>[Tag " . $this->tag["tag"] . ", Stacks]</a>";
    $value .= "<li><a href='javascript:copyToClipboard(\"\\cite[\\href{http://stacks.math.columbia.edu/tag/" . $this->tag["tag"] . "}{Section " . $this->tag["tag"] . "}]{stacks-project}\")'>[Section " . $this->tag["tag"] . ", Stacks]</a>";
    $value .= "<li><a href='javascript:copyToClipboard(\"\\href{http://stacks.math.columbia.edu/tag/" . $this->tag["tag"] . "}{Tag " . $this->tag["tag"] . "}\")'>Tag " . $this->tag["tag"] . "</a>";
    $value .= "<li><a href='javascript:copyToClipboard(\"\\href{http://stacks.math.columbia.edu/tag/" . $this->tag["tag"] . "}{Tag " . $this->tag["tag"] . "}\")'>Section " . $this->tag["tag"] . "</a>";
    $value .= "</ul>";
    $value .= "<p>For more information, see <a href='#'>How to reference tags</a>.</p>";

    return $value;
  }
  private function printComment() {
    $value = "";
    $value .= "<div class='comment' id='comment-" . $comment["id"] . "'>";
    //    <a href='#comment-175'>Comment #175</a> by <cite class='comment-author'>Adeel</cite> on March 20, 2013 at 6:45 pm UTC
    //    <blockquote><p>In (3), the second Y should be X.</p></blockquote>
    //  </div>

    //  <div class='comment' id='comment-182'>
    //    <a href='#comment-182'>Comment #182</a> by <cite class='comment-author'>Johan</cite> (<a href='http://math.columbia.edu/~dejong'>site</a>) on March 27, 2013 at 6:05 pm UTC
    //    <blockquote><p>Fixed, see <a href='https://github.com/stacks/stacks-project/commit/0fced65bc54854942308acb7c91adfa753ebaa1c'>here</a>. Thanks!</p></blockquote>
    //  </div>
    $value .= "</div>";

    return $value;
  }
  private function printView() {
    $value = "";
    $value .= "<p id='code-link' class='toggle'><a href='#code'>code</a></p>";

    return $value;
  }

}

?>
