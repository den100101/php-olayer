<?php
require "db.php";

$message = "";
$success = false;

if (isset($_POST["create-account"])) {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $usertype = $_POST["usertype"] ?? "";

    $allowedRoles = [
        "Admin",
        "Registrar",
        "Dean",
        "Instructor",
        "Student"
    ];

    if ($username === "" || $password === "" || $confirmPassword === "" || $usertype === "") {
        $message = "Please fill in all fields.";
    } elseif (strlen($username) > 50) {
        $message = "Username must not exceed 50 characters.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } elseif (!in_array($usertype, $allowedRoles, true)) {
        $message = "Invalid user type.";
    } else {
        $check = $conn->prepare(
            "SELECT ID FROM tbl_account WHERE USERNAME = ? LIMIT 1"
        );
        $check->bind_param("s", $username);
        $check->execute();

        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($exists) {
            $message = "Username already exists.";
        } else {
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO tbl_account (USERNAME, PASSWORD, USERTYPE)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param(
                "sss",
                $username,
                $hashedPassword,
                $usertype
            );

            if ($stmt->execute()) {
                $success = true;
                $message = "Account created successfully! You can now log in.";
            } else {
                $message = "Unable to create account. Please try again.";
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account | Course Portal</title>

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

        .register-alert {
            padding: 12px 14px;
            margin-bottom: 16px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.5;
        }

        .register-alert.error {
            color: #991b1b;
            background: #fee2e2;
        }

        .register-alert.success {
            color: #166534;
            background: #dcfce7;
        }

        .auth-form .btn {
            width: 100%;
        }

        .field select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font: inherit;
            background: #fff;
        }
    </style>
</head>

<body class="auth-page">
    <div class="auth">
        <section class="auth-card">

            <aside class="auth-side">
                <div class="brand-mark">CP</div>
                <p class="eyebrow">COURSE MANAGEMENT SYSTEM</p>
                <h1>Create your account and get started.</h1>
                <p class="side-copy">
                    Register an account to access the tools available
                    for your assigned user role.
                </p>
                <div class="side-note">Simple. Organized. Secure.</div>
            </aside>

            <main class="auth-form">
                <h2>Create Account</h2>
                <p class="sub">Enter your details to register.</p>

                <?php if ($message !== ""): ?>
                    <div class="register-alert <?= $success ? 'success' : 'error' ?>">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="on">
                    <div class="field">
                        <label for="username">Username</label>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            maxlength="50"
                            autocomplete="username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            required>
                    </div>

                    <div class="field">
                        <label for="usertype">User Type</label>
                        <select id="usertype" name="usertype" required>
                            <option value="">Select user type</option>
                            <?php
                            $roles = ["Admin", "Registrar", "Dean", "Instructor", "Student"];
                            foreach ($roles as $role):
                            ?>
                                <option
                                    value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= (($_POST['usertype'] ?? '') === $role) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="password-wrap">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                minlength="8"
                                autocomplete="new-password"
                                required>
                            <button
                                class="toggle"
                                type="button"
                                onclick="togglePassword('password', this)">Show</button>
                        </div>
                    </div>

                    <div class="field">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-wrap">
                            <input
                                id="confirm_password"
                                type="password"
                                name="confirm_password"
                                minlength="8"
                                autocomplete="new-password"
                                required>
                            <button
                                class="toggle"
                                type="button"
                                onclick="togglePassword('confirm_password', this)">Show</button>
                        </div>
                    </div>

                    <button
                        class="btn"
                        type="submit"
                        name="create-account"
                        value="1">
                        Create Account <span aria-hidden="true">→</span>
                    </button>
                </form>

                <p class="auth-foot">
                    Already have an account?
                    <a href="login.php">Sign in</a>
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