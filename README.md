<<<<<<< HEAD
# WPSD Site Health

WPSD Site Health adds a **WP Bloat** tab to **Tools → Site Health** in WordPress. It shows how much PHP code plugins, themes, and WordPress core load while serving that page, together with the CSS and JavaScript enqueued by plugins and themes.

Use it to investigate backend overhead, compare plugins that offer similar features, and check whether your own code loads selectively. A small feature can carry a large amount of code; this tool makes that cost easier to inspect.

The goal is awareness and inspire better development decisions. Results are starting points for investigation, not proof that a component is responsible for a slow website.

## What the plugin shows

- **A sortable overview** of plugins, themes, and WordPress core, with component names and versions where available.
- **PHP file counts and OPcache sizes** at an early stage of the request and again at shutdown.
- **Plugin and theme load durations** in milliseconds.
- **PHP file breakdowns** showing the files present at the early snapshot and the additional files loaded by shutdown.
- **CSS/JS counts and sizes** alongside each plugin or theme, with a separate list of resource URLs.
- **Current page timing**, displayed as TTFB in the report.
- **Optional research contributions**, with a choice of which components to include.

PHP and asset information are presented together in one WP Bloat tab. Click a component name to jump to its PHP file breakdown, or a column heading to change the sort order within each group. Results initially sort by load duration, highest first.

## Installation and use

1. Download this repository and place the plugin folder in `wp-content/plugins/`, or upload a ZIP containing the plugin folder through **Plugins → Add New → Upload Plugin**.
2. Activate **WPSD Site Health**.
3. Open **Tools → Site Health → WP Bloat** with an account allowed to view Site Health checks, normally an administrator.
4. Review the largest file counts, OPcache sizes, and load durations. Follow component names to inspect their files, then check the CSS/JS resource list.
5. Repeat under comparable conditions after changing a plugin, theme, or version.

The plugin header declares WordPress **5.9 or later** and PHP **7.4 or later**. JavaScript must be enabled to display the report. OPcache is optional; unavailable or restricted OPcache data is shown as **N/A**, while the other measurements remain available.

On activation, the plugin moves itself to the front of the site's active plugin list so it can observe plugins loaded after it. Other tools that change plugin loading order can affect the measurements.

## Reading the measurements

### Early snapshot → shutdown

File count and OPcache columns show two values, such as `8 → 24`. These are two stages of the **same request**, not a comparison with a previous visit or plugin version.

| Group | Early snapshot | Final snapshot |
| --- | --- | --- |
| Plugins | End of `plugins_loaded` | `shutdown` |
| Themes | End of `after_setup_theme` | `shutdown` |
| WordPress core | End of `plugins_loaded` | `shutdown` |

The PHP breakdown lists the files included at the early snapshot separately from files added later. File counts come from PHP's included-file list, so they describe files actually loaded during this request rather than every PHP file installed on disk.

### OPcache size

OPcache stores compiled PHP code in shared memory. The report adds up the OPcache memory reported for each component's included files. This is not the component's total runtime memory usage or PHP source size.

When permitted, the plugin requests an **OPcache reset when Site Health loads**. This can affect cache warmth and timing for requests sharing that cache. Cache settings and availability also affect the reported sizes; a low or zero value does not necessarily mean the component has no caching cost.

### Load duration

Plugin duration estimates the interval between successive `plugin_loaded` callbacks. Theme duration covers the setup interval from `setup_theme` to the plugin's `after_setup_theme` timing callback. Values are rounded to whole milliseconds.

These measurements describe bootstrap work. They do not include every callback a component runs later in the request, and they do not provide function-level profiling. Theme timing is a shared setup interval rather than an independent measurement for each parent or child theme. WordPress core has no separate load-duration measurement.

### CSS and JavaScript

The report groups queued resources by plugin or theme URLs and shows CSS and JavaScript counts separately. Where a resource maps to a readable local file under `wp-content`, it also reports its size; unavailable sizes appear as **N/A**.

