<?php
require "db.php";
require_once "auth.php";
require_login();
require_page_access("subjects");

$message = "";
if (isset($_GET["delete"])) {
    if (current_role() !== "Admin") {
        http_response_code(403);
        exit("Only Admin can delete courses.");
    }
    $id = (int)$_GET["delete"];
    $stmt = $conn->prepare("DELETE FROM tbl_course WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: tblcourse.php");
    exit();
}

if (isset($_POST["add-btn"])) {
    if (current_role() !== "Admin") {
        http_response_code(403);
        exit("Only Admin can add courses.");
    }
    $code = trim($_POST["code"] ?? "");
    $courseName = trim($_POST["name"] ?? "");
    $units = (int)($_POST["units"] ?? 3);
    if ($code !== "" && $courseName !== "" && $units >= 1 && $units <= 9) {
        $stmt = $conn->prepare("INSERT INTO tbl_course (COURSE_CODE, COURSE_NAME, UNITS) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $code, $courseName, $units);
        if (!$stmt->execute()) {
            $message = "Could not add course. Check whether the course code already exists.";
        } else {
            header("Location: tblcourse.php");
            exit();
        }
    } else {
        $message = "Please complete all fields correctly.";
    }
}

$courses = $conn->query("SELECT * FROM tbl_course ORDER BY COURSE_CODE");
$total = $courses ? $courses->num_rows : 0;
$unitsRow = $conn->query("SELECT COALESCE(SUM(UNITS),0) AS u FROM tbl_course")->fetch_assoc();
$unitsTotal = (int)$unitsRow["u"];
$usersRow = $conn->query("SELECT COUNT(*) AS c FROM tbl_account")->fetch_assoc();
$userTotal = (int)$usersRow["c"];
$name = $_SESSION["USERNAME"];
$role = $_SESSION["USERTYPE"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Subjects | Course Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="app-page">
<header class="nav">
  <a class="brand" href="tblcourse.php"><span class="brand-mark small">CP</span><span>Course Portal</span></a>
  <div class="nav-right"><span class="role-pill"><?= htmlspecialchars($role) ?></span><span class="user-name"><?= htmlspecialchars($name) ?></span><a class="logout" href="logout.php">Log out</a></div>
</header>
<div class="app-layout">
  <aside class="sidebar">
    <p class="side-label">MAIN MENU</p>
    <a class="side-link active" href="tblcourse.php"><span>▦</span> Subjects</a>
    <?php if (can_access_page("term")): ?><a class="side-link" href="term.php"><span>◷</span> Term</a><?php endif; ?>
    <?php if (can_access_page("instructor")): ?><a class="side-link" href="Instructor.php"><span>♙</span> Instructor</a><?php endif; ?>
    <?php if (can_access_page("school_year")): ?><a class="side-link" href="schoolyear.php"><span>▤</span> School Year</a><?php endif; ?>
    <?php if (can_access_page("section")): ?><a class="side-link" href="section.php"><span>▣</span> Section</a><?php endif; ?>
    <?php if (can_access_page("year_level")): ?><a class="side-link" href="yearlevel.php"><span>▥</span> Year Level</a><?php endif; ?>
    <?php if (can_access_page("students")): ?><a class="side-link" href="students.php"><span>♧</span> Students</a><?php endif; ?>
    <?php if (can_access_page("accounts")): ?><a class="side-link" href="accounts.php"><span>⚙</span> Accounts</a><?php endif; ?>
    <div class="sidebar-bottom"><span class="online-dot"></span> Signed in as <?= htmlspecialchars($role) ?></div>
  </aside>
  <main class="main-content">
    <div class="page-heading"><div><p class="eyebrow dark">ACADEMIC MANAGEMENT</p><h1>Subjects</h1><p class="muted">Manage and review the course catalog.</p></div><div class="date-chip"><?= date("M d, Y") ?></div></div>
    <?php if ($message): ?><div class="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <section class="stats">
      <article class="stat"><div class="stat-icon blue">▦</div><div><span class="stat-label">Total subjects</span><strong><?= $total ?></strong></div></article>
      <article class="stat"><div class="stat-icon violet">◈</div><div><span class="stat-label">Total units</span><strong><?= $unitsTotal ?></strong></div></article>
      <?php if ($role === "Admin"): ?><article class="stat"><div class="stat-icon green">♙</div><div><span class="stat-label">User accounts</span><strong><?= $userTotal ?></strong></div></article><?php endif; ?>
    </section>
    <?php if ($role === "Admin"): ?>
    <section class="panel">
      <div class="panel-heading"><div><h2>Add subject</h2><p class="muted">Enter the subject code, title, and unit count.</p></div></div>
      <form method="post" class="add-form">
        <div class="field"><label for="code">Subject code</label><input id="code" name="code" placeholder="e.g. IT107" required maxlength="30"></div>
        <div class="field"><label for="name">Subject name</label><input id="name" name="name" placeholder="Enter subject title" required maxlength="150"></div>
        <div class="field"><label for="units">Units</label><input id="units" type="number" name="units" value="3" min="1" max="9" required></div>
        <button class="btn" type="submit" name="add-btn">+ Add subject</button>
      </form>
    </section>
    <?php else: ?>
    <div class="notice">You have view-only access to the subject list.</div>
    <?php endif; ?>
    <section class="panel">
      <div class="panel-heading list-heading"><div><h2>Subject catalog</h2><p class="muted">All available subjects and their units.</p></div><label class="search-wrap"><span>⌕</span><input class="search" id="q" placeholder="Search subjects..." aria-label="Search subjects"></label></div>
      <div class="table-wrap"><table id="tbl"><thead><tr><th>#</th><th>Subject code</th><th>Subject name</th><th>Units</th><?php if ($role === "Admin"): ?><th>Action</th><?php endif; ?></tr></thead><tbody>
      <?php $n=1; while ($c = $courses->fetch_assoc()): ?>
      <tr><td class="row-number"><?= $n++ ?></td><td><span class="badge"><?= htmlspecialchars($c["COURSE_CODE"]) ?></span></td><td class="course-name"><?= htmlspecialchars($c["COURSE_NAME"]) ?></td><td><span class="units"><?= (int)$c["UNITS"] ?> units</span></td><?php if ($role === "Admin"): ?><td><a class="del" href="?delete=<?= (int)$c["ID"] ?>" onclick="return confirm('Delete this subject?')">Delete</a></td><?php endif; ?></tr>
      <?php endwhile; ?>
      </tbody></table>
      <?php if ($total === 0): ?><div class="empty">No subjects found.<?php if ($role === "Admin"): ?> Add the first subject above.<?php endif; ?></div><?php endif; ?>
      </div>
    </section>
    <footer class="footer">Course Portal · Role-based academic management</footer>
  </main>
</div>
<script>
const search = document.getElementById("q");
search.addEventListener("input", function () {
  const value = this.value.toLowerCase();
  document.querySelectorAll("#tbl tbody tr").forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(value) ? "" : "none";
  });
});
</script>
</body>
</html>
