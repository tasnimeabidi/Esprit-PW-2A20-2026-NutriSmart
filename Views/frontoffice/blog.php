<?php


if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: /ProjetNutrismart/index.php?action=login");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Blog NutriSmart</title>

  <link rel="stylesheet" href="/ProjetNutrismart/css/shared-styles.css">

  <style>
    .blog-hero {
      min-height: 100vh;
      padding: 4rem 2rem;
      background: linear-gradient(
          rgba(34, 60, 42, 0.55),
          rgba(34, 60, 42, 0.55)
        ),
        url("https://images.unsplash.com/photo-1490818387583-1baba5e638af?auto=format&fit=crop&w=1600&q=80")
        center/cover fixed;
    }

    .blog-container {
      max-width: 900px;
      margin: 0 auto;
    }

    .blog-title {
      text-align: center;
      font-size: 2.5rem;
      font-weight: 900;
      color: var(--forest);
      margin-bottom: 2rem;
    }

    .post-card {
      background: rgba(255,255,255,0.9);
      backdrop-filter: blur(20px);
      border-radius: 1.5rem;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 20px 50px rgba(0,0,0,0.08);
    }

    .post-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.5rem;
    }

    .post-title {
      font-size: 1.3rem;
      font-weight: 800;
      color: var(--forest);
    }

    .post-meta {
      font-size: 0.85rem;
      color: var(--gray);
    }

    .post-content {
      margin: 1rem 0;
      color: var(--charcoal);
      line-height: 1.6;
    }

    .post-actions a {
      margin-right: 10px;
      font-size: 0.9rem;
      text-decoration: none;
      font-weight: 700;
      color: var(--primary);
    }

    .post-actions a:last-child {
      color: red;
    }

    .create-box {
      background: rgba(255,255,255,0.95);
      padding: 1.5rem;
      border-radius: 1.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 20px 50px rgba(0,0,0,0.08);
    }

    .create-box input,
    .create-box textarea {
      width: 100%;
      padding: 0.8rem;
      margin-bottom: 0.8rem;
      border-radius: 0.8rem;
      border: 1px solid #ddd;
    }

    .btn {
      background: var(--primary);
      color: white;
      padding: 0.7rem 1.4rem;
      border: none;
      border-radius: 2rem;
      cursor: pointer;
      font-weight: 700;
    }

    .logout {
      display: inline-block;
      margin-bottom: 1.5rem;
      background: red;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 2rem;
      text-decoration: none;
      font-weight: 700;
    }

  </style>
</head>

<body>

<nav>
  <a href="/ProjetNutrismart/Views/frontoffice/nutrismart-website.php" class="nav-logo">
    NutriSmart
  </a>

  <ul class="nav-links">
    <li><a href="/ProjetNutrismart/Views/frontoffice/nutrismart-website.php">Accueil</a></li>
    <li><a class="active" href="/ProjetNutrismart/index.php?action=blog">Blog</a></li>
  </ul>

  <div class="nav-auth">
    <a class="nav-cta" href="/ProjetNutrismart/index.php?action=logout">Logout</a>
  </div>
</nav>

<main class="blog-hero">
  <div class="blog-container">

    <h1 class="blog-title">Blog NutriSmart</h1>

    <!-- CREATE POST -->
    <div class="create-box">
      <form id="postForm" action="/ProjetNutrismart/index.php?action=create" method="POST" enctype="multipart/form-data">
        <input type="text" name="titre" placeholder="Titre">
        <textarea name="contenu" placeholder="Contenu..."></textarea>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
        <button class="btn" type="submit">Publier</button>
      </form>
    </div>

    <!-- POSTS -->
    <?php foreach ($posts ?? [] as $p): ?>

      <div class="post-card">

        <!-- EDIT POST -->
        <?php if (isset($_GET['edit']) && $_GET['edit'] == $p['id_publication']): ?>

          <form action="/ProjetNutrismart/index.php?action=update" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $p['id_publication'] ?>">

            <input type="text" name="titre" value="<?= htmlspecialchars($p['titre']) ?>" required>
            <textarea name="contenu" required><?= htmlspecialchars($p['contenu']) ?></textarea>

            <?php if (!empty($p['image'])): ?>
              <img src="/ProjetNutrismart/public/uploads/<?= $p['image'] ?>" width="150">
            <?php endif; ?>

            <input type="file" name="image">

            <button class="btn" type="submit">Save</button>
          </form>

        <?php else: ?>

          <!-- POST DISPLAY -->
          <div class="post-header">
            <div class="post-title"><?= htmlspecialchars($p['titre']) ?></div>
            <div class="post-meta"><?= htmlspecialchars($p['nom']) ?> • <?= $p['date_publication'] ?></div>
          </div>

          <div class="post-content">
            <?= nl2br(htmlspecialchars($p['contenu'])) ?>
          </div>

          <?php if (!empty($p['image'])): ?>
            <img src="/ProjetNutrismart/public/uploads/<?= $p['image'] ?>" width="250">
          <?php endif; ?>

          <?php if (isset($_SESSION['id_utilisateur']) && $p['id_utilisateur'] == $_SESSION['id_utilisateur']): ?>
            <div class="post-actions">
              <a href="/ProjetNutrismart/index.php?action=blog&edit=<?= $p['id_publication'] ?>">Edit</a>
              <a href="/ProjetNutrismart/index.php?action=delete&id=<?= $p['id_publication'] ?>">Delete</a>
            </div>
          <?php endif; ?>

          <!-- ========================= -->
          <!-- COMMENTS SECTION -->
          <!-- ========================= -->

          <div class="comments">

            <?php $comments = $commentModelForView->getByPostId($p['id_publication']); ?>

            <?php foreach ($comments as $c): ?>

              <div class="comment" data-id="<?= $c['id_commentaire'] ?>">

                <strong><?= htmlspecialchars($c['nom']) ?></strong>

                <!-- TEXT -->
                <p class="comment-text"><?= htmlspecialchars($c['contenu']) ?></p>

                <!-- EDIT BOX (hidden) -->
                <textarea class="edit-box" style="display:none; width:100%;">
                  <?= htmlspecialchars($c['contenu']) ?>
                </textarea>

                <small><?= $c['date_commentaire'] ?></small>

                <?php if (isset($_SESSION['id_utilisateur']) && $c['id_utilisateur'] == $_SESSION['id_utilisateur']): ?>
                  <div class="comment-actions">

                    <button type="button" class="edit-btn">Edit</button>
                    <button type="button" class="save-btn" style="display:none;">Save</button>
                    <a href="/ProjetNutrismart/index.php?action=delete_comment&id=<?= $c['id_commentaire'] ?>">
                      Delete
                    </a>

                  </div>
                <?php endif; ?>

              </div>

            <?php endforeach; ?>

          </div>

          <!-- ADD COMMENT -->
          <form action="/ProjetNutrismart/index.php?action=add_comment" method="POST">
            <input type="hidden" name="id_publication" value="<?= $p['id_publication'] ?>">

            <textarea name="contenu" maxlength="500" placeholder="Write a comment..." required></textarea>

            <button class="btn" type="submit">Comment</button>
          </form>

          <!-- ========================= -->

        <?php endif; ?>

      </div>

    <?php endforeach; ?>

  </div>
</main>

<!-- AJAX COMMENTS SCRIPT -->
<script src="/ProjetNutrismart/js/Comments.js"></script>

<script src="/ProjetNutrismart/public/publication-validation.js"></script>
</body>
</html>