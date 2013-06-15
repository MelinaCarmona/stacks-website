<?php

require_once("php/page.php");
require_once("php/general.php");

class StatisticsPage extends Page {
  private $statistics;
  private $tag;

  public function __construct($database, $tag) {
    $this->db = $database;

    $sql = $this->db->prepare("SELECT tag, creation_date, creation_commit, modification_date, modification_commit, label, position FROM tags WHERE tag = :tag");
    $sql->bindParam(":tag", $tag);

    if ($sql->execute())
      $this->tag = $sql->fetch();

    // phantom is actually a chapter
    if (isPhantom($this->tag["label"]))
      $this->tag["type"] = "chapter";

    $sql = $this->db->prepare("SELECT key, value FROM statistics WHERE key LIKE :tag");
    $sql->bindValue(":tag", $tag . "%");

    if ($sql->execute())
      $result = $sql->fetchAll();

    foreach ($result as $row)
      $this->statistics[substr($row["key"], 5)] = $row["value"];
  }

  public function getHead() {
    $output = "";

    $output .= "<link rel='stylesheet' type='text/css' href='" . href("css/tag.css") . "'>";

    return $output;
  }

  public function getMain() {
    $output = "";
    $output .= "<h2>Tag <var>" . $this->tag["tag"] . "</var></h2>";

    $output .= "<h3>Information on the label</h3>";
    $output .= "<p>This tag currently has the label <var>" . $this->tag["label"] . "</var>.";
    $output .= "<dl>";
    $output .= "<dt>Part of the Stacks project since</dt>";
    if ($this->tag["creation_date"] == "May 16 11:14:00 2009 -0400")
      $output .= "<dd>" . $this->tag["creation_date"] . " (see <a href='" . href("tags#stacks-epoch") . "'>Stacks epoch</a>) in <a href='https://github.com/stacks/stacks-project/commit/" . $this->tag["creation_commit"] . "'>commit " . substr($this->tag["creation_commit"], 0, 7) . "</a>.</dd>";
    else
      $output .= "<dd>" . $this->tag["creation_date"] . " in <a href='https://github.com/stacks/stacks-project/commit/" . $this->tag["creation_commit"] . "'>commit " . substr($this->tag["creation_commit"], 0, 7) . "</a>.</dd>";
    $output .= "<dt>Last modification to this label (not its contents)</dt>";
    $output .= "<dd>" . $this->tag["modification_date"] . " in <a href='https://github.com/stacks/stacks-project/commit/" . $this->tag["modification_commit"] . "'>commit " . substr($this->tag["modification_commit"], 0, 7) . "</a>.</dd>";
    $output .= "</dl>";

    $output .= "<h3>Numbers</h3>";
    $output .= "<p>The dependency graph has the following properties";
    $output .= "<table class='alternating' id='numbers'>";
    $output .= "<tr><td>number of nodes</td><td>" . $this->statistics["node count"] . "</td><td></td>";
    $output .= "<tr><td>number of edges</td><td>" . $this->statistics["edge count"] . "</td><td>(ignoring multiplicity)</tr>";
    $output .= "<tr><td></td><td>" . ($this->statistics["total edge count"] - 1) . "</td><td>(with multiplicity)</tr>";
    $output .= "<tr><td>number of chapters used</td><td>" . $this->statistics["chapter count"] . "</td><td></tr>";
    $output .= "<tr><td>number of tags using this tag</td><td><em>5</em></td><td>(directly)</td>";
    $output .= "<tr><td></td><td><em>235</em></td><td>(both directly and indirectly)</td>";
    $output .= "</table>";

    // TODO only if there are actually results using this tag
    $output .= "<h3>Tags using this result</h3>";
    $output .= "<ul id='using'>";
    $output .= "<li><a href='" . href("tag/" . "0123") . "'><var>0123</var></a>";
    $output .= "</ul>";

    # TODO this page needs more stuff, and a sidebar

    return $output;
  }
  public function getSidebar() {
    $output = "";

    $output .= "<h2>Navigating results</h2>";
    $siblingTags = getSiblingTags($this->tag["position"]);
    if (!empty($siblingTags)) {
      $output .= "<p class='navigation'>";
      if (isset($siblingTags["previous"]))
        $output .= "<span class='left'><a title='" . $siblingTags["previous"]["tag"] . " " . $siblingTags["previous"]["label"] . "' href='" . href("tag/" . $siblingTags["previous"]["tag"]) . "/statistics'>&lt;&lt; Previous tag</a></span>";
      if (isset($siblingTags["next"]))
        $output .= "<span class='right'><a title='" . $siblingTags["next"]["tag"] . " " . $siblingTags["next"]["label"] . "' href='" . href("tag/" . $siblingTags["next"]["tag"]) . "/statistics'>Next tag &gt;&gt;</a></span>";
      $output .= "</p>";
    }

    $output .= "<h2>Dependency graphs</h2>";
    $output .= "<p style='margin-left: 1em'>" . printGraphLink($this->tag["tag"], "cluster", "cluster") . "<br>";
    $output .= printGraphLink($this->tag["tag"], "force", "force-directed") . "<br>";
    $output .= printGraphLink($this->tag["tag"], "collapsible", "collapsible") . "<br>";

    return $output;
  }
  public function getTitle() {
    return " &mdash; Statistics for the tag " . $this->tag["tag"];
  }
}

?>

