<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = parse_ini_file("config.ini");

function isValidTag($tag) {
  return preg_match_all('/^[[:alnum:]]{4}$/', $tag, $matches) === 1;
}

try {
  $database = new PDO("sqlite:" . $config["database"]);
  $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e) {
  print "Something went wrong with the database. If the problem persists, please contact us at <a href='mailto:stacks.project@gmail.com'>stacks.project@gmail.com</a>.";
  // if there is actually a persistent error: add output code here to check it
  exit();
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

function printBackLink() {
  print("<br><a href='#' onclick='history.go(-2);'>go back</a>");
}



if (isset($_POST["skip"])) {
  header("Location: input.php");
}
elseif (isset($_POST["submit"])) {
  // submit the slogan

  // if this triggers the user is messing with the POST request
  if (!isset($_POST['tag']) or !isValidTag($_POST['tag'])) {
    print('The tag your browser supplied in the request is not in a valid format.');
    printBackLink();
    exit();
  }

  if ($_POST['tag'] !== $_POST['check']) {
    print('You did not pass the captcha. Please go back and fill in the correct tag to prove you are not a computer.');
    printBackLink();
    exit();
  }

  // the tag is not present in the database, when we start handling removed tags this will have to change
  if (!tagExists($_POST['tag'])) {
    print('The tag you are trying to post a comment on does not exist.');
    printBackLink();
    exit();
  }

  // empty author
  if (empty($_POST['name'])) {
    print('You must supply your name.');
    printBackLink();
    exit();
  }

  // empty email
  if (empty($_POST['email'])) {
    print('You must supply your email address. Remark that it will not be posted.');
    printBackLink();
    exit();
  }

  // nonempty email, but the format is wrong
  if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    print('You must supply a correctly formatted email address. Your current input is ' . $_POST['email']);
    printBackLink();
    exit();
  }

  // from here on it's safe to ignore the fact that it's user input
  $tag = $_POST['tag'];
  $author = $_POST['name'];
  $email = $_POST['email'];
  $slogan = htmlspecialchars($_POST['slogan']);
  // for some reason Firefox is inserting &nbsp;'s in the input when you have two consecutive spaces, we don't like that
  $slogan = str_replace('&nbsp;', ' ', $slogan);

  try {
    $sql = $database->prepare('INSERT INTO slogans (tag, author, slogan, email) VALUES (:tag, :author, :slogan, :email)');
    $sql->bindParam(':tag', $tag);
    $sql->bindParam(':author', $author);
    $sql->bindParam(':slogan', $slogan);
    $sql->bindParam(':email', $email);

    if(!$sql->execute()) {
      print("Something went wrong with your slogan.\n");
      print_r($sql->errorInfo());
      exit();
    }
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  session_start();
  $_SESSION["tag"] = $tag;

  header('Location: input.php');
}
?>
