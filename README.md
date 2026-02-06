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
