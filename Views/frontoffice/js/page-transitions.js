/**
 * NutriSmart — Smooth Page Transitions
 * Intercepts internal link clicks, fades the page out, then navigates.
 */
(function () {
  'use strict';

  function handleLinkClick(e) {
    var link = e.currentTarget;
    var href = link.getAttribute('href');

    // Skip: no href, external links, hash anchors, or javascript: links
    if (
      !href ||
      href.startsWith('http') ||
      href.startsWith('#') ||
      href.startsWith('javascript') ||
      href.startsWith('mailto') ||
      link.hasAttribute('download') ||
      link.getAttribute('target') === '_blank'
    ) {
      return;
    }

    e.preventDefault();
    window.location.href = href;
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Attach to all internal <a> tags
    var links = document.querySelectorAll('a');
    links.forEach(function (link) {
      link.addEventListener('click', handleLinkClick);
    });

    // Also watch for dynamically added links (e.g., rendered by JS)
    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === 1) {
            var newLinks = node.tagName === 'A' ? [node] : node.querySelectorAll('a');
            newLinks.forEach(function (link) {
              link.addEventListener('click', handleLinkClick);
            });
          }
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  });
})();
