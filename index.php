<?php
require_once "pdo.php";
require_once "util.php";

session_start();


?>

<!DOCTYPE html>
<html>
<head>
<title>Kelly Loyd's Resume Registry</title>
<?php require_once "head.php"; ?>
</head>
<body>
  <?php
  flashMessages();
 ?>
<div class="container">
<h1>Kelly Loyd's Resume Registry</h1>
<?php

  if ( isset($_SESSION['name']) ) {
    $logged_in = true;
  } else {
    echo('<p><a href="login.php">Please log in</a></p>');
    # echo('<p>Attempt to <a href="add.php">add data</a> without logging in</p>');
    $logged_in = false;
  }

  # profile table: profile_id, user_id, first_name, last_name, email, headline, summary
  $stmtcount = $pdo->query("SELECT count(*) from profile");
  $countrows = $stmtcount->fetch();
  $rowcount = intval($countrows[0]);
  if ($rowcount != 0) {
    $stmt = $pdo->query("SELECT profile_id, first_name, last_name, headline from profile");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo('<table border="1">');
    echo("<tr><td><b>Name</b></td><td><b>Headline</b></td>");
    if ($logged_in === true) {
      echo("<td><b>Action</b></td>");
    }
    echo("</tr>\n");
    foreach ( $rows as $row ) {
      echo "<tr><td>";
      echo('<a href="view.php?profile_id=' . $row['profile_id'] .'">');
      echo(htmlentities($row['first_name'] . ' ' . $row['last_name']));
      echo("</a>");
      echo("</td><td>");
      echo(htmlentities($row['headline']));

      if ($logged_in === true) {
        echo("</td><td>");
        echo('<a href="edit.php?profile_id='.$row['profile_id'].'">Edit</a> / ');
        echo('<a href="delete.php?profile_id='.$row['profile_id'].'">Delete</a>');
      }
      echo("</td></tr>\n");
    }
    echo('</table>');
  }
  if ($logged_in === true) {
    echo('<a href="add.php">Add New Entry</a><br> ');
    echo('<a href="logout.php">Logout</a>');
  }


?>

</div>
</body>
</html>
