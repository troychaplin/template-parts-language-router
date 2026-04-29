# Template Parts Router — Usage

This guide covers everything you need to set up, use, and troubleshoot the plugin in a real block theme. If you just want the elevator pitch, see the [README](../README.md).

## Contents

- [Installation](#installation)
- [Required WPML setup](#required-wpml-setup)
- [The naming convention](#the-naming-convention)
- [Using the router block](#using-the-router-block)
- [Pattern variants](#pattern-variants)
- [The editor experience](#the-editor-experience)
- [Migrating from WPML-managed template parts](#migrating-from-wpml-managed-template-parts)
- [Troubleshooting](#troubleshooting)
- [How it works under the hood](#how-it-works-under-the-hood)
- [Limitations](#limitations)

## Installation

Standard WordPress plugin install. Drop the plugin folder into `wp-content/plugins/` and activate from `Plugins → Installed Plugins`. The plugin registers a single block (`tp-router/router`) on `init` and adds two render-stack filters (`pre_render_block` and `render_block`) that maintain context for auto-detection during `do_blocks()`.

The plugin's header declares `Requires Plugins: sitepress-multilingual-cms`, so WordPress 6.5+ won't let you activate it unless WPML is also installed and active.

The plugin ships with its build output (`build/`) committed, so no `npm install` or `composer install` is needed for production use. If you're hacking on the source, see the development scripts in `package.json` and `composer.json`.

## Required WPML setup

WPML, by default, treats `wp_template_part` and `wp_template` as translatable post types. That means as soon as a translator opens a template part in the Site Editor in the secondary language, WPML creates a per-language copy in the database. **WordPress's template-part lookup prefers the database copy over the file**, which means your file-based router never gets a chance to run.

### What to change

Go to:

> **WP Admin → WPML → Settings → Multilingual Content Setup → Custom Posts Translation**

Find these two rows and set both to **Not translatable** (some WPML versions label this "Do nothing"):

- **Templates** (`wp_template`)
- **Template Parts** (`wp_template_part`)

Save the settings page.

### Why both, not just template parts

`wp_template` matters because some block themes wrap header/footer references inside templates that are themselves customized via the Site Editor. If WPML translates the template, the FR copy of the template might rewrite slugs, override the file, or otherwise interfere with how the router is reached. Disabling translation on both keeps WordPress's normal file-first resolution path intact for everything in your `templates/` and `parts/` folders.

### Cleaning up rows WPML already created

Disabling translation does **not** delete existing translated rows. If you've already let WPML translate template parts, you'll have orphan database entries that continue to override your files. Find and delete them:

```bash
# List remaining rows
wp post list --post_type=wp_template_part --post_status=any --fields=ID,post_name,post_status
wp post list --post_type=wp_template      --post_status=any --fields=ID,post_name,post_status

# Inspect a row before deciding
wp post get <ID> --field=post_content

# Delete the orphans you don't want
wp post delete <ID> [<ID>...] --force
```

You can also do this through the Site Editor's UI: `Patterns → Manage all template parts → ⋮ → Clear customizations` for each row. Same effect, slower if there are many.

After cleanup, hit a frontend URL in each language and confirm the router is doing its job (see [Troubleshooting](#troubleshooting) if not).

## The naming convention

This section covers **template-part variants** (the default). Pattern variants follow a different convention — see [Pattern variants](#pattern-variants).

For a template part with base slug `<base>`, create one file per language using the pattern:

```
parts/<base>-<lang>.html
```

The language code is whatever `apply_filters( 'wpml_current_language', 'en' )` returns at render time — typically the two-letter codes WPML uses (`en`, `fr`, `es`, `de`, …). If WPML is configured with locale-style codes (`en_US`, `fr_CA`), use those.

Example for a bilingual EN/FR site:

```
parts/footer.html       <!-- contains the router block -->
parts/footer-en.html    <!-- English footer content -->
parts/footer-fr.html    <!-- French footer content -->
```

The "host" file (`parts/footer.html`) is what your templates reference via `<!-- wp:template-part {"slug":"footer"} /-->`. Its only content is the router block. The variant files contain the actual blocks for each language.

There is no convention requiring you to keep an unsuffixed `parts/<base>.html` file with content of its own. Once the router is in `parts/footer.html`, the unsuffixed file's content is just the router; the language-specific content lives in the suffixed files.

## Using the router block

### Basic form

Inside `parts/footer.html`:

```html
<!-- wp:tp-router/router {"baseSlug":"footer"} /-->
```

That's the entire content of the host file. The block has no inner content; it's purely a render-time directive.

### Auto-detection

You can omit the `baseSlug` attribute on the frontend:

```html
<!-- wp:tp-router/router /-->
```

The plugin maintains a render-stack of `core/template-part` blocks currently being rendered. When the router fires, it peeks the top of the stack and uses that slug as the base. This works because, on the frontend, `core/template-part` always wraps the host file's render call.

**The auto-detection is frontend-only.** In the Site Editor's REST `block-renderer` preview (and any context where the router renders without a parent template-part on the stack), there's nothing to peek at. So:

- For the **frontend**, `<!-- wp:tp-router/router /-->` is enough.
- For a **clean editor preview**, set `baseSlug` explicitly.
- The block's editor inspector shows the auto-detected value as a placeholder when the field is empty, so you can see what the frontend will resolve.

### Recommendation

Set `baseSlug` explicitly even when auto-detection would work. It's clearer for whoever reads the file, makes the editor preview render the right variant on first load, and doesn't break if WordPress ever changes how template parts get rendered internally.

## Pattern variants

The router can render either a **template part** (default) or a **theme pattern** as the variant. The choice is per-block, controlled by the **Variant source** radio in the inspector or the `variantType` attribute (`"template-part"` or `"pattern"`).

### When to choose which

| | Template part variant | Pattern variant |
|---|---|---|
| Native PHP in the file | No (HTML only) | Yes (full PHP, including `__()`, `get_template_directory_uri()`, conditional logic) |
| `__()` / `_e()` translations | No | Yes |
| Dynamic content at render | No | Yes |
| Editor preview | Native, inline | Read-only preview |
| Click into a child block to edit | Yes — saves to the variant | No — pattern is the source of truth |
| To edit the variant | Open the variant template part in Site Editor | Edit the PHP file in your IDE |
| File-based / CBT-friendly | Yes | Yes |

Choose **template part** when the variant is a flat block tree that translators or content editors will tweak in the Site Editor. Choose **pattern** when the variant needs PHP — typically a footer with `<?php echo date( 'Y' ); ?>`, asset URLs from `get_template_directory_uri()`, translatable strings via WordPress i18n functions, or any dynamic logic.

### Naming convention for pattern variants

Patterns are looked up in the **active theme's pattern registry** by slug:

```
{stylesheet}/{baseSlug}-{lang}
```

For example, with theme `idocs-block-theme`, base slug `footer`, and the FR language:

```
idocs-block-theme/footer-fr
```

Plugin-registered patterns (or patterns from any namespace other than the active theme's stylesheet) are intentionally not supported. Pattern variants are scoped to your theme.

### Authoring a pattern variant

Drop a PHP file in your theme's `patterns/` directory. The `Slug:` header must follow the convention above:

```php
<?php
/**
 * Title: Footer FR
 * Slug: idocs-block-theme/footer-fr
 * Inserter: no
 */
?>
<!-- wp:group {"tagName":"footer", "className":"site-footer"} -->
<footer class="wp-block-group site-footer">
    <!-- wp:paragraph -->
    <p>
        <?php
        printf(
            /* translators: %d: current year */
            esc_html__( '© %d International Documents Canada', 'idocs-block-theme' ),
            (int) date( 'Y' )
        );
        ?>
    </p>
    <!-- /wp:paragraph -->
</footer>
<!-- /wp:group -->
```

Setting `Inserter: no` keeps the variant out of the regular block-inserter UI — these patterns aren't meant to be inserted by hand; the router renders them.

### What the editor shows

For pattern variants, the inspector's "Variant source" radio is set to **Pattern** and the editor renders the parsed pattern blocks **read-only and locked** (`templateLock: 'all'`). You see exactly what the frontend will produce, but cannot click into the blocks to edit them. To make changes, edit the pattern's PHP file and reload the editor.

### What the frontend does

The router resolves the pattern slug, looks it up via `WP_Block_Patterns_Registry`, and renders its content with `do_blocks()`. If the pattern isn't registered, the router renders nothing — same silent behavior as a missing template-part variant.

## The editor experience

### What you see

When you open a template that references `<!-- wp:template-part {"slug":"footer"} /-->`, the editor:

1. Decomposes the template part inline (its native behavior).
2. Encounters the router block inside `parts/footer.html`.
3. Reads the router's `baseSlug` attribute (or auto-detects from the parent template-part block in the editor's block tree, which is also tracked).
4. Computes the target variant slug — `<base>-<previewLang>` — using the **Preview language** value from the inspector (defaults to `en`).
5. Loads the variant template part as an entity via `useEntityBlockEditor`.
6. Renders its blocks inline as native, editable inner blocks.

The result looks and behaves identical to a plain `core/template-part` reference. Layout context, theme.json styles, per-block selection, click-to-edit, drag-and-drop reordering — everything works.

### Inline editing

If you click into a block inside the rendered variant and edit it, the change is saved to the **variant entity** — for example, edits inside the EN preview save to `footer-en`, edits inside the FR preview save to `footer-fr`. This is the same hook (`useEntityBlockEditor`) that core's `core/template-part` block uses, so the data flow is identical.

### Preview language

The inspector exposes a **Preview language** text input. It's free-form (any code WPML reports — `en`, `fr`, `es`, etc.). Changing it switches which variant entity the editor loads, so you can see and edit each language's variant from any template that uses the router.

The preview language is intentionally **not persisted as a block attribute**. It's React state local to your editor session. Persisting it would dirty the post for every editor whose preview preference differs and would be misleading anyway, since the frontend always uses WPML's actual current language regardless of what the editor was last set to.

### Auto-detect in the editor

In the editor, the auto-detect uses the editor's block tree, not the PHP render stack. The block walks `getBlockParents( clientId )` looking for the nearest enclosing `core/template-part` ancestor and reads its `slug`. The detected value appears as the `baseSlug` field's placeholder in the inspector.

## Migrating from WPML-managed template parts

If you've been using WPML to manage per-language template parts in the database and want to switch to file-based, the migration is:

1. **Export each language's content to a file.** Open the template part in the Site Editor, switch to the language, copy the block markup. Save as `parts/<base>-<lang>.html`. Repeat for every language and every template part.
2. **Disable translation for `wp_template_part` and `wp_template`** in WPML (see [Required WPML setup](#required-wpml-setup)).
3. **Replace each host template part's content with the router**:
   ```html
   <!-- wp:tp-router/router {"baseSlug":"<base>"} /-->
   ```
4. **Delete the orphan database rows** WPML left behind (see the cleanup snippet in that section).
5. **Test each language URL** on the frontend.

If you skip step 4, your files will still be overridden by the leftover database rows.

If a particular slot needs PHP — i18n strings, dynamic content, asset URLs — author its variants as patterns instead and switch the corresponding router to **Variant source: Pattern**. You can mix template-part and pattern variants freely across different routers in the same theme.

## Troubleshooting

### "FR page renders an empty footer"

Almost always means a database-stored `wp_template_part` row is overriding your file. Check:

```bash
wp post list --post_type=wp_template_part --post_status=any --fields=ID,post_name,post_status
```

If you see a row whose `post_name` matches your host slug (`footer`) or any of your variant slugs (`footer-fr`), delete it:

```bash
wp post delete <ID> --force
```

Then reload the FR page.

### "EN works, FR loads the EN content"

Means `apply_filters( 'wpml_current_language', 'en' )` is returning `'en'` even on FR pages. Drop a temporary line at the top of `src/blocks/router/render.php` to confirm:

```php
error_log( 'TPR resolved: ' . $base_slug . '-' . apply_filters( 'wpml_current_language', 'en' ) );
```

Hit a FR URL and `tail -f wp-content/debug.log`. If you see `TPR resolved: footer-en` instead of `footer-fr`, the issue is that WPML isn't reporting the right language for that request. Check that WPML is fully activated, that the language switcher works for non-template content (e.g., posts), and that the URL pattern WPML expects matches the URL you're hitting. The router itself is only doing what `wpml_current_language` tells it.

### "Variant is missing — what does the user see?"

For **template-part variants**: `block_template_part( "footer-zz" )` for a non-existent slug renders nothing — no warning, no fallback.

For **pattern variants**: if `WP_Block_Patterns_Registry::is_registered( '{theme}/footer-zz' )` returns false, the router renders nothing. The editor surfaces this as an explicit Placeholder ("Pattern '{slug}' not found…") because it can detect the missing pattern in the patterns store; the frontend stays silent to match the template-part behavior.

If silent fallback isn't what you want, wrap the call in `render.php` to fall back to the canonical slug or a default-language variant. The plugin doesn't do this by default to keep resolution one-step and predictable.

### "Cursor jumps out of the Base Slug field"

Should not happen as of the current build (the Edit component preserves `<InspectorControls>` mount across the placeholder/variant branch flip). If it returns after a refactor, the cause is React unmounting the inspector when the component's root JSX structure changes between renders. Keep `<InspectorControls>` outside any conditional return.

### "Block shows 'Loading theme info…' indefinitely"

Means `useSelect( ( s ) => s( coreStore ).getCurrentTheme()?.stylesheet )` returned `null`. The current theme entity isn't loaded yet. Usually resolves on its own once the editor finishes booting; if it persists, check the browser console for REST errors loading `/wp/v2/themes`.

### "DB has duplicate template-parts with the same `post_name`"

WPML assigned different language codes to each, so as far as WPML was concerned they were different objects sharing a slug. After disabling WPML translation, those duplicates linger and WordPress will pick one arbitrarily (typically the most recently modified). Delete the duplicates you don't want.

## How it works under the hood

### The block

`tp-router/router` is registered via `block.json` in `src/blocks/router/`. It declares two attributes (`baseSlug` for the variant base, `variantType` for the source — `"template-part"` or `"pattern"`), a JS Edit component, no save output, and a server `render` reference to `render.php`.

### The frontend render

`render.php` is the block's server render. It resolves the base slug:

```php
$base_slug = '' !== ( $attributes['baseSlug'] ?? '' )
    ? (string) $attributes['baseSlug']
    : (string) ( \Template_Parts_Router\Render_Stack::peek() ?? '' );
```

If empty, it returns nothing. Otherwise it reads the language and dispatches by `variantType`:

```php
$variant_slug = "{$base_slug}-{$lang}";

if ( 'pattern' === $variant_type ) {
    $pattern_slug = get_stylesheet() . '/' . $variant_slug;
    $registry     = \WP_Block_Patterns_Registry::get_instance();
    if ( $registry->is_registered( $pattern_slug ) ) {
        echo do_blocks( $registry->get_registered( $pattern_slug )['content'] );
    }
    return;
}

block_template_part( $variant_slug );
```

`block_template_part()` does the file lookup and emits the variant's HTML for template-part variants. For pattern variants, the registry lookup happens against the active theme's namespace only.

### The render stack

`Render_Stack` (in `classes/class-render-stack.php`) hooks `pre_render_block` and `render_block` to maintain a static stack of `core/template-part` slugs being rendered:

```php
// Push on the way in:
add_filter( 'pre_render_block', function( $pre, $block ) {
    if ( 'core/template-part' === $block['blockName'] ) {
        self::$stack[] = $block['attrs']['slug'] ?? '';
    }
    return $pre;
} );

// Pop on the way out:
add_filter( 'render_block', function( $html, $block ) {
    if ( 'core/template-part' === $block['blockName'] ) {
        array_pop( self::$stack );
    }
    return $html;
} );
```

PHP is single-threaded, so the stack is safe. Nesting works correctly. `pre_render_block` always pairs with `render_block` even when the block render is short-circuited by another filter.

### The editor edit

`edit.js` resolves the base slug from the attribute, falling back to a parent-walk on the editor's block tree:

```js
const parentIds = getBlockParents( clientId );
for ( let i = parentIds.length - 1; i >= 0; i-- ) {
    const block = getBlock( parentIds[ i ] );
    if ( block?.name === 'core/template-part' ) {
        foundSlug = block.attributes?.slug;
        break;
    }
}
```

For **template-part variants**, it constructs the entity ID as `${theme}//${base}-${previewLang}` and hands it to `useEntityBlockEditor`, which gives back `[blocks, onInput, onChange]` bound to that variant. Those go into `useInnerBlocksProps`, which renders the variant's blocks inline and saves edits back to the variant entity.

For **pattern variants**, it pulls the registered patterns via `select( coreStore ).getBlockPatterns()`, finds the one whose `name` matches `${theme}/${base}-${previewLang}`, parses its `content` string into blocks via `parse()` from `@wordpress/blocks`, and hands those blocks to `useInnerBlocksProps` with `templateLock: 'all'`. The same inline rendering, with edits locked.

The InspectorControls always renders, regardless of whether an entity or pattern has resolved, so the React tree stays stable across the placeholder→variant transition. (This was the cause of the now-fixed cursor-jump bug — see [Troubleshooting](#troubleshooting).)

## Limitations

- **WPML only**, currently. Adapting the language source to Polylang or another plugin is one filter substitution in `render.php` and one selector swap in `edit.js`, but no UI is built for it in this release.
- **No fallback chain.** If `parts/footer-fr.html` (or `idocs-block-theme/footer-fr` for pattern variants) doesn't exist, nothing renders. Add a check in `render.php` if you need a fallback to the canonical slug.
- **Editor preview is single-language at a time.** The Preview language input switches one variant in/out — there's no side-by-side comparison view. The frontend always reflects the actual WPML language.
- **The plugin assumes the host template part is referenced via `core/template-part`.** If your theme calls `block_template_part()` directly from PHP (e.g., from a custom function or shortcode), the render stack may not capture the slug, and you'll need to set `baseSlug` explicitly.
- **Pattern variants are theme-scoped.** Only patterns under your active theme's namespace (`{stylesheet}/...`) are looked up. Plugin-registered patterns are out of scope by design.
- **Pattern variants are read-only in the editor.** Patterns aren't editable entities. To change a pattern variant, edit the PHP file directly.
