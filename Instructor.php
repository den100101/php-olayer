    <?php
    require "db.php";
    require_once "auth.php";
    require_login();
    require_page_access("instructor");

    $message = "";
    if (isset($_POST["add-instructor"])) {
        if (!in_array(current_role(), ["Admin", "Registrar"], true)) {
            http_response_code(403);
            exit("You are not allowed to add instructors.");
        }

        $salutation = trim($_POST["salutation"] ?? "");
        $first = trim($_POST["firstname"] ?? "");
        $middle = trim($_POST["middlename"] ?? "");
        $last = trim($_POST["lastname"] ?? "");

        if ($salutation === "" || $first === "" || $last === "") {
            $message = "Salutation, first name, and last name are required.";
        } else {
            $photo = null;
            if (!empty($_FILES["photo"]["name"])) {
                $allowed = ["jpg" => "image/jpeg", "jpeg" => "image/jpeg", "png" => "image/png", "webp" => "image/webp"];
                $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                $mime = mime_content_type($_FILES["photo"]["tmp_name"]);
                if (!isset($allowed[$ext]) || $allowed[$ext] !== $mime || $_FILES["photo"]["size"] > 2 * 1024 * 1024) {
                    $message = "Photo must be JPG, PNG, or WEBP and no larger than 2 MB.";
                } else {
                    $uploadDir = __DIR__ . "/uploads/instructors";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileName = bin2hex(random_bytes(12)) . "." . $ext;
                    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $uploadDir . "/" . $fileName)) {
                        $photo = "uploads/instructors/" . $fileName;
                    } else {
                        $message = "Could not upload the photo.";
                    }
                }
            }

            if ($message === "") {
                $stmt = $conn->prepare("INSERT INTO tbl_instructors (SALUTATION, FIRSTNAME, MIDDLENAME, LASTNAME, PHOTO) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $salutation, $first, $middle, $last, $photo);
                if ($stmt->execute()) {
                    header("Location: Instructor.php?added=1");
                    exit();
                }
                $message = "Could not save instructor. Please check the database.";
            }
        }
    }
    if (isset($_GET["added"])) $message = "Instructor added successfully.";

    if (isset($_GET["delete"])) {
        if (current_role() !== "Admin") {
            http_response_code(403);
            exit("Only Admin can delete instructors.");
        }
        $id = (int)$_GET["delete"];
        $stmt = $conn->prepare("SELECT PHOTO FROM tbl_instructors WHERE ID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $del = $conn->prepare("DELETE FROM tbl_instructors WHERE ID = ?");
        $del->bind_param("i", $id);
        $del->execute();
        if ($existing && !empty($existing["PHOTO"]) && strpos($existing["PHOTO"], "uploads/instructors/") === 0) {
            $path = __DIR__ . "/" . $existing["PHOTO"];
            if (is_file($path)) unlink($path);
        }
        header("Location: Instructor.php");
        exit();
    }

    $instructors = $conn->query("SELECT ID, SALUTATION, FIRSTNAME, MIDDLENAME, LASTNAME, PHOTO FROM tbl_instructors ORDER BY LASTNAME, FIRSTNAME");
    $role = current_role();
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Instructors | Course Portal</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
    </head>

    <body class="app-page">
        <header class="nav"><a class="brand" href="tblcourse.php"><span class="brand-mark small">CP</span><span>Course Portal</span></a>
            <div class="nav-right"><span class="role-pill"><?= htmlspecialchars($role) ?></span><span class="user-name"><?= htmlspecialchars($_SESSION["USERNAME"]) ?></span><a class="logout" href="logout.php">Log out</a></div>
        </header>
        <div class="app-layout">
            <aside class="sidebar">
                <p class="side-label">MAIN MENU</p><a class="side-link" href="tblcourse.php"><span>▦</span> Subjects</a><a class="side-link active" href="Instructor.php"><span>♙</span> Instructor</a>
                <div class="sidebar-bottom"><span class="online-dot"></span> Signed in as <?= htmlspecialchars($role) ?></div>
            </aside>
            <main class="main-content">
                <div class="page-heading">
                    <div>
                        <p class="eyebrow dark">ACADEMIC MANAGEMENT</p>
                        <h1>Instructors</h1>
                        <p class="muted">Manage instructor names and profile photos.</p>
                    </div>
                </div>
                <?php if ($message): ?><div class="<?= strpos($message, "successfully") !== false ? "notice" : "alert" ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <?php if (in_array($role, ["Admin", "Registrar"], true)): ?>
                    <section class="panel">
                        <div class="panel-heading">
                            <div>
                                <h2>Add instructor</h2>
                                <p class="muted">Fields marked with * are required.</p>
                            </div>
                        </div>
                        <form method="post" enctype="multipart/form-data" class="add-form instructor-form">
                            <div class="field"><label for="salutation">Salutation *</label><select id="salutation" name="salutation" required>
                                    <option value="">Select</option>
                                    <option>Dr.</option>
                                    <option>Mr.</option>
                                    <option>Ms.</option>
                                    <option>Mrs.</option>
                                    <option>Prof.</option>
                                </select></div>
                            <div class="field"><label for="firstname">First name *</label><input id="firstname" name="firstname" required maxlength="100"></div>
                            <div class="field"><label for="middlename">Middle name</label><input id="middlename" name="middlename" maxlength="100"></div>
                            <div class="field"><label for="lastname">Last name *</label><input id="lastname" name="lastname" required maxlength="100"></div>
                            <div class="field"><label for="photo">Photo (JPG/PNG/WEBP, max 2 MB)</label><input id="photo" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></div>
                            <button class="btn" type="submit" name="add-instructor">+ Add instructor</button>
                        </form>
                    </section>
                <?php else: ?><div class="notice">You have view-only access to instructors.</div><?php endif; ?>
                <section class="panel">
                    <div class="panel-heading list-heading">
                        <div>
                            <h2>Instructor directory</h2>
                            <p class="muted">The current instructor records.</p>
                        </div><label class="search-wrap"><span>⌕</span><input class="search" id="q" placeholder="Search instructors..." aria-label="Search instructors"></label>
                    </div>
                    <div class="table-wrap">
                        <table id="tbl">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Photo</th>
                                    <th>Salutation</th>
                                    <th>First name</th>
                                    <th>Middle name</th>
                                    <th>Last name</th><?php if ($role === "Admin"): ?><th>Action</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($i = $instructors->fetch_assoc()): ?><tr>
                                        <td><?= (int)$i["ID"] ?></td>
                                        <td><?php if (!empty($i["PHOTO"])): ?><img class="instructor-photo" src="<?= htmlspecialchars($i["PHOTO"]) ?>" alt="Instructor photo"><?php else: ?><span class="photo-placeholder">—</span><?php endif; ?></td>
                                        <td><?= htmlspecialchars($i["SALUTATION"]) ?></td>
                                        <td><?= htmlspecialchars($i["FIRSTNAME"]) ?></td>
                                        <td><?= htmlspecialchars($i["MIDDLENAME"] ?? "") ?></td>
                                        <td><?= htmlspecialchars($i["LASTNAME"]) ?></td><?php if ($role === "Admin"): ?><td><a class="del" href="?delete=<?= (int)$i["ID"] ?>" onclick="return confirm('Delete this instructor?')">Delete</a></td><?php endif; ?>
                                    </tr><?php endwhile; ?>
                            </tbody>
                        </table><?php if ($instructors->num_rows === 0): ?><div class="empty">No instructor records yet.</div><?php endif; ?>
                    </div>
                </section>
                <footer class="footer">Course Portal · Instructor directory</footer>
            </main>
        </div>
        <script>
            document.getElementById("q").addEventListener("input", function() {
                const value = this.value.toLowerCase();
                document.querySelectorAll("#tbl tbody tr").forEach(row => row.style.display = row.textContent.toLowerCase().includes(value) ? "" : "none");
            });
        </script>
    </body>

    </html>