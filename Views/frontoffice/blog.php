<?php if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: /ProjetNutrismart/index.php?action=login"); exit;
} ?>
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
            ), url("https://images.unsplash.com/photo-1490818387583-1baba5e638af?auto=format&fit=crop&w=1600&q=80") center/cover fixed;
        }
        .blog-container { max-width: 900px; margin: 0 auto; }
        .blog-title {
            text-align: center; font-size: 2.5rem; font-weight: 900;
            color: var(--forest); margin-bottom: 2rem;
        }

        /* ── SEARCH & FILTER BAR ── */
        .search-bar {
            display: flex;
            gap: 0.8rem;
            align-items: center;
            flex-wrap: wrap;
            background: rgba(255,255,255,0.95);
            border-radius: 1.5rem;
            padding: 1rem 1.2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.08);
        }
        .search-bar input[type="text"] {
            flex: 1;
            min-width: 180px;
            padding: 0.6rem 1rem;
            border-radius: 2rem;
            border: 1.5px solid #ddd;
            font-family: inherit;
            font-size: 0.92rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .search-bar input[type="text"]:focus {
            border-color: var(--primary);
        }
        .search-bar select {
            padding: 0.6rem 1rem;
            border-radius: 2rem;
            border: 1.5px solid #ddd;
            font-family: inherit;
            font-size: 0.92rem;
            background: white;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
        }
        .search-bar select:focus {
            border-color: var(--primary);
        }
        .search-clear {
            background: #f5f5f5;
            border: 1.5px solid #ddd;
            border-radius: 2rem;
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            color: var(--gray);
            transition: all 0.15s;
            font-family: inherit;
        }
        .search-clear:hover {
            background: #ffe0e0;
            border-color: #ef9a9a;
            color: #c62828;
        }

        /* no results message */
        .no-results {
            display: none;
            text-align: center;
            background: rgba(255,255,255,0.9);
            border-radius: 1.5rem;
            padding: 2.5rem;
            color: var(--gray);
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 20px 50px rgba(0,0,0,0.08);
        }
        .no-results span { font-size: 2rem; display: block; margin-bottom: 0.5rem; }

        /* ── POST CARDS ── */
        .post-card {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem; padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.08);
            transition: transform 0.15s;
        }
        .post-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 0.5rem;
        }
        .post-title  { font-size: 1.3rem; font-weight: 800; color: var(--forest); }
        .post-meta   { font-size: 0.85rem; color: var(--gray); }
        .post-content { margin: 1rem 0; color: var(--charcoal); line-height: 1.6; }

        /* highlight matched search text */
        mark {
            background: #fff9c4;
            border-radius: 3px;
            padding: 0 2px;
            color: inherit;
        }

        .create-box {
            background: rgba(255,255,255,0.95); padding: 1.5rem;
            border-radius: 1.5rem; margin-bottom: 2rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.08);
        }
        .create-box input, .create-box textarea {
            width: 100%; padding: 0.8rem; margin-bottom: 0.8rem;
            border-radius: 0.8rem; border: 1px solid #ddd;
        }
        .btn {
            background: var(--primary); color: white;
            padding: 0.7rem 1.4rem; border: none;
            border-radius: 2rem; cursor: pointer; font-weight: 700;
        }

        /* ── POST ACTION BUTTONS ── */
        .post-actions { display: flex; gap: 0.5rem; margin-top: 0.8rem; }
        .post-actions a {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.38rem 0.9rem; border-radius: 2rem;
            font-size: 0.82rem; font-weight: 700;
            text-decoration: none; transition: all 0.18s;
        }
        .post-actions .btn-edit  { background: #e8f5e9; color: #2e7d32; border: 1.5px solid #a5d6a7; }
        .post-actions .btn-edit:hover  { background: #c8e6c9; transform: translateY(-1px); }
        .post-actions .btn-delete { background: #fdecea; color: #c62828; border: 1.5px solid #ef9a9a; }
        .post-actions .btn-delete:hover { background: #ffcdd2; transform: translateY(-1px); }

        /* ── REACTION BUTTONS ── */
        .reaction-bar { display: flex; align-items: center; gap: 0.6rem; margin: 0.8rem 0; flex-wrap: wrap; }
        .reaction-btn {
            display: inline-flex; align-items: center; gap: 0.3rem;
            padding: 0.35rem 0.85rem; border-radius: 2rem;
            border: 2px solid #e0e0e0; background: #f9f9f9;
            cursor: pointer; font-size: 0.9rem; font-weight: 700;
            transition: all 0.18s; user-select: none;
        }
        .reaction-btn:hover { background: #f0f0f0; border-color: #bbb; }
        .reaction-btn.active-like    { background: #e8f5e9; border-color: #4caf50; color: #2e7d32; }
        .reaction-btn.active-dislike { background: #fdecea; border-color: #f44336; color: #c62828; }
        .reaction-btn .count { font-size: 0.85rem; }

        /* ── COMMENTS ── */
        .comment {
            background: #f7faf7; border: 1px solid rgba(74,124,89,0.1);
            border-radius: 1rem; padding: 0.85rem 1rem; margin-bottom: 0.7rem;
        }
        .comment strong { color: var(--forest); font-size: 0.9rem; }
        .comment-text  { margin: 0.3rem 0 0.2rem; color: var(--charcoal); font-size: 0.92rem; line-height: 1.5; }
        .comment small  { color: var(--gray); font-size: 0.78rem; }

        /* ── COMMENT ACTION BUTTONS ── */
        .comment-actions { display: flex; align-items: center; gap: 0.4rem; margin-top: 0.5rem; flex-wrap: wrap; }
        .cbtn {
            display: inline-flex; align-items: center; gap: 0.25rem;
            padding: 0.28rem 0.75rem; border-radius: 2rem;
            font-size: 0.78rem; font-weight: 700; cursor: pointer;
            border: 1.5px solid transparent; transition: all 0.15s;
            text-decoration: none; background: none; font-family: inherit;
        }
        .cbtn-edit   { background: #e8f5e9; color: #2e7d32; border-color: #a5d6a7; }
        .cbtn-edit:hover   { background: #c8e6c9; transform: translateY(-1px); }
        .cbtn-save   { background: #e3f2fd; color: #1565c0; border-color: #90caf9; }
        .cbtn-save:hover   { background: #bbdefb; transform: translateY(-1px); }
        .cbtn-delete { background: #fdecea; color: #c62828; border-color: #ef9a9a; }
        .cbtn-delete:hover { background: #ffcdd2; transform: translateY(-1px); }

        /* ── COMMENT REACTION BAR ── */
        .comment-reaction-bar { display: flex; gap: 0.4rem; margin-top: 0.4rem; }
        .comment-reaction-btn {
            display: inline-flex; align-items: center; gap: 0.2rem;
            padding: 0.2rem 0.6rem; border-radius: 2rem;
            border: 1.5px solid #ddd; background: white;
            cursor: pointer; font-size: 0.8rem; font-weight: 700; transition: all 0.15s;
        }
        .comment-reaction-btn:hover { background: #f0f0f0; }
        .comment-reaction-btn.active-like    { background: #e8f5e9; border-color: #4caf50; color: #2e7d32; }
        .comment-reaction-btn.active-dislike { background: #fdecea; border-color: #f44336; color: #c62828; }

        /* ── NOTIFICATION BELL ── */
        .notif-wrapper { position: relative; display: inline-block; cursor: pointer; margin-left: 1rem; }
        .notif-bell { font-size: 1.4rem; }
        .notif-badge {
            position: absolute; top: -6px; right: -8px;
            background: red; color: white; font-size: 0.7rem; font-weight: 800;
            border-radius: 50%; width: 18px; height: 18px;
            display: flex; align-items: center; justify-content: center;
        }
        .notif-badge.hidden { display: none; }
        .notif-dropdown {
            display: none; position: absolute; right: 0; top: 2.2rem;
            width: 320px; background: white; border-radius: 1rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15); z-index: 999; overflow: hidden;
        }
        .notif-dropdown.open { display: block; }
        .notif-header {
            padding: 0.7rem 1rem; background: var(--forest, #2e7d32);
            color: white; font-weight: 800; font-size: 0.95rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .notif-header button {
            background: rgba(255,255,255,0.2); border: none; color: white;
            border-radius: 1rem; padding: 0.2rem 0.6rem;
            cursor: pointer; font-size: 0.75rem; font-weight: 700;
        }
        .notif-list { max-height: 320px; overflow-y: auto; }
        .notif-item { padding: 0.75rem 1rem; border-bottom: 1px solid #f0f0f0; font-size: 0.85rem; color: #333; }
        .notif-item.unread { background: #f0faf0; font-weight: 600; }
        .notif-item small { color: #999; display: block; margin-top: 0.2rem; }
        .notif-empty { padding: 1.2rem 1rem; text-align: center; color: #aaa; font-size: 0.85rem; }
    </style>
</head>
<body>
<nav>
    <a href="/ProjetNutrismart/Views/frontoffice/nutrismart-website.php" class="nav-logo">NutriSmart</a>
    <ul class="nav-links">
        <li><a href="/ProjetNutrismart/Views/frontoffice/nutrismart-website.php">Accueil</a></li>
        <li><a class="active" href="/ProjetNutrismart/index.php?action=blog">Blog</a></li>
    </ul>
    <div class="nav-auth" style="display:flex;align-items:center;">
        <div class="notif-wrapper" id="notifWrapper">
            <span class="notif-bell">🔔</span>
            <span class="notif-badge hidden" id="notifBadge">0</span>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    Notifications
                    <button id="markReadBtn">Tout lire</button>
                </div>
                <div class="notif-list" id="notifList">
                    <div class="notif-empty">Aucune notification</div>
                </div>
            </div>
        </div>
        <a class="nav-cta" href="/ProjetNutrismart/index.php?action=logout" style="margin-left:1rem;">Logout</a>
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

        <!-- ★ SEARCH & FILTER BAR ★ -->
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="🔍 Rechercher par titre ou auteur...">
            <select id="sortSelect">
                <option value="newest">📅 Plus récent</option>
                <option value="oldest">📅 Plus ancien</option>
                <option value="most_liked">👍 Plus aimé</option>
            </select>
            <button class="search-clear" id="clearBtn">✕ Effacer</button>
        </div>

        <!-- NO RESULTS -->
        <div class="no-results" id="noResults">
            <span>🔎</span>
            Aucune publication ne correspond à votre recherche.
        </div>

        <!-- POSTS -->
        <div id="postsContainer">
        <?php foreach ($posts ?? [] as $p): ?>
        <?php
            $postCounts  = $reactionModelForView->countForPost($p['id_publication']);
            $myPostReact = $reactionModelForView->getUserReactionOnPost($_SESSION['id_utilisateur'], $p['id_publication']);
        ?>
        <div class="post-card"
             data-titre="<?= htmlspecialchars(strtolower($p['titre'])) ?>"
             data-auteur="<?= htmlspecialchars(strtolower($p['nom'])) ?>"
             data-date="<?= $p['date_publication'] ?>"
             data-likes="<?= $postCounts['like'] ?>">

            <!-- EDIT POST FORM -->
            <?php if (isset($_GET['edit']) && $_GET['edit'] == $p['id_publication']): ?>
            <form action="/ProjetNutrismart/index.php?action=update" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $p['id_publication'] ?>">
                <input type="text" name="titre" value="<?= htmlspecialchars($p['titre']) ?>" required
                    style="width:100%;padding:0.8rem;border-radius:0.8rem;border:1px solid #ddd;margin-bottom:0.8rem;">
                <textarea name="contenu" required
                    style="width:100%;padding:0.8rem;border-radius:0.8rem;border:1px solid #ddd;margin-bottom:0.8rem;"><?= htmlspecialchars($p['contenu']) ?></textarea>
                <?php if (!empty($p['image'])): ?>
                    <img src="/ProjetNutrismart/public/uploads/<?= $p['image'] ?>" width="150"
                        style="border-radius:0.5rem;margin-bottom:0.5rem;display:block;">
                <?php endif; ?>
                <input type="file" name="image" style="margin-bottom:0.8rem;">
                <button class="btn" type="submit">💾 Enregistrer</button>
            </form>

            <?php else: ?>
            <!-- POST DISPLAY -->
            <div class="post-header">
                <div class="post-title searchable-title"><?= htmlspecialchars($p['titre']) ?></div>
                <div class="post-meta">
                    <span class="searchable-author"><?= htmlspecialchars($p['nom']) ?></span>
                    • <?= $p['date_publication'] ?>
                </div>
            </div>
            <div class="post-content">
                <?= nl2br(htmlspecialchars($p['contenu'])) ?>
            </div>
            <!-- AI BUTTON -->
<button 
    class="ai-btn"
    data-id="<?= $p['id_publication'] ?>"
    data-content="<?= htmlspecialchars($p['contenu']) ?>"
    style="
        margin-top:10px;
        background:#4CAF50;
        color:white;
        border:none;
        padding:10px 16px;
        border-radius:10px;
        cursor:pointer;
        font-weight:bold;
        display:flex;
        align-items:center;
        gap:8px;
    "
>
    <span class="ai-text">✨ Résumer avec IA</span>
    <span class="ai-spinner" style="display:none;"></span>
</button>

<!-- AI RESULT OUTPUT -->
<div class="ai-result" id="ai-result-<?= $p['id_publication'] ?>"></div>

<div class="ai-result" id="ai-result-<?= $p['id_publication'] ?>" style="margin-top:10px;"></div>

<!-- AI OUTPUT BOX -->
<div class="ai-result" id="ai-result-<?= $p['id_publication'] ?>" style="margin-top:10px;"></div>

<div class="ai-result"></div>
            <?php if (!empty($p['image'])): ?>
                <img src="/ProjetNutrismart/public/uploads/<?= $p['image'] ?>" width="250"
                    style="border-radius:0.8rem;margin-bottom:0.5rem;display:block;">
            <?php endif; ?>

            <!-- REACTION BAR -->
            <div class="reaction-bar" data-post-id="<?= $p['id_publication'] ?>">
                <button class="reaction-btn <?= $myPostReact === 'like' ? 'active-like' : '' ?>"
                    data-type="like" data-target="post" data-id="<?= $p['id_publication'] ?>">
                    👍 <span class="count"><?= $postCounts['like'] ?></span>
                </button>
                <button class="reaction-btn <?= $myPostReact === 'dislike' ? 'active-dislike' : '' ?>"
                    data-type="dislike" data-target="post" data-id="<?= $p['id_publication'] ?>">
                    👎 <span class="count"><?= $postCounts['dislike'] ?></span>
                </button>
            </div>

            <!-- POST EDIT / DELETE -->
            <?php if (isset($_SESSION['id_utilisateur']) && $p['id_utilisateur'] == $_SESSION['id_utilisateur']): ?>
            <div class="post-actions">
                <a class="btn-edit"
                   href="/ProjetNutrismart/index.php?action=blog&edit=<?= $p['id_publication'] ?>">
                    ✏️ Modifier
                </a>
                <a class="btn-delete"
                   href="/ProjetNutrismart/index.php?action=delete&id=<?= $p['id_publication'] ?>"
                   onclick="return confirm('Supprimer cette publication ?')">
                    🗑️ Supprimer
                </a>
            </div>
            <?php endif; ?>

            <!-- COMMENTS SECTION -->
            <div class="comments" style="margin-top:1.2rem;">
                <?php $comments = $commentModelForView->getByPostId($p['id_publication']); ?>
                <?php foreach ($comments as $c): ?>
                <?php
                    $cCounts  = $reactionModelForView->countForComment($c['id_commentaire']);
                    $myCReact = $reactionModelForView->getUserReactionOnComment($_SESSION['id_utilisateur'], $c['id_commentaire']);
                ?>
                <div class="comment" data-id="<?= $c['id_commentaire'] ?>">
                    <strong><?= htmlspecialchars($c['nom']) ?></strong>
                    <p class="comment-text"><?= htmlspecialchars($c['contenu']) ?></p>
                    <textarea class="edit-box" style="display:none;width:100%;border-radius:0.6rem;border:1px solid #ddd;padding:0.5rem;margin-top:0.4rem;font-family:inherit;"><?= htmlspecialchars($c['contenu']) ?></textarea>
                    <small><?= $c['date_commentaire'] ?></small>

                    <div class="comment-reaction-bar">
                        <button class="comment-reaction-btn <?= $myCReact === 'like' ? 'active-like' : '' ?>"
                            data-type="like" data-target="comment"
                            data-id="<?= $c['id_commentaire'] ?>"
                            data-post-id="<?= $p['id_publication'] ?>">
                            👍 <span class="count"><?= $cCounts['like'] ?></span>
                        </button>
                        <button class="comment-reaction-btn <?= $myCReact === 'dislike' ? 'active-dislike' : '' ?>"
                            data-type="dislike" data-target="comment"
                            data-id="<?= $c['id_commentaire'] ?>"
                            data-post-id="<?= $p['id_publication'] ?>">
                            👎 <span class="count"><?= $cCounts['dislike'] ?></span>
                        </button>
                    </div>

                    <?php if (isset($_SESSION['id_utilisateur']) && $c['id_utilisateur'] == $_SESSION['id_utilisateur']): ?>
                    <div class="comment-actions">
                        <button type="button" class="cbtn cbtn-edit edit-btn">✏️ Modifier</button>
                        <button type="button" class="cbtn cbtn-save save-btn" style="display:none;">💾 Enregistrer</button>
                        <a class="cbtn cbtn-delete"
                           href="/ProjetNutrismart/index.php?action=delete_comment&id=<?= $c['id_commentaire'] ?>"
                           onclick="return confirm('Supprimer ce commentaire ?')">
                           🗑️ Supprimer
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- ADD COMMENT -->
            <form action="/ProjetNutrismart/index.php?action=add_comment" method="POST" style="margin-top:1rem;">
                <input type="hidden" name="id_publication" value="<?= $p['id_publication'] ?>">
                <textarea name="contenu" maxlength="500" placeholder="Écrire un commentaire..." required
                    style="width:100%;padding:0.7rem;border-radius:0.8rem;border:1px solid #ddd;margin-bottom:0.5rem;font-family:inherit;resize:vertical;"></textarea>
                <button class="btn" type="submit">💬 Commenter</button>
            </form>

            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div><!-- end #postsContainer -->
    </div>
</main>

<script src="/ProjetNutrismart/js/Comments.js"></script>
<script src="/ProjetNutrismart/js/publication-validation.js"></script>
<script src="/ProjetNutrismart/js/reactions.js"></script>
<script src="/ProjetNutrismart/js/notifications.js"></script>
<script src="/ProjetNutrismart/js/ai.js"></script>

<script>
/* ============================================================
   SEARCH, FILTER & SORT — pure JS, no backend calls
   ============================================================ */
(function () {
    const searchInput  = document.getElementById('searchInput');
    const sortSelect   = document.getElementById('sortSelect');
    const clearBtn     = document.getElementById('clearBtn');
    const container    = document.getElementById('postsContainer');
    const noResults    = document.getElementById('noResults');

    /* collect all cards once into an array we can sort/filter */
    let cards = Array.from(container.querySelectorAll('.post-card'));

    /* ── highlight matching text inside an element ── */
    function highlight(el, query) {
        if (!el) return;
        // restore original text first
        el.innerHTML = el.dataset.original ?? el.textContent;
        if (!query) return;

        // save original on first run
        if (!el.dataset.original) el.dataset.original = el.textContent;

        const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex   = new RegExp(`(${escaped})`, 'gi');
        el.innerHTML  = (el.dataset.original).replace(regex, '<mark>$1</mark>');
    }

    /* ── restore all highlights ── */
    function clearHighlights() {
        container.querySelectorAll('.searchable-title, .searchable-author').forEach(el => {
            if (el.dataset.original) el.innerHTML = el.dataset.original;
        });
    }

    /* ── main filter + sort function ── */
    function applyFilters() {
        const query = searchInput.value.trim().toLowerCase();
        const sort  = sortSelect.value;

        /* 1. filter */
        let visible = cards.filter(card => {
            if (!query) return true;
            const titre  = card.dataset.titre  ?? '';
            const auteur = card.dataset.auteur ?? '';
            return titre.includes(query) || auteur.includes(query);
        });

        /* 2. sort */
        visible.sort((a, b) => {
            if (sort === 'newest') {
                return new Date(b.dataset.date) - new Date(a.dataset.date);
            }
            if (sort === 'oldest') {
                return new Date(a.dataset.date) - new Date(b.dataset.date);
            }
            if (sort === 'most_liked') {
                return parseInt(b.dataset.likes) - parseInt(a.dataset.likes);
            }
            return 0;
        });

        /* 3. hide all, then show+reorder visible ones */
        cards.forEach(c => c.style.display = 'none');
        clearHighlights();

        if (visible.length === 0) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
            visible.forEach(card => {
                card.style.display = 'block';
                container.appendChild(card); // reorder in DOM

                /* highlight matched text */
                if (query) {
                    highlight(card.querySelector('.searchable-title'),  query);
                    highlight(card.querySelector('.searchable-author'), query);
                }
            });
        }
    }

    /* ── events ── */
    searchInput.addEventListener('input',  applyFilters);
    sortSelect.addEventListener('change',  applyFilters);

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        sortSelect.value  = 'newest';
        applyFilters();
    });

    /* initial sort on load */
    applyFilters();
})();
</script>
</body>
</html>