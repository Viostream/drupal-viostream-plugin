# Changelog

All notable changes to the Viostream Drupal/GovCMS module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-02-17

### Added

- **API Client Service** (`ViostreamClient`) — Guzzle-based HTTP client with
  Basic Authentication for the Viostream REST API. Supports listing media
  (paginated, searchable, sortable), fetching media details with dimensions,
  and building embed URLs with autoplay/mute/controls options.
- **Admin Settings Form** (`ViostreamSettingsForm`) — Configuration page at
  `/admin/config/media/viostream` for managing API credentials (Access Key
  and API Key) with connection testing.
- **Media Browser Controller** (`ViostreamMediaBrowserController`) — AJAX
  endpoints for browsing, searching, and fetching detail for Viostream media
  items. Returns JSON responses with proper HTTP error codes.
- **Field Formatter** (`ViostreamFormatter`) — Renders Link fields as
  embedded Viostream video players with configurable width, height, autoplay,
  muted, controls, and responsive mode. Supports dynamic aspect ratios from
  the Viostream API.
- **Field Widget** (`ViostreamBrowserWidget`) — Provides an integrated media
  browser modal for selecting Viostream videos when editing Link fields.
- **CKEditor 5 Plugin** (`ViostreamVideo`) — Toolbar button for inserting
  Viostream videos into rich text content via a dialog with video search and
  selection. Built with Webpack, externalising CKEditor 5 core packages.
- **Text Filter** (`ViostreamVideoFilter`) — Processes `<viostream-video>`
  HTML tags in filtered text, converting them to responsive iframe embeds
  with dimensions from the API.
- **Permissions** — `administer viostream` (restricted) and
  `browse viostream media`.
- **Config schema** for module settings, field formatter settings, and field
  widget settings.
- **Twig templates** for video player embed (`viostream-video.html.twig`) and
  media browser interface (`viostream-media-browser.html.twig`).
- **JavaScript assets** — ES5 Drupal behaviors for the media browser and
  field widget, with XSS-safe HTML escaping helpers.
- **CSS assets** — Styles for the video embed, media browser, field widget,
  and CKEditor integration.
- **Docker development environment** — Dockerfile and scripts for running a
  local Drupal instance with the module pre-installed.
- **Comprehensive unit test suite** — 145 PHPUnit tests covering all 7
  source classes at 99.05% line coverage.
- **Documentation** — README, INSTALL.txt, quick start guide, usage
  examples, and Drupal help page integration.
