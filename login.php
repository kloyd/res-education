<?php // Do not put any HTML above this line
require_once "pdo.php";
require_once "util.php";

session_start();

// if logged in, then redirect to index.php
if (isset($_SESSION['name'])) {
  header("Location: index.php");
  return;
}

if ( isset($_POST['cancel'] ) ) {
    // cancel sends back to index
    header("Location: index.php");
    return;
}

$salt = 'XyZzy12*_';
$failure = false;  // If we have no POST data

// Check to see if we have some POST data, if we do process it
if ( isset($_POST['email']) && isset($_POST['pass']) ) {
  if ( strlen($_POST['email']) < 1 || strlen($_POST['pass']) < 1 ) {
    $err = "User name and password are required.";
    $_SESSION['error'] = $err;
    error_log("Login fail ".$_POST['email']." $err");
    header("Location: login.php");
    return;
  } else {
    if (strpos($_POST['email'], '@') === false) {
      $err = "Email must have an at sign (@).";
      $_SESSION['error'] = $err;
      error_log("Login fail ".$_POST['email']." $err");
      header("Location: login.php");
      return;
    } else {
      #Since we are checking if the stored hashed password matches the hash computation of the user-provided password,
      # If we get a row, then the password matches, if we don't get a row (i.e. $row is false) then the password did not match.
      #If the password matches, put the user_id value for the user's row into session as well as the user's name:
      $check = hash('md5', $salt.$_POST['pass']);
      $stmt = $pdo->prepare('SELECT user_id, name FROM users
          WHERE email = :em AND password = :pw');
      $stmt->execute(array( ':em' => $_POST['email'], ':pw' => $check));
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if ( $row !== false ) {
        $_SESSION['name'] = $row['name'];
        $_SESSION['user_id'] = $row['user_id'];
        // Redirect the browser to index.php
        header("Location: index.php");
        return;
      } else {
        error_log("Login fail ".$_POST['email']." $check");
        $_SESSION['error'] = "Incorrect password";
        header("Location: login.php");
        return;
      }
    }
  }
}

// Fall through into the View
?>
<!DOCTYPE html>
<html>
<head>
<title>Kelly Loyd Profiles - Login</title>
<?php require_once "head.php"; ?>
</head>
<body>
<div class="container">
<h1>Please Log In</h1>
<?php flashMessages(); ?>
<form method="POST">
<label for="nam">Email Address</label>
<input type="text" name="email" id="email"><br/>
<label for="id_1723">Password</label>
<input type="password" name="pass" id="id_1723"><br/>
<input type="submit" onclick="return doValidate();" value="Log In">
<input type="submit" name="cancel" value="Cancel">
</form>
<p>
For a password hint, view source and find a password hint
in the HTML comments.
<!-- Hint: The password is the four character sound a cat
makes (all lower case) followed by 123. -->
</p>
<script>
function doValidate() {
  console.log('Validating...');
  try {
    addr = document.getElementById('email').value;
    pw = document.getElementById('id_1723').value;
    console.log("Validating addr="+addr+" pw="+pw);
    if (addr == null || addr == "" || pw == null || pw == "") {
      alert("Both fields must be filled out.");
      return false;
    }
    if (addr.indexOf('@') == -1 ) {
      alert("Invalid email address.");
      return false;
    }
    return true;
  } catch(e) {
    return false;
  }
  return false;
}
</script>
</div>
</body>
