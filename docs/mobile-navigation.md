# Mobile drawer first-paint and navigation fix — 2026-11-20

The drawer used to depend on `#vr-toggle:not([hidden])`. The server rendered a hidden toggle, leaving the menu as visible in-flow navigation until footer JavaScript loaded. Revealing the toggle simultaneously changed menu positioning and started a 250ms closing transition. Under a delayed-script reproduction this caused 17 visible closed-menu frames.

Compact CSS now positions the drawer offscreen and hidden from the first styled paint. Transition styling is enabled only by the interaction class `vr-drawer-motion`. Initial setup, breakpoint changes, cross-document link clicks and page-cache restoration close/reset without animation. Explicit hamburger/shade/Escape actions and same-document anchors retain normal motion. Modified/new-tab clicks keep normal browser behavior.

`pagehide` stores the closed state before back/forward caching; persisted `pageshow` restores without motion. A fresh non-persisted pageshow deliberately does not reset the drawer, so a tap while images are still loading is not interrupted. Existing WordPress admin-bar offset handling remains in place.

Without scripting, a conditional `<noscript>` stylesheet exposes the same links as ordinary wrapped navigation in page flow. It is requested only when scripting is disabled and requires no inline boot script.

Validation:

- Delayed footer JS: fixed/hidden menu before JS, zero initialization transitions and zero visible closed-menu frames.
- Deliberate open/close remains animated on mobile, tablet and coarse-pointer desktop.
- Cross-page navigation, actual Back and persisted page lifecycle events restore closed without motion.
- Same-document anchors keep closing animation; reduced-motion settings disable it.
- Resizing to desktop exposes regular links and releases inert/scroll locking.
- No-JS links remain visible and fit a 375px viewport.
- Header/sidebar offsets remain aligned with and without WordPress's admin toolbar.

Deploy the dated update ZIP into the existing `wp-content/themes/` directory, merging its `vietride/` files, and purge page cache. The update includes the CSS, JS and noscript stylesheet together. It is an update package and retains original photos already installed in the theme.
