
<?php
require_once 'php/init_session.php';
include 'php/controller.php';   
include 'php/db.php';
// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: html/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmark Manager</title>
    <link rel="stylesheet" href="../shared/includes.css">
    <link rel="stylesheet" href="stylesheet/styles.css">
    <script src="javascript/scripts.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>
    <header>
        <div class="header-left">
            <div class="logo">
                <h1>Bookmark Manager</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="../tma2.html">Home</a></li>
                    <li><a href="html/documentation.html">Documentation</a></li>
                    <?php if (!isset($_SESSION['username'])): ?>
                        <li><a href="html/login.php">Login</a></li>
                        <li><a href="html/register.php">Register</a></li>
                    <?php else: ?>

                        <li><a href="php/logout.php">Logout</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php if (isset($_SESSION['username'])): ?>
            <p class="logged-in-user">Logged in as: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
        <?php endif; ?>
    </header>

    <main>

        <h1>Welcome to Bookmark Manager</h1>

        <section id="popular-websites">
            <h2>Top 10 Popular Bookmarks</h2>
            <ul id="popular-list">
                <?php foreach ($popularBookmarks as $bookmark): ?>
                    <li>
                        <a href="<?= htmlspecialchars($bookmark['url']) ?>" target="_blank">
                            <?= htmlspecialchars($bookmark['title']) ?>
                        </a>
                        <span>(<?= htmlspecialchars($bookmark['count']) ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section id="user-bookmarks">
            <h2>Your Bookmarks</h2>
            <ul id="bookmark-list">
                <?php foreach ($userBookmarks as $bookmark): ?>
                    <li>
                        <a href="<?= htmlspecialchars($bookmark['url']) ?>"
                            target="_blank"><?= htmlspecialchars($bookmark['title']) ?></a>
                        <button class="edit-bookmark" data-id="<?= $bookmark['id'] ?>">Edit</button>
                        <button class="delete-bookmark" data-id="<?= $bookmark['id'] ?>">Delete</button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <form id="edit-bookmark-form" method="POST" action="" style="display: none;">
                <input type="hidden" id="edit-id" name="id">
                <label for="edit-title">Title:</label>
                <input type="text" id="edit-title" name="title" required>
                <label for="edit-url">URL:</label>
                <small style="color: gray;">Please include "http://" or "https://" in the URL.</small>
                <input type="url" id="edit-url" name="url" required>
                <button type="submit" name="edit-bookmark">Save Changes</button>
                <button type="button" id="cancel-edit">Cancel</button>
            </form>
            <form id="delete-bookmark-form" method="POST" action="" style="display: none;">
                <input type="hidden" name="delete-bookmark" value="1">
                <input type="hidden" id="delete-id" name="id">
            </form>
        </section>

        <section id="add-bookmark-form">
            <h2>Add Bookmark</h2>
            <form id="add-bookmark-form" method="POST" action="">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>
                <label for="url">URL:</label>
                <small style="color: gray;">Please include "http://" or "https://" in the URL.</small>
                <input type="url" id="url" name="url" required>
                <button type="submit" name="add-bookmark">Add Bookmark</button>

                <?php if (isset($_SESSION['url_error'])): ?>
                    <div class="form-error"><?= $_SESSION['url_error'] ?></div>
                    <?php unset($_SESSION['url_error']); ?>
                <?php endif; ?>
            </form>


        </section>
        <a href="#top" class="back-to-top">⬆ Back to Top</a>
    </main>

    <footer>
        <p>&copy; 2025 Bookmark Manager. All rights reserved.</p>

    </footer>
</body>

</html>