Sizes are local file sizes, not compressed network transfer sizes. This is a view of the enqueue queues, not a complete browser network audit: dependency-only resources, inline code, external assets, and URLs outside the recognized plugin/theme paths may be absent. The diagnostic plugin's own assets are excluded.

An asset appearing on Site Health is worth reviewing, but its presence alone does not establish that it is unnecessary.

### Page timing and indicators

The value labeled **TTFB** is elapsed server time from the request start to the shutdown snapshot. It is not a browser measurement of the first response byte and does not include network latency.

Plugin and theme rows use these built-in thresholds for check and cross indicators:

| Metric | Plugin threshold | Theme threshold |
| --- | --- | --- |
| Included PHP files | 10 | 10 |
| Reported OPcache size | 50 KB | 50 KB |
| Load duration | 2 ms | 5 ms |

A cross means the measured value exceeds the threshold. These are investigation prompts, not universal performance budgets. Legitimate functionality, server speed, and the surrounding installation all influence the results.

## Contributing performance data

Sharing is optional. Viewing a report does not automatically submit its measurements to the research service.

To contribute, open **Contribute to our research**, review the selected plugins, themes, and core entries, and uncheck anything you want to exclude. Each group also has an **Unselect** button. Agree to the displayed privacy policy and choose **Contribute this data** to send the selection to the WP Speed Doctor research endpoint.

The submitted payload contains:

- Selected component names, slugs, and versions.
- Early and shutdown PHP file counts and OPcache sizes.
- Available initial load durations.
- CSS and JavaScript counts.
- The page timing value.
- WordPress locale.

The payload does not include the website URL, user account details, PHP file breakdowns, asset URLs, or asset sizes. Review custom plugin and theme names before sharing if they contain private information. The receiving server sees the sending server's IP address as part of the connection; it is not a field in the performance payload.

The current implementation limits successful submissions to once per hour per site outside development environments.

## Scope and limitations

- **This is a Site Health snapshot.** It does not measure frontend pages, checkout, REST requests, or every admin screen. Use the findings to guide further testing in the contexts that matter to your site.
- **This is a diagnostic tool, not an optimizer or full profiler.** It does not remove assets, disable plugins, or identify expensive functions and database queries.
- **File attribution depends on paths.** The report recognizes standard `wp-content/plugins/` and `wp-content/themes/` locations. Must-use plugins, drop-ins, custom layouts, and symlinks may not be represented accurately. Core rows cover `wp-admin` and `wp-includes`, not every core file.
- **Timing depends on the environment.** Load order, OPcache state, hosting resources, and other activity affect results. A displayed `0 ms` can simply mean a duration rounded below one millisecond.
- **Multisite needs extra care.** Network-activated plugins are included in metadata collection, but network loading order can affect coverage and timing. A simple single-site installation is the clearest starting point for comparisons.

## Development and roadmap

Version **1.6** brings back shutdown collection, combines PHP and CSS/JS reporting in the WP Bloat tab, adds sortable results, and introduces optional data sharing with component selection. See [the changelog](changelog.log) for release history.

Research data collection is available now. Public visualizations are under construction. Planned work includes historical comparisons across plugin and WordPress core versions, making it easier to see whether updates increase or reduce overhead. The current plugin report does not provide historical graphs.

## Author and license

Developed by [WP Speed Doctor](https://wpspeeddoctor.com/).

Licensed under the GNU General Public License, version 3. See [LICENSE](LICENSE).
=======
# Site Health addon by WP Speed Doctor

Adds into Site Health menu tabs with bloated PHP plugins and CSS/JS files.

A diagnostic tool that reveals **PHP, OPCache, and CSS/JS bloat** caused by WordPress plugins or themes.

The goal is simple:  
make invisible backend waste **visible, measurable, and actionable**.

### This tool is about **awareness**, not blame.

---

## Roadmap for this plugin

- Collect historical data and visualize the bloat evolution of plugins over time
- Show whether each plugin update makes it lighter, heavier, or unchanged
- Display trend graphs instead of single snapshots
- WordPress core will be tracked the same way
- Core updates are not immune to bloat, and should be measurable like everything else

Performance is not a one-time achievement —
it is a continuous discipline.

---

## Why this exists

WordPress performance discussions are usually focused on **front-end metrics** (LCP, CLS, JS size).  
Meanwhile, a large part of the slowdown happens **before HTML is even sent**:

- Too many PHP files loaded
- Excessive OPCache memory usage
- Plugins loading admin assets where they are not needed
- Unnecessary code executed on every request

This tool exposes that hidden cost.

---

## What it does

### For WordPress users

- Identify **which plugins and themes slow down your site**
- Warns you which plugins are loading excessive PHP files and unnecessary CSS/JS
- Shows when a theme behaves just like a bloated plugin
- Reveals hidden backend cost even when everything looks lightweight
- Detect plugins that load **CSS/JS on pages where they should not exist**
- Understand why a “lightweight” site still has poor TTFB
- Helps you separate marketing claims from actual runtime behavior

### For WordPress developers

- Get **direct feedback** on whether your plugin implements **selective code loading correctly**
- Validate architectural decisions with measurable data
- Improve plugin quality beyond “it works”

---

## What is measured

### PHP Bloat
- Number of PHP files loaded per plugin/theme
- OPCache memory consumed per component
- Execution time contribution during **plugin_load** hook when most bloat is loaded

### CSS / JS Bloat
- List of enqueued assets that **should not be present** on the current page

---

## Example insights

- A plugin with **10× more PHP files** than expected
- A small utility plugin consuming **megabytes of OPCache**
- Backend bloat that directly correlates with higher TTFB

---

## Why backend bloat matters

Backend inefficiency is not just a performance issue.

It also means:
- Higher **CPU usage**
- More **energy consumption**
- Increased **hosting costs**
- Reduced scalability under load

Every unnecessary PHP file and every wasted millisecond is paid for — on every request.

---

## Philosophy

- Backend performance matters as much as frontend
- “Fast enough” is not fast
- If the code runs, it should have a reason
- Selective loading is not optional — it is responsible development

This tool is about **awareness**, not blame.

---

## Who should use this

- Plugin developers who care about quality
- Performance-focused WordPress users
- Users building a new WordPress site who want to choose well-coded plugins from the start
- Anyone who wants to verify marketing claims like “lightweight” or “performance-focused” with real data
- Site owners comparing multiple plugins or themes that claim to solve the same problem
- Developers who want direct, measurable feedback on how their code behaves in real environments
- Agencies and consultants who need to justify technical decisions with evidence, not opinions

If performance matters, assumptions are not enough — measurement is required.
---

## Shortfalls of this test

- This tool is not a profiler and does not perform deep runtime or function-level analysis
- Results are heuristic-based, not the absolute truth
- Some plugins legitimately execute logic on pages like Site Health, which may result in a false-positive warning
- Plugins that manipulate plugin load order or bootstrap sequence can interfere with accurate impact measurement
- There could be many other reasons why the website is slow, this is just the simplest, yet very effective way to identify the source of the slow website
- WordPress Multisite environments may produce unreliable or misleading results
→ for best accuracy, test on a simple single-site installation

This tool highlights suspicious behavior, not definitive guilt.
It is meant to start an investigation, not end it.

---

## Status

This project is actively evolving.  
Metrics, thresholds, and visualizations may change as better insights emerge.

---

## License

GPL-2.0 or later

---

## Author

Built by a developer obsessed with performance and tired of hearing that “WordPress is slow”.

It isn’t.
Poorly written and carelessly loaded code is.
>>>>>>> origin/main
