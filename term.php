<?php
require_once __DIR__ . "/auth.php";
require_page_access("term");

$message = "";
$messageType = "success";

// ADD TERM
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_term"])) {
    $termName = trim($_POST["term_name"] ?? "");
    $status = $_POST["status"] ?? "Active";

    if ($termName === "") {
        $message = "Please enter a term name.";
        $messageType = "error";
    } elseif (!in_array($status, ["Active", "Inactive"], true)) {
        $message = "Invalid status.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO tbl_term (TERM_NAME, STATUS) VALUES (?, ?)"
        );
        $stmt->bind_param("ss", $termName, $status);
        $stmt->execute();
        $stmt->close();

        header("Location: term.php?success=added");
        exit();
    }
}

// UPDATE TERM
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_term"])) {
    $id = (int)($_POST["id"] ?? 0);
    $termName = trim($_POST["term_name"] ?? "");
    $status = $_POST["status"] ?? "Active";

    if (
        $id <= 0 || $termName === "" ||
        !in_array($status, ["Active", "Inactive"], true)
    ) {
        $message = "Invalid term information.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare(
            "UPDATE tbl_term SET TERM_NAME = ?, STATUS = ? WHERE ID = ?"
        );
        $stmt->bind_param("ssi", $termName, $status, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: term.php?success=updated");
        exit();
    }
}

// DELETE TERM
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_term"])) {
    $id = (int)($_POST["id"] ?? 0);

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM tbl_term WHERE ID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: term.php?success=deleted");
    exit();
}

// SUCCESS MESSAGES
if (isset($_GET["success"])) {
    $messages = [
        "added" => "Term added successfully.",
        "updated" => "Term updated successfully.",
        "deleted" => "Term deleted successfully."
    ];

    $message = $messages[$_GET["success"]] ?? "";
}

// FETCH TERMS
$terms = $conn->query(
    "SELECT ID, TERM_NAME, STATUS FROM tbl_term ORDER BY ID DESC"
);

$editTerm = null;

if (isset($_GET["edit"])) {
    $editId = (int)$_GET["edit"];

    $stmt = $conn->prepare("SELECT * FROM tbl_term WHERE ID = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editTerm = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Term Management</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .module-container {
            padding: 30px;
        }

        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .module-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, .05);
        }

        .module-form {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .module-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .module-form input,
        .module-form select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
        }

        .module-table {
            width: 100%;
            border-collapse: collapse;
        }

        .module-table th,
        .module-table td {
            padding: 14px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .module-table th {
            background: #f8f9fb;
        }

        .status-active {
            color: #16834a;
            background: #e7f7ee;
            padding: 5px 12px;
            border-radius: 20px;
        }

        .status-inactive {
            color: #777;
            background: #eee;
            padding: 5px 12px;
            border-radius: 20px;
        }

        .action-link {
            margin-right: 10px;
            color: #3157d5;
            text-decoration: none;
            font-weight: 600;
        }

        .delete-btn {
            border: none;
            background: none;
            color: #c70808;
            font-weight: 600;
            cursor: pointer;
        }

        .module-alert {
            padding: 13px;
            border-radius: 7px;
            background: #e7f7ee;
            color: #16834a;
            margin-bottom: 20px;
        }

        .module-alert.error {
            background: #fdeaea;
            color: #c70808;
        }

        .table-responsive {
            overflow-x: auto;
        }

        @media(max-width: 700px) {
            .module-container {
                padding: 15px;
            }

            .module-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <?php
    // Include your existing navigation if available.
    if (file_exists(__DIR__ . "/navigation.php")) {
        require_once __DIR__ . "/navigation.php";
    }
    ?>

    <main class="module-container">

        <div class="module-header">
            <div>
                <h1>Term Management</h1>
                <p>Manage academic terms and their current status.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="module-alert <?= $messageType === "error" ? "error" : "" ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <section class="module-card">
            <h2><?= $editTerm ? "Edit Academic Term" : "Add Academic Term" ?></h2>

            <form method="POST" class="module-form">

                <?php if ($editTerm): ?>
                    <input type="hidden" name="id" value="<?= (int)$editTerm["ID"] ?>">
                <?php endif; ?>

                <div>
                    <label for="term_name">Term Name</label>
                    <input
                        type="text"
                        id="term_name"
                        name="term_name"
                        placeholder="Example: First Semester"
                        value="<?= e($editTerm["TERM_NAME"] ?? "") ?>"
                        required>
                </div>

                <div>
                    <label for="status">Status</label>
                    <select name="status" id="status" required>
                        <option value="Active"
                            <?= ($editTerm["STATUS"] ?? "Active") === "Active" ? "selected" : "" ?>>
                            Active
                        </option>
                        <option value="Inactive"
                            <?= ($editTerm["STATUS"] ?? "") === "Inactive" ? "selected" : "" ?>>
                            Inactive
                        </option>
                    </select>
                </div>

                <div>
                    <?php if ($editTerm): ?>
                        <button class="btn" type="submit" name="update_term">
                            Update Term
                        </button>
                        <a href="term.php">Cancel</a>
                    <?php else: ?>
                        <button class="btn" type="submit" name="add_term">
                            Add Term
                        </button>
                    <?php endif; ?>
                </div>

            </form>
        </section>

        <section class="module-card">
            <h2>Academic Terms</h2>

            <div class="table-responsive">
                <table class="module-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Term Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($terms->num_rows > 0): ?>
                            <?php while ($term = $terms->fetch_assoc()): ?>
                                <tr>
                                    <td><?= (int)$term["ID"] ?></td>

                                    <td><?= e($term["TERM_NAME"]) ?></td>

                                    <td>
                                        <span class="<?= $term["STATUS"] === "Active" ? "status-active" : "status-inactive" ?>">
                                            <?= e($term["STATUS"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a class="action-link"
                                            href="term.php?edit=<?= (int)$term["ID"] ?>">
                                            Edit
                                        </a>

                                        <form method="POST"
                                            style="display:inline"
                                            onsubmit="return confirm('Delete this term?');">

                                            <input type="hidden" name="id"
                                                value="<?= (int)$term["ID"] ?>">

                                            <button type="submit"
                                                name="delete_term"
                                                class="delete-btn">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No academic terms found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>
        </section>

    </main>

</body>

</html>