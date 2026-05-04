/* ============================================================
   notifications.js — Bell icon, dropdown, polling
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    const wrapper    = document.getElementById('notifWrapper');
    const dropdown   = document.getElementById('notifDropdown');
    const badge      = document.getElementById('notifBadge');
    const list       = document.getElementById('notifList');
    const markReadBtn = document.getElementById('markReadBtn');

    if (!wrapper) return; // guard: not on blog page

    /* ── fetch and render notifications ── */
    function loadNotifications() {
        fetch('/ProjetNutrismart/index.php?action=get_notifications')
            .then(r => r.json())
            .then(data => {
                if (data.error) return;

                // Update badge
                if (data.unread > 0) {
                    badge.textContent = data.unread > 9 ? '9+' : data.unread;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }

                // Render notification items
                if (!data.notifications || data.notifications.length === 0) {
                    list.innerHTML = '<div class="notif-empty">Aucune notification</div>';
                    return;
                }

                list.innerHTML = data.notifications.map(n => `
                    <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}">
                        ${escapeHtml(n.message)}
                        <small>${formatDate(n.date_notification)}</small>
                    </div>
                `).join('');
            })
            .catch(console.error);
    }

    /* ── toggle dropdown on bell click ── */
    wrapper.addEventListener('click', function (e) {
        e.stopPropagation(); // ✅ FIX: prevent document listener from closing it immediately
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) {
            markAllRead();
        }
    });

    /* ── close dropdown when clicking outside ── */
    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });

    /* ── mark all read button ── */
    if (markReadBtn) {
        markReadBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            markAllRead();
        });
    }

    function markAllRead() {
        fetch('/ProjetNutrismart/index.php?action=mark_notifications_read', {
            method: 'POST'
        })
        .then(r => r.json())
        .then(() => {
            badge.classList.add('hidden');
            // Remove unread styling locally
            list.querySelectorAll('.notif-item.unread').forEach(el => {
                el.classList.remove('unread');
            });
        })
        .catch(console.error);
    }

    /* ── helpers ── */
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d)) return dateStr;
        return d.toLocaleDateString('fr-FR', {
            day: '2-digit', month: 'short',
            hour: '2-digit', minute: '2-digit'
        });
    }

    /* ── initial load + poll every 30 seconds ── */
    loadNotifications();
    setInterval(loadNotifications, 30000);
});