<?php
require_once "pdo.php";
require_once "util.php";

session_start();

if ( isset($_SESSION['name'])) {
  $name = htmlentities($_SESSION['name']);
} else {
  die('Not logged in');
}

if ( isset($_POST['first_name']) && isset($_POST['last_name'])
    && isset($_POST['email']) && isset($_POST['headline'])
    && isset($_POST['summary'])) {

  if (strlen($_POST['first_name']) < 1 || strlen($_POST['last_name']) < 1 ||
      strlen($_POST['email']) < 1 || strlen($_POST['headline']) < 1
      || strlen($_POST['summary']) < 1) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: edit.php?profile_id=".$_POST['profile_id']);
        return;
  }

  if (strpos($_POST['email'], '@') !== false) {
      $sql = "update profile
                set first_name = :fn, last_name = :ln,
                email = :em, headline = :he, summary = :su
                where profile_id = :profile_id";
      $stmt = $pdo->prepare($sql);
      $stmt->execute(array(
        ':fn' => $_POST['first_name'],
        ':ln' => $_POST['last_name'],
        ':em' => $_POST['email'],
        ':he' => $_POST['headline'],
        ':su' => $_POST['summary'],
        ':profile_id' => $_POST['profile_id']));

        $stmt = $pdo->prepare('DELETE FROM position
            WHERE profile_id = :pid');
        $stmt->execute(array(':pid' => $_POST['profile_id']));
        // Insert the position entries.
        $rank = 1;
        for($i=1; $i<=9; $i++) {
          if (!isset($_POST['year'.$i])) continue;
          if (!isset($_POST['desc'.$i])) continue;
          $year = $_POST['year'.$i];
          $desc = $_POST['desc'.$i];

          $stmt = $pdo->prepare('INSERT INTO position
                (profile_id, rank, year, description)
                VALUES ( :pid, :rank, :year, :desc)');
          $stmt->execute(array(
            ':pid' => $_POST['profile_id'],
            ':rank' => $rank,
            ':year' => $year,
            ':desc' => $desc)
          );
          $rank++;
        }
        // now redirect to index.php
        $_SESSION['success'] = "Profile updated";
        header('Location: index.php');
        return;
    } else {
      $_SESSION['error'] = "email requires @ sign.";
      header("Location: edit.php?profile_id=".$_POST['profile_id']);
      return;
    }
}

// Guardian: Make sure that profile_id is present
if ( ! isset($_GET['profile_id']) ) {
  $_SESSION['error'] = "Missing profile_id";
  header('Location: index.php');
  return;
}

$stmt = $pdo->prepare("SELECT * FROM profile where profile_id = :xyz");
$stmt->execute(array(":xyz" => $_GET['profile_id']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ( $row === false ) {
    $_SESSION['error'] = 'Bad value for profile_id';
    header( 'Location: index.php' ) ;
    return;
}

$positions = loadPos($pdo, $_REQUEST['profile_id']);

// Flash pattern
if ( isset($_SESSION['error']) ) {
    echo '<p style="color:red">'.$_SESSION['error']."</p>\n";
    unset($_SESSION['error']);
}

$fn = htmlentities($row['first_name']);
$ln = htmlentities($row['last_name']);
$em = htmlentities($row['email']);
$he = htmlentities($row['headline']);
$su = htmlentities($row['summary']);
$profile_id = $row['profile_id'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Kelly Loyd's Profile Edit</title>
<?php require_once "head.php"; ?>
</head>
<body>
  <div class="container">
  <h1>Editing Profile for <?= $name; ?></h1>
  <form method="post">
  <p>First Name:
  <input type="text" name="first_name" size="60" value="<?= $fn ?>"/></p>
  <p>Last Name:
  <input type="text" name="last_name" size="60" value="<?= $ln ?>"/></p>
  <p>Email:
  <input type="text" name="email" size="30" value="<?= $em ?>"/></p>
  <p>Headline:<br/>
  <input type="text" name="headline" size="80" value="<?= $he ?>"/></p>
  <p>Summary:<br/>
  <textarea name="summary" rows="8" cols="80"><?= $su ?></textarea>
  <p>
  Position: <input type="submit" id="addPos" value="+">
  <div id="position_fields">
    <?php $rank = 1;
     foreach ($positions as $pos) { ?>
    <div id="position<?= $rank ?>">
      <p>Year: <input type="text" name="year<?= $rank ?>" value="<?= $pos['year'] ?>">
      <input type="button" value="-" onclick="$('#position<?= $rank ?>').remove();return false;"></p>
      <textarea name="desc<?= $rank ?>" rows="8" cols="80"><?= $pos['description'] ?></textarea>
    </div>
    <?php  $rank++;
    } ?>
  </div>
  <input type="hidden" name="profile_id" value="<?= $profile_id ?>">
  <p>
    <input type="submit" value="Save" />
    <a href="index.php">Cancel</a>
  </p>
</form>
<script>
countPos = <?= $rank - 1?>;

// http://stackoverflow.com/questions/17650776/add-remove-html-inside-div-using-javascript
$(document).ready(function(){
  window.console && console.log('Document ready called.');
  $('#addPos').click(function(event){
    event.preventDefault();
    if (countPos >= 9) {
      alert("Maximum of nine position entries exceeded");
      return;
    }
    countPos++;
    window.console && console.log("Adding position " + countPos);
    $('#position_fields').append(
      '<div id="position'+countPos+'"> \
      <p>Year: <input type="text" name="year'+countPos+'" value="" /> \
      <input type="button" value="-" \
          onclick="$(\'#position'+countPos+'\').remove();return false;"></p> \
      <textarea name="desc'+countPos+'" rows="8" cols="80"></textarea> \
      </div>');
    });
});

</script>


</body>
</html>
