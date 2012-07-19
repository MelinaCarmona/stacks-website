<!doctype html>
<?php
  include('config.php');
  include('functions.php');
?>
<html>
  <head>
    <title>Stacks Project -- About</title>
    <link rel="stylesheet" type="text/css" href="<?php print(full_url('style.css')); ?>">
    <link rel="icon" type="image/vnd.microsoft.icon" href="<?php print(full_url('stacks.ico')); ?>"> 
    <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="<?php print(full_url('stacks.ico')); ?>"> 
    <meta charset="utf-8">
  </head>

  <body>
    <h1><a href="<?php print(full_url('')); ?>">The Stacks Project</a></h1>
    <?php print_navigation(); ?>

    <h2>About</h2>
    <p>The Stacks project started in 2005. The initial idea was for it to be a collaborative web-based project with the aim of writing an introductory text about algebraic stacks. Temporarily there was a mailing list and some discussion as to how to proceed. For example, there are issues with referencing such a document, how to distribute credit, who does what, and many more. Although we have definite ideas about most of these points we would like to take 
a more positive approach. Namely, to simply create something and solve problems and answer questions as they come up.
    
    <hr>

    <p>We do want to answer a few basic questions that the casual visitor may have 
about this project:
    <ol>
      <li>The Stacks project is no longer an introductory text, but aims to build up enough basic algebraic geometry as foundations for algebraic stacks. This implies a good deal of theory on commutative algebra, schemes, varieties, algebraic spaces, has to be developed en route.
      <li>The Stacks project has a maintainer (currently <a href="http://www.math.columbia.edu/~dejong/">A. J. de Jong</a>) who accepts changes etc. proposed by contributors. Although everyone is encouraged to participate it is not a wiki.
      <li>The Stacks project is meant to be read online, and therefore we do not worry about length of the chapters, etc. Moreover, with hyperlinks it is possible to quickly browse through the chapters to find the lemmas, theorems, etc. that a given result depends on.
    </ol>

    <h2><a href="<?php print(full_url('acknowledgements')); ?>">Acknowledgements</a></h2>
    <p>We have a page <a href="<?php print(full_url('acknowledgements')); ?>">acknowledging support</a>. If you have participated in the Stacks project under a grant you can contact <a href="mailto:stacks.project@gmail.com">stacks.project@gmail.com</a> and it will be added to this file.

    <h2><a href="https://github.com/pbelmans/stacks-website">Stacks project website</a></h2>
    <p>This website is maintained and developed as a <a href="https://github.com/pbelmans/stacks-website">separate project</a>. If you wish to start your own project, inspired by the Stacks project the combination of <a href="https://github.com/stacks/stacks-project"><code>stacks-project</code></a> and <a href="https://github.com/pbelmans/stacks-website"><code>stacks-website</code></a> can serve as a starting point. If you have any questions about this, send an email to <a href="mailto:stacks.project@gmail.com">stacks.project@gmail.com</a>.

    <p id="backlink">Back to the <a href="<?php print(full_url('')); ?>">main page</a>.
  </body>
</html>
