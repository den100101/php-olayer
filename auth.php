<?php
require_once __DIR__ . "/db.php";

function current_role(): string
{
    return $_SESSION["USERTYPE"] ?? "";
}

function require_login(): void
{
    if (empty($_SESSION["USERNAME"]) || empty($_SESSION["USERTYPE"])) {
        header("Location: login.php");
        exit();
    }
}

/*
 * Role access map based on the assignment.
 * "Subjects" maps to tblcourse.php in this starter project.
 */
function can_access_page(string $page): bool
{
    require_login();

    $role = current_role();
    $permissions = [
        "Admin" => ["subjects", "term", "instructor", "school_year", "section", "year_level", "students", "accounts"],
        "Registrar" => ["subjects", "term", "instructor", "school_year"],
        "Dean" => ["subjects", "section", "instructor", "year_level"],
        "Instructor" => ["subjects", "section"],
        "Student" => ["subjects"],
    ];

    return isset($permissions[$role]) && in_array($page, $permissions[$role], true);
}

function require_page_access(string $page): void
{
    if (!can_access_page($page)) {
        http_response_code(403);
        exit("403 Forbidden: Your user type is not allowed to access this page.");
    }
}
