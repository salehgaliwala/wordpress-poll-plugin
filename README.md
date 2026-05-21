# CTS Daily Poll – WordPress Poll Plugin

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue)](https://github.com/salehgaliwala/wordpress-poll-plugin)
[![License](https://img.shields.io/badge/License-GPLv2-green)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.0-orange)](https://github.com/salehgaliwala/wordpress-poll-plugin/releases)

A lightweight **WordPress daily poll plugin** that lets you create, manage, and embed polls anywhere on your site using simple shortcodes. Built entirely with native WordPress APIs — Custom Post Types, custom database tables, AJAX, and shortcodes.

**Perfect for:** bloggers, news sites, community websites, membership sites, and anyone who wants to add interactive polls to their WordPress site without bloat.

---

## Features

- ✅ **Daily Poll System** – Set a poll per day; the plugin automatically shows today's active poll or falls back to the most recent one
- ✅ **Shortcode Embedding** – Use `[daily_poll]` to embed the active poll anywhere (posts, pages, widgets, templates)
- ✅ **Historical Results** – Use `[poll_results]` to display a paginated table of past poll results
- ✅ **AJAX Voting** – Votes are submitted without page reload — smooth, fast user experience
- ✅ **Animated Results Bars** – CSS-only animated bar charts show vote percentages in real time
- ✅ **Duplicate Voting Prevention** – Three layers: IP address check, logged-in user ID check, and browser cookie
- ✅ **Rate Limiting** – Prevents vote spam (max 5 votes per IP per 10 seconds)
- ✅ **Voter Privacy** – IP addresses are stored but never displayed publicly
- ✅ **Logged-in & Anonymous Voting** – Supports both registered users (by user ID) and guests (by IP)
- ✅ **Custom Post Type** – Polls are managed as a dedicated CPT with its own admin menu
- ✅ **Admin Columns** – Quick overview of poll dates, active/closed status, vote counts, and shortcode copy
- ✅ **Date-Based Sorting** – Sort polls by date in the admin list
- ✅ **Caching** – Vote results are cached via WordPress Transients (5 min) for optimal performance
- ✅ **Fully Localizable** – 49 translatable strings with `.pot` file included; ready for any language
- ✅ **Security Hardened** – Nonce verification, `$wpdb->prepare()`, `esc_html()` / `esc_attr()` on all output
- ✅ **Custom CSS** – Clean, mobile-responsive styling with smooth animations
- ✅ **No Bloat** – Zero external dependencies, no heavy page builders, no premium upsells

---

## Installation

### WordPress Admin (manual)

1. Download the plugin ZIP from [GitHub Releases](https://github.com/salehgaliwala/wordpress-poll-plugin/releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate**

### Manual (FTP)

1. Clone or download this repository:
   ```bash
   git clone https://github.com/salehgaliwala/wordpress-poll-plugin.git
   ```
2. Upload the `cts-poll` folder to `/wp-content/plugins/`
3. Go to **Plugins** in the WordPress admin and activate **"CTS Daily Poll"**

After activation, a new **"Daily Polls"** menu item appears in your WordPress admin sidebar.

---

## Quick Start

### Creating a Poll

1. Go to **Daily Polls → Add New Poll**
2. Enter a **Title** (e.g., "Tuesday Poll")
3. In the **Poll Settings** meta box:
   - Enter the **Poll Question** (e.g., "What's your favorite color?")
   - Add **Poll Options** (use the + button to add more)
   - Set the **Poll Date** — this is the date the poll is tied to
   - Check **Active** to accept votes
4. **Publish** the poll

### Embedding the Active Poll

Insert this shortcode anywhere on your site:

```php
[daily_poll]
```

This automatically displays today's active poll. If no poll exists for today, it shows the most recent active poll.

### Advanced Shortcode Usage

```php
// Show a specific poll by ID
[daily_poll poll_id="42"]

// Always show results (no voting form)
[daily_poll poll_id="42" show_results="always"]

// Custom title
[daily_poll title="Today's Question"]

// Show historical results (last 10 completed polls)
[poll_results]

// Show 25 results per page
[poll_results count="25"]
```

### Finding a Poll's ID

Go to **Daily Polls → All Polls**. Each row shows the shortcode in the **Shortcode** column. You can also hover over a poll title and click the **"Copy Shortcode"** row action.

---

## Shortcode Reference

### `[daily_poll]`

| Attribute     | Default       | Description |
|---------------|---------------|-------------|
| `poll_id`     | `0` (auto)    | Specific poll ID. Leave empty for today's active poll. |
| `show_results`| `after_vote`  | `after_vote` = show results after voting; `always` = always show results |
| `title`       | (poll question) | Custom heading above the poll |

### `[poll_results]`

| Attribute | Default | Description |
|-----------|---------|-------------|
| `count`   | `10`    | Number of completed polls to display per page |

---

## Translating the Plugin

The plugin is fully translation-ready and includes a `.pot` file at `languages/cts-poll.pot`.

To translate into your language:

1. Copy `languages/cts-poll.pot` to `languages/cts-poll-{locale}.po` (e.g., `fr_FR`, `de_DE`, `es_ES`)
2. Translate the strings using [Poedit](https://poedit.net/) or any `.po` editor
3. Save the compiled `.mo` file in the same folder
4. WordPress automatically loads the translation when the site language matches

---

## Development

### File Structure

```
cts-poll/
├── cts-poll.php                          # Main plugin bootstrap
├── README.md                             # This file
├── languages/
│   └── cts-poll.pot                      # Translation template
├── includes/
│   ├── class-cts-poll-post-type.php      # Custom Post Type registration
│   ├── class-cts-poll-meta-boxes.php     # Admin meta boxes (question, options, date, active)
│   ├── class-cts-poll-shortcodes.php     # [daily_poll] and [poll_results] shortcodes
│   ├── class-cts-poll-ajax.php           # AJAX handlers for voting and results
│   └── class-cts-poll-admin.php          # Admin columns, sorting, row actions
├── assets/
│   ├── css/
│   │   └── poll-styles.css               # Front-end styles
│   └── js/
│       └── poll-scripts.js               # Front-end AJAX + admin copy shortcode
└── templates/                            # (reserved for future template overrides)
```

### Database

On activation, the plugin creates a custom table `wp_cts_poll_votes`:

| Column         | Type        | Description |
|----------------|-------------|-------------|
| `id`           | BIGINT      | Primary key |
| `poll_id`      | BIGINT      | Post ID of the poll |
| `option_index` | INT         | Index of the selected option |
| `voter_ip`     | VARCHAR(64) | Voter's IP address |
| `user_id`      | BIGINT      | WordPress user ID (0 for guests) |
| `vote_date`    | DATETIME    | Timestamp of the vote |

---

## Security

| Measure | Implementation |
|---------|---------------|
| **CSRF Protection** | WordPress nonce verified on all AJAX requests |
| **SQL Injection** | All queries use `$wpdb->prepare()` |
| **XSS Prevention** | All output uses `esc_html()` / `esc_attr()` |
| **Input Sanitization** | All POST data sanitized with `sanitize_text_field()` |
| **Rate Limiting** | Max 5 votes per IP per 10-second window |
| **Duplicate Prevention** | IP + user_id + cookie three-layer check |

---

## Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **Database:** MySQL 5.6+ / MariaDB 10.1+

---

## Frequently Asked Questions

### Can I have multiple active polls?

The plugin is designed for one active poll per day. You can create multiple polls (one per date), but only today's poll will be shown by `[daily_poll]`.

### Where are votes stored?

Votes are stored in a dedicated custom database table (`wp_cts_poll_votes`), not in post meta. This ensures fast queries and easy data management.

### Can I see who voted?

The plugin stores IP addresses and user IDs for duplicate detection but does not provide a voter list UI. Vote data is anonymous by design.

### Does it work with caching plugins?

Yes. Vote results are cached via WordPress Transients (5-minute TTL), which work with most caching plugins. The cache is cleared whenever a new vote is cast.

### Is it GDPR compliant?

The plugin stores IP addresses for duplicate prevention — a legitimate interest. You should mention the use of cookies and IP storage in your privacy policy. The plugin does not track users across sessions or share data with third parties.

---

## Changelog

### 1.0.0
- Initial release
- Custom Post Type for polls
- Custom database table for votes
- `[daily_poll]` shortcode with AJAX voting
- `[poll_results]` shortcode with paginated history
- Duplicate prevention (IP + user_id + cookie)
- Rate limiting
- Admin columns and shortcode copy
- Transient caching
- Full localization support (49 strings, .pot file included)

---

## License

This project is licensed under the **GPL v2 or later** — see the [LICENSE](LICENSE) file for details.

---

## Contributing

Contributions are welcome! Please open an [issue](https://github.com/salehgaliwala/wordpress-poll-plugin/issues) or submit a [pull request](https://github.com/salehgaliwala/wordpress-poll-plugin/pulls).

---

## Support

For bugs and feature requests, please use the [GitHub Issues](https://github.com/salehgaliwala/wordpress-poll-plugin/issues) page.