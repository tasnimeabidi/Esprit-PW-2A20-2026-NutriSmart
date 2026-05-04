/* ============================================================
   reactions.js — Like / Dislike for posts AND comments
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    /* ── helper: send AJAX POST ── */
    function postData(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(body).toString()
        }).then(r => r.json());
    }

    /* ── update button UI after a reaction ── */
    function updateBtn(btn, myReaction, likes, dislikes) {
        const type      = btn.dataset.type;
        const isPost    = btn.dataset.target === 'post';
        const activeClass = type === 'like' ? 'active-like' : 'active-dislike';
        const sibling   = btn.parentElement.querySelector(
            `[data-type="${type === 'like' ? 'dislike' : 'like'}"]`
        );

        // Reset both buttons in this bar
        btn.classList.remove('active-like', 'active-dislike');
        if (sibling) sibling.classList.remove('active-like', 'active-dislike');

        // Apply active class if user has a reaction
        if (myReaction === 'like') {
            btn.parentElement.querySelector('[data-type="like"]').classList.add('active-like');
        } else if (myReaction === 'dislike') {
            btn.parentElement.querySelector('[data-type="dislike"]').classList.add('active-dislike');
        }

        // Update counts
        btn.parentElement.querySelector('[data-type="like"] .count').textContent   = likes;
        btn.parentElement.querySelector('[data-type="dislike"] .count').textContent = dislikes;
    }

    /* ── delegate click on all reaction buttons ── */
    document.body.addEventListener('click', function (e) {
        const btn = e.target.closest('.reaction-btn, .comment-reaction-btn');
        if (!btn) return;

        const target = btn.dataset.target;   // 'post' or 'comment'
        const type   = btn.dataset.type;     // 'like' or 'dislike'
        const id     = btn.dataset.id;

        if (target === 'post') {
            postData('/ProjetNutrismart/index.php?action=react_post', {
                id_publication: id,
                type: type
            }).then(data => {
                if (data.success) {
                    updateBtn(btn, data.myReaction, data.likes, data.dislikes);
                }
            }).catch(console.error);

        } else if (target === 'comment') {
            const postId = btn.dataset.postId;
            postData('/ProjetNutrismart/index.php?action=react_comment', {
                id_commentaire: id,
                id_publication: postId,
                type: type
            }).then(data => {
                if (data.success) {
                    updateBtn(btn, data.myReaction, data.likes, data.dislikes);
                }
            }).catch(console.error);
        }
    });
});