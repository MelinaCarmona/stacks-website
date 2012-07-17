<!doctype html>
<?php
  include('config.php');
  include('functions.php');
?>
<html>
  <head>
    <title>Stacks Project</title>
    <link rel="stylesheet" type="text/css" href="<?php print(full_url('style.css')); ?>">
    <link rel="icon" type="image/vnd.microsoft.icon" href="<?php print(full_url('stacks.ico')); ?>"> 
    <meta charset="utf-8">
  </head>

  <body>
    <h1><a href="<?php print(full_url('')); ?>">The Stacks Project</a></h1>
    <?php print_navigation(); ?>

    <h2><a href="<?php print(full_url('about')); ?>">About</a></h2>
    <p>This is the home page of the Stacks project. It is an open source textbook and reference work on algebraic stacks and the algebraic geometry needed to define them. For more general information click we have an <a href="<?php print(full_url('about')); ?>">extensive about page</a>.

    <h2><a href="<?php print(full_url('contribute')); ?>">How to contribute</a></h2>
    <p>The Stacks project is a collaborative effort. There is a <a href="tex/CONTRIBUTORS">list of people who have contributed so far</a>. If you would like to know how to participate more can be found at the <a href="<?php print(full_url('contribute')); ?>">contribute page</a>. To informally comment on the Stacks project visit the <a href="http://math.columbia.edu/~dejong/wordpress/">blog</a>.

    <h2><a href="<?php print(full_url('browse')); ?>">Browsing and Downloads</a></h2>
    <p>The entire project in one file: <a href="<?php print(full_url('download/book.pdf')); ?>"><code>pdf</code> version</a> | <a href="<?php print(full_url('download/book.dvi')); ?>"><code>dvi</code> version</a>. You can <a href="<?php print(full_url('browse')); ?>">browse the project</a>. There is a tree view which starts at <a href="<?php print(full_url('chapter/1')); ?>">Chapter 1</a>. For other downloads (e.g. TeX files) we have a <a href="<?php print(full_url('downloads')); ?>">dedicated downloads page</a>.

    <h2><a href="<?php print(full_url('tag')); ?>">Looking up results and tags</a></h2>
    <p>You can <a href="<?php print(full_url('search')); ?>">search</a> the Stacks project by keywords:
    <form action="<?php print(full_url('search')); ?>" method="get">
      <label>Keywords: <input type="text" name="keywords"></label> 
      <input type="submit" value="search"> 
    </form> 

    <p>If you on the other hand have a tag for an item (which can be anything, from section, lemma, theorem, etc.) in the Stacks project, you can <a href="<?php print(full_url('tag')); ?>">search for the tag's page</a>.

    <h2><a href="<?php print(full_url('tags')); ?>">Referencing the Stacks project</a></h2>
    <p>Items (sections, lemmas, theorems, etc.) in the Stacks project are referenced by their tag. See the <a href="<?php print(full_url('tags')); ?>">tags page</a> to learn more about tags and how to reference them in a LaTeX document.

    <h2><a href="<?php print(full_url('recent-comments')); ?>">Leaving comments</a></h2>
    <p>You can leave comments on each and every tag's page. If you wish to stay updated on the comments, there is both a <a href="<?php print(full_url('recent-comments')); ?>">page containing recent comments</a> and <a href="<?php print(full_url('recent-comments.rss')); ?>">an <abbr title="Really Simple Syndication">RSS</abbr> feed <img src="<?php print(full_url('rss-icon.png')); ?>"></a> available.

    <h2><a href="https://github.com/stacks/stacks-project/commits/master">Recent changes to the Stacks project</a></h2>
    <p>You can either see the <a href="<?php print(full_url('log.log')); ?>">last 50 log entries in plaintext</a> or <a href="https://github.com/stacks/stacks-project/commits/master">browse the complete history</a>.

    <h2><a href="<?php print(full_url('tex/COPYING')); ?>">License</a></h2>
    <p>This project is licensed under the <a href="<?php print(full_url('tex/COPYING')); ?>">GNU Free Documentation License</a>.
  </body>

</html>

