<img src="./assets/github-banner.jpg" alt="A file splitting off into multiple files with language references on them." style="width: 100%; height: auto;">

# Template Parts Router for WPML

A drop-in WordPress block that renders the right language variant of a template part based on the active WPML language — using a file-per-language convention instead of database-stored translations.

If you build block themes, version-control them, and use Create Block Theme to keep files in sync with the editor, you've probably hit the wall where WPML's per-language template parts only exist in the database. This plugin closes that gap.

## Why this exists

WPML translates template parts by storing per-language copies in the `wp_posts` table. Theme authors who treat files as the source of truth — for git history, code review, CI, theme distribution, or Create Block Theme round-trips — lose that source of truth the moment a translator opens the editor. The translated copy lives only in the database, and exporting the theme collapses everything back to a single file.

Template Parts Router takes the opposite approach. You keep one template part file per language on disk. A single router block, dropped into the canonical template part, picks the right file at render time using `wpml_current_language`. WPML stays out of template-part resolution entirely.

## At a glance

- **One block, no per-slot setup.** Drop `tp-router/router` into any template part.
- **File-based.** Variants live as `parts/{slug}-{lang}.html` next to your other template parts. CBT round-trips just work.
- **Auto-detects the surrounding slot.** Inside `parts/footer.html`, the router infers `footer` automatically. Override with `baseSlug` only if you need to.
- **Native editor preview.** Variants render inline as real, editable blocks via `useEntityBlockEditor` — the same machinery core's `core/template-part` uses. No `ServerSideRender` opaque chunk, no broken layout cascade, no thick selection outlines.
- **Inline editing in context.** Edit the resolved variant from inside its parent template — changes save to the variant entity, exactly like core's template-part block.
- **Preview-language toggle.** A control in the block inspector lets you flip which variant the editor renders. The frontend always uses the actual WPML language.

## Quick start

1. Install and activate the plugin.
2. Disable WPML's translation of `wp_template_part` and `wp_template`. (One-time setup; see [docs/usage.md](docs/usage.md#required-wpml-setup) for the exact toggle path and why.)
3. Add the router to a template part:

   ```html
   <!-- parts/footer.html -->
   <!-- wp:tp-router/router {"baseSlug":"footer"} /-->
   ```

4. Create one file per language alongside it:

   ```
   parts/footer-en.html
   parts/footer-fr.html
   ```

That's it. English visitors see `footer-en.html`; French visitors see `footer-fr.html`. No database rows, no per-language fork in the Site Editor.

## How it resolves

```
templates/index.html      ─►  <!-- wp:template-part {"slug":"footer"} /-->
parts/footer.html         ─►  <!-- wp:tp-router/router {"baseSlug":"footer"} /-->
                                              │
                                              ▼
                          apply_filters( 'wpml_current_language', 'en' )
                                              │
                                              ▼
                          parts/footer-{lang}.html
```

## Documentation

Full setup, conventions, the editor experience, migration from WPML-managed template parts, troubleshooting, and a walkthrough of how the resolution works under the hood: **[docs/usage.md](docs/usage.md)**.

## Requirements

- WordPress 6.6+
- PHP 7.4+
- WPML Multilingual CMS (4.6+ recommended)

Other multilingual plugins (Polylang, TranslatePress, etc.) are not supported in this release. The router reads the language code from the `wpml_current_language` filter; adapting it to other plugins is straightforward but out of scope here.

## License

GPL-2.0-or-later. See `plugin.php` header for the full license declaration.
