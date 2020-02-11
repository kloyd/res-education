<?php
require_once "pdo.php";
require_once "util.php";

session_start();

checkLoggedIn();

if ( isset($_POST['cancel'] ) ) {
    // cancel sends back to index
    header("Location: index.php");
    return;
}

if ( isset($_POST['first_name']) && isset($_POST['last_name'])
    && isset($_POST['email']) && isset($_POST['headline'])
    && isset($_POST['summary'])) {

    $msg = validateProfile();
    if (is_string($msg)) {
      $_SESSION['error'] = $msg;
      header('Location: edit.php');
      return;
    }

    $msg = validatePos();
    if (is_string($msg)) {
      $_SESSION['error'] = $msg;
      header('Location: edit.php');
      return;
    }

    $msg = validateEdu();
    if (is_string($msg)) {
      $_SESSION['error'] = $msg;
      header('Location: edit.php');
      return;
    }

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
    $stmt->execute(array(':pid' => $_REQUEST['profile_id']));
    // Insert the position entries.
    insertPositions($pdo, $_REQUEST['profile_id']);

    $stmt = $pdo->prepare('DELETE FROM education
        WHERE profile_id = :pid');
    $stmt->execute(array(':pid' => $_REQUEST['profile_id']));
    // Insert the education entries.
    insertEducations($pdo, $_REQUEST['profile_id']);

    // now redirect to index.php
    $_SESSION['success'] = "Profile updated";
    header('Location: index.php');
    return;
}

// Guardian: Make sure that profile_id is present
if ( ! isset($_GET['profile_id']) ) {
  $_SESSION['error'] = "Missing profile_id";
  header('Location: index.php');
  return;
}

$stmt = $pdo->prepare('SELECT * FROM profile
where profile_id = :prof
and user_id = :uid');
$stmt->execute(array(':prof' => $_GET['profile_id'],
                      ':uid' => $_SESSION['user_id']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ( $row === false ) {
    $_SESSION['error'] = 'Cannot find Profile';
    header( 'Location: index.php' ) ;
    return;
}

$positions = loadPos($pdo, $_REQUEST['profile_id']);
$schools = loadEdu($pdo, $_REQUEST['profile_id']);

// Flash pattern
flashMessages();

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
  <h1>Editing Profile for <?= $_SESSION['name']; ?></h1>
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
    <?php $countEdu = 0;  ?>
    Education: <input type="submit" id="addEdu" value="+">
    <div id="education_fields">
    <?php if ( count($schools) > 0) {
       foreach ($schools as $edu) {
         $countEdu++; ?>
         <div id="education<?= $countEdu ?>">
         <p>Year: <input type="text" name="edu_year<?= $countEdu ?>" value="<?= $edu['year'] ?>" />
         <input type="button" value="-"
             onclick="$('#education<?= $countEdu ?>').remove();return false;"></p>
         <p>School: <input class ="school" type="text" size="80" name="edu_school<?= $countEdu ?>"
         class="school" value="<?= htmlentities($edu['name']) ?>" />
         </p></div>
      <?php }
    } ?>
    </div>

  <?php $countPos = 0;  ?>
  Position: <input type="submit" id="addPos" value="+">
  <div id="position_fields">
  <?php if ( count($positions) > 0) {
     foreach ($positions as $pos) {
       $countPos++; ?>
    <div class="position" id="position<?= $countPos ?>">
      <p>Year: <input type="text" name="year<?= $countPos ?>" value="<?= $pos['year'] ?>">
      <input type="button" value="-" onclick="$('#position<?= $countPos ?>').remove();return false;"></p>
      <textarea name="desc<?= $countPos ?>" rows="8" cols="80"><?= htmlentities($pos['description']) ?></textarea>
    </div>
    <?php }
  } ?>
  </div>
  <input type="hidden" name="profile_id" value="<?= $profile_id ?>">
  <p>
    <input type="submit" value="Save" />
    <input type="submit" name="cancel" value="Cancel">
  </p>
</form>
<script>
countPos = <?= $countPos ?>;
countEdu = <?= $countEdu ?>;
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

    $('#addEdu').click(function(event){
      event.preventDefault();
      if (countEdu >= 9) {
        alert("Maximum of nine position entries exceeded");
        return;
      }
      countEdu++;
      window.console && console.log("Adding education " + countEdu);

      $('#education_fields').append(
        '<div id="education'+countEdu+'"> \
        <p>Year: <input type="text" name="edu_year'+countEdu+'" value="" /> \
        <input type="button" value="-" \
            onclick="$(\'#education'+countEdu+'\').remove();return false;"></p> \
        <p>School: <input class ="school" type="text" size="80" name="edu_school'+countEdu+'" \
        class="school" value="" /> \
        </p></div>'
      );

      $('.school').autocomplete({
         source: "school.php"
       });

    });

});

</script>

</script>


</body>
</html>
