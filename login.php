<?php
require "db.php";
$message = "";

if (isset($_POST["submit-btn"])) {
  $username = trim($_POST["usernamelog"] ?? "");
  $password = $_POST["passwordlog"] ?? "";

  $stmt = $conn->prepare("SELECT ID, USERNAME, PASSWORD, USERTYPE FROM tbl_account WHERE USERNAME = ? LIMIT 1");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row["PASSWORD"])) {
      session_regenerate_id(true);
      $_SESSION["USER_ID"] = (int)$row["ID"];
      $_SESSION["USERNAME"] = $row["USERNAME"];
      $_SESSION["USERTYPE"] = $row["USERTYPE"];
      header("Location: tblcourse.php");
      exit();
    }
  }
  $message = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | Course Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .auth-foot a {
      color: rgb(199, 8, 8);
      font-weight: 700;
      text-decoration: none;
    }

    .auth-foot a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body class="auth-page">
  <div class="auth">
    <section class="auth-card">
      <aside class="auth-side">
        <div class="brand-mark">CP</div>
        <p class="eyebrow">COURSE MANAGEMENT SYSTEM</p>
        <h1>Learning starts with the right tools.</h1>
        <p class="side-copy">Sign in to access your courses and the tools available for your user role.</p>
        <div class="side-note">Simple. Organized. Secure.</div>
      </aside>

      <main class="auth-form">
        <h2>Welcome back</h2>
        <p class="sub">Enter your account details to continue.</p>

        <?php if ($message): ?>
          <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="on">
          <div class="field">
            <label for="usernamelog">Username</label>
            <input id="usernamelog" type="text" name="usernamelog" autocomplete="username" required>
          </div>

          <div class="field">
            <label for="pw">Password</label>
            <div class="password-wrap">
              <input id="pw" type="password" name="passwordlog" autocomplete="current-password" required>
              <button class="toggle" type="button" onclick="togglePassword('pw',this)" aria-label="Show password">Show</button>
            </div>
          </div>

          <button class="btn" type="submit" name="submit-btn">
            Sign in <span aria-hidden="true">→</span>
          </button>
        </form>

        <p class="auth-foot">
          Don't have an account?
          <a href="register.php">Create Account</a>
        </p>
      </main>
    </section>
  </div>

  <script>
    function togglePassword(id, button) {
      const input = document.getElementById(id);
      input.type = input.type === "password" ? "text" : "password";
      button.textContent = input.type === "password" ? "Show" : "Hide";
    }
  </script>
</body>

</html>
```