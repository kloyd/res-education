<?php
require_once "pdo.php";
require_once "util.php";

session_start();

if (! isset($_SESSION['user_id'])) {
  die("ACCESS DENIED");
  return;
}

if ( isset($_SESSION['name'])) {
  $name = $_SESSION['name'];
} else {
  die('Not logged in.');
  return;
}

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
    header('Location: add.php');
    return;
  }

  $msg = validatePos();
  if (is_string($msg)) {
    $_SESSION['error'] = $msg;
    header('Location: add.php');
    return;
  }

  // Data is valid, time to insert.
  $stmt = $pdo->prepare('INSERT INTO Profile
      (user_id, first_name, last_name, email, headline, summary)
      VALUES ( :uid, :fn, :ln, :em, :he, :su)');

  $stmt->execute(array(
    ':uid' => $_SESSION['user_id'],
    ':fn' => $_POST['first_name'],
    ':ln' => $_POST['last_name'],
    ':em' => $_POST['email'],
    ':he' => $_POST['headline'],
    ':su' => $_POST['summary'])
  );
  $profile_id = $pdo->lastInsertId();

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
      ':pid' => $profile_id,
      ':rank' => $rank,
      ':year' => $year,
      ':desc' => $desc)
    );
    $rank++;
  }
  // now redirect to view.php
  $_SESSION['success'] = "New Profile added";
  header('Location: index.php');
  return;
}

?>
<html>
<head>
<title>Kelly Loyd's Profile Add</title>
<?php require_once "head.php"; ?>
</head>
<body>
  <div class="container">
    <?php flashMessages(); ?>
  <?php echo("<h1>Adding Profile for $name</h1>\n"); ?>
  <form method="post">
  <p>First Name:
  <input type="text" name="first_name" size="60"/></p>
  <p>Last Name:
  <input type="text" name="last_name" size="60"/></p>
  <p>Email:
  <input type="text" name="email" size="30"/></p>
  <p>Headline:<br/>
  <input type="text" name="headline" size="80"/></p>
  <p>Summary:<br/>
  <textarea name="summary" rows="8" cols="80"></textarea>
  <p>
  Education: <input type="submit" id="addEdu" value="+">
  <div id="education_fields">
  </div>
</p>
  <p>
  Position: <input type="submit" id="addPos" value="+">
  <div id="position_fields">
  </div>
</p>
<p>
  <input type="submit" value="Add">
  <input type="submit" name="cancel" value="Cancel">
  </p>
</form>
<script>
countPos = 0;
countEdu = 0;

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

</body>
</html>
