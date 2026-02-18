# Viostream for Drupal/GovCMS

[![Build Status](https://github.com/Viostream/govcms-viostream-plugin/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/Viostream/govcms-viostream-plugin/actions)
[![Coverage Status](https://codecov.io/gh/Viostream/drupal-viostream-plugin/branch/main/graph/badge.svg)](https://codecov.io/gh/Viostream/drupal-viostream-plugin)
[![Packagist](https://img.shields.io/packagist/v/viostream/drupal-viostream-plugin)](https://packagist.org/packages/viostream/drupal-viostream-plugin)
[![Packagist Downloads](https://img.shields.io/packagist/dt/viostream/drupal-viostream-plugin)](https://packagist.org/packages/viostream/drupal-viostream-plugin)
[![PHP Version](https://img.shields.io/packagist/php-v/viostream/drupal-viostream-plugin)](https://packagist.org/packages/viostream/drupal-viostream-plugin)
[![License](https://img.shields.io/packagist/l/viostream/drupal-viostream-plugin)](LICENSE)
[![Issues](https://img.shields.io/github/issues/Viostream/drupal-viostream-plugin)](https://github.com/Viostream/drupal-viostream-plugin/issues)
[![Last Commit](https://img.shields.io/github/last-commit/Viostream/drupal-viostream-plugin?logo=github)](https://github.com/Viostream/drupal-viostream-plugin/commits/main)

A Drupal module that integrates with the Viostream API to let you browse, search, and embed Viostream videos in your Drupal or GovCMS website.

## Description

The Viostream module connects your Drupal site to the Viostream platform via its REST API. Content editors can browse and search their Viostream media library directly from within Drupal, select videos, and embed them into content fields. The module also provides a field formatter for rendering embedded Viostream video players with configurable options.

## Features

- **Viostream API Integration**: Full API v3 client connecting to your Viostream account using HTTP Basic Authentication
- **Admin Connection Settings**: Configuration form to enter API credentials with a Test Connection button
- **CKEditor 5 Plugin**: Toolbar button to browse and embed Viostream videos inline within rich text content (Drupal 10+)
- **Media Browser Widget**: Field widget with a modal-based media browser for searching, browsing, and selecting videos
- **Standalone Media Browser**: A dedicated page for browsing your full Viostream media library
- **Text Filter**: Converts `<viostream-video>` tags to embedded iframes on display
- **Field Formatter**: Renders Viostream videos as embedded iframes with configurable player settings
- **Responsive Design**: Optional responsive video player with 16:9 aspect ratio
- **Customizable Player**: Configure width, height, autoplay, mute, and controls
- **Share Link Support**: Supports share.viostream.com URLs for video embedding
- **GovCMS Compatible**: Fully compatible with GovCMS distributions

## Requirements

- Drupal 10 or 11
- Field module (core)
- Filter module (core)
- Link module (core)
- System module (core)
- CKEditor 5 module (core, Drupal 10+ only) - required for the CKEditor toolbar integration
- A Viostream account with API access (Access Key and API Key from Developer Tools)

## Installation

### Using Composer (Recommended)

```bash
composer require viostream/drupal-viostream-plugin
```

### Manual Installation

1. Download the module
2. Extract to your `modules/contrib` directory
3. Enable the module:
   ```bash
   drush en viostream
   ```
   Or via the Drupal admin interface: Admin > Extend > Enable "Viostream"

## Configuration

### Step 1: Connect to Viostream API

Before you can browse and select videos, you need to configure your Viostream API credentials:

1. Navigate to **Admin > Configuration > Media > Viostream Settings**
   (or visit `/admin/config/media/viostream`)
2. Enter your **Access Key** (starts with `VC-`) from Viostream Developer Tools
3. Enter your **API Key** from Viostream Developer Tools
4. Click **Test Connection** to verify your credentials work
5. Click **Save configuration**

The credentials are stored securely in Drupal configuration. The Test Connection button calls the Viostream Account API to verify the credentials are valid.

### Step 2: Set Up a Video Field

There are three ways to embed Viostream videos in your content:

#### Option A: CKEditor 5 Inline Embed (Recommended for Drupal 10+)

Embed Viostream videos directly within rich text content using the CKEditor 5 toolbar:

1. **Enable the Text Filter**:
   - Navigate to: Admin > Configuration > Content authoring > Text formats and editors
   - Edit your text format (e.g., "Full HTML" or "Basic HTML")
   - Under **Enabled filters**, check **Viostream Video Embed**
   - Save the text format

2. **Add the Toolbar Button**:
   - On the same text format configuration page, in the **CKEditor 5 Toolbar** section, drag the **Viostream Video** button into the toolbar
   - The button shows a video icon and will appear after you enable the filter
   - Save the text format

3. **Embed Videos in Content**:
   - Create or edit any content with a rich text field using that text format
   - Click the **Viostream Video** button in the CKEditor toolbar
   - A modal opens showing your Viostream media library
   - Search, sort, and paginate to find the video you want
   - Click a video to select it, then click **Select Video**
   - The video appears as a preview widget in the editor
   - Save your content - the video renders as an embedded player

#### Option B: Media Browser Widget

Use the Viostream Browser widget to let editors browse and select videos visually:

1. **Add a Link Field** to your content type:
   - Navigate to: Admin > Structure > Content types > [Your content type] > Manage fields
   - Click "Add field"
   - Select "Link" as the field type
   - Name it (e.g., "Viostream Video")
   - Save the field settings

2. **Configure the Form Widget**:
   - Go to: Admin > Structure > Content types > [Your content type] > Manage form display
   - Find your Viostream video field
   - Change the Widget to **Viostream Browser**
   - Save

3. **Configure the Display Formatter**:
   - Go to: Admin > Structure > Content types > [Your content type] > Manage display
   - Find your Viostream video field
   - Change the Format to **Viostream Video**
   - Click the settings gear icon to configure player options (width, height, responsive, autoplay, muted, controls)
   - Save your settings

4. **Add Video Content**:
   - Create or edit content
   - Click the **Browse Viostream** button on the video field
   - A modal opens showing your Viostream media library
   - Search by title, sort results, and paginate through videos
   - Click a video to select it
   - The field is populated with `https://share.viostream.com/{video_key}`
   - Save your content

#### Option C: Manual URL Entry

Use the standard Link widget and enter Viostream URLs manually:

1. Add a Link field as described above
2. Keep the default Link widget in Manage form display
3. Configure the Viostream Video formatter in Manage display
4. When creating content, manually enter a `https://share.viostream.com/{VIDEO_ID}` URL

### Standalone Media Browser

A standalone media browser page is available at **Admin > Content > Browse Viostream Media** (or visit `/admin/content/viostream/browse`). This lets administrators browse the full Viostream media library without being in a content editing context.

## Permissions

The module defines two permissions:

| Permission                 | Description                                                     |
| -------------------------- | --------------------------------------------------------------- |
| **Administer Viostream**   | Access the Viostream settings form to configure API credentials |
| **Browse Viostream media** | Access the media browser to search and select videos            |

## Configuration Options

### Field Formatter Settings

| Setting       | Description                         | Default |
| ------------- | ----------------------------------- | ------- |
| Width         | Player width (%, px, or CSS units)  | 100%    |
| Height        | Player height in pixels             | 400     |
| Responsive    | Enable responsive 16:9 aspect ratio | Yes     |
| Autoplay      | Auto-start video playback           | No      |
| Muted         | Mute video by default               | No      |
| Show controls | Display player controls             | Yes     |

### API Configuration

| Setting    | Description                                   |
| ---------- | --------------------------------------------- |
| Access Key | Your Viostream Access Key (starts with `VC-`) |
| API Key    | Your Viostream API Key from Developer Tools   |

## Supported URL Formats

The field formatter recognizes share.viostream.com URLs:

- `https://share.viostream.com/{VIDEO_ID}`
- `http://share.viostream.com/{VIDEO_ID}`

## Architecture

### Module Structure

```
viostream/
├── viostream.info.yml              # Module metadata and dependencies
├── viostream.module                # Hook implementations (theme, help)
├── viostream.ckeditor5.yml         # CKEditor 5 plugin definition
├── viostream.services.yml          # Service definitions (API client)
├── viostream.routing.yml           # Route definitions
├── viostream.links.menu.yml        # Admin menu links
├── viostream.permissions.yml       # Permission definitions
├── viostream.libraries.yml         # JS/CSS library definitions
├── config/
│   ├── install/
│   │   └── viostream.settings.yml  # Default configuration
│   └── schema/
│       └── viostream.schema.yml    # Configuration schema
├── src/
│   ├── Client/
│   │   └── ViostreamClient.php     # Viostream API v3 client service
│   ├── Controller/
│   │   └── ViostreamMediaBrowserController.php  # Media browser endpoints
│   ├── Form/
│   │   └── ViostreamSettingsForm.php            # Admin settings form
│   └── Plugin/
│       ├── CKEditor5Plugin/
│       │   └── ViostreamVideo.php               # CKEditor 5 plugin (dynamic config)
│       ├── Field/
│       │   ├── FieldFormatter/
│       │   │   └── ViostreamFormatter.php       # Video embed formatter
│       │   └── FieldWidget/
│       │       └── ViostreamBrowserWidget.php   # Media browser widget
│       └── Filter/
│           └── ViostreamVideoFilter.php         # Text filter for <viostream-video>
├── js/
│   ├── ckeditor5_plugins/
│   │   └── viostreamVideo/
│   │       ├── src/                # CKEditor 5 plugin ES module source
│   │       │   ├── index.js
│   │       │   ├── viostreamvideo.js
│   │       │   ├── viostreamvideoediting.js
│   │       │   ├── viostreamvideoui.js
│   │       │   ├── insertviostreamvideocommand.js
│   │       │   └── icon.svg
│   │       ├── build/
│   │       │   └── viostreamVideo.js  # Compiled plugin bundle
│   │       ├── webpack.config.js
│   │       └── package.json
│   ├── viostream-browser.js        # Standalone browser page JS
│   └── viostream-widget.js         # Widget modal browser JS
├── css/
│   ├── viostream.css               # Video embed styles
│   ├── viostream-browser.css       # Media browser / modal styles
│   ├── viostream-ckeditor.css      # CKEditor 5 widget styles
│   └── viostream-widget.css        # Field widget styles
└── templates/
    ├── viostream-video.html.twig              # Video player template
    └── viostream-media-browser.html.twig      # Media browser page template
```

### API Client

The `ViostreamClient` service (`viostream.client`) provides methods for all Viostream API v3 endpoints:

- **Account**: `getAccount()` - retrieve account details (used for connection testing)
- **Media**: `listMedia()`, `getMedia()`, `ingestMedia()`, `getMediaStatus()` - manage video assets
- **Channels**: `listChannels()`, `getChannel()`, `getChannelsByIds()` - manage channels
- **Tags**: `listTags()`, `getTagUsage()` - manage tags
- **Whitelists**: Full CRUD for whitelists, domains, and media associations

## Examples

### Responsive Video (Recommended)

```
Field configuration:
- Widget: Viostream Browser
- Formatter: Viostream Video
- Width: 100%
- Height: 400 (ignored when responsive)
- Responsive: Yes
- Autoplay: No
- Muted: No
- Show controls: Yes
```

### Fixed Size Video

```
Field configuration:
- Width: 640px
- Height: 360
- Responsive: No
- Autoplay: No
- Muted: No
- Show controls: Yes
```

### Auto-playing Muted Video

```
Field configuration:
- Width: 100%
- Height: 400
- Responsive: Yes
- Autoplay: Yes
- Muted: Yes
- Show controls: Yes
```

## Troubleshooting

### API Connection Fails

- Verify your Access Key starts with `VC-`
- Check that your API Key is correct (from Viostream Developer Tools)
- Ensure your server can reach `https://api.app.viostream.com`
- Try the Test Connection button on the settings page for diagnostic messages

### Browse Viostream button doesn't appear

- Ensure the field widget is set to **Viostream Browser** (not the default Link widget)
- Clear Drupal cache: `drush cr`
- Check that the user has the **Browse Viostream media** permission

### Media browser shows no results

- Verify API credentials are configured at Admin > Configuration > Media > Viostream Settings
- Test the connection using the Test Connection button
- Ensure your Viostream account has media assets

### Video doesn't display

- Verify the video ID or URL is correct
- Check that the video is accessible and not private
- Ensure the field is configured to use "Viostream Video" formatter

### Player controls don't work

- Ensure "Show controls" is enabled in formatter settings
- Check browser console for JavaScript errors

### Responsive sizing issues

- Make sure "Responsive" is enabled in formatter settings
- Verify no conflicting CSS is affecting the video wrapper

## Development

### Running Tests

```bash
# PHPUnit tests (if available)
phpunit --group viostream

# Code standards check
phpcs --standard=Drupal modules/contrib/viostream
```

### Running Locally

_Make sure you run npm build on the ckeditor5 plugin if you make changes to the
JS source files._

```bash
docker build -t viostream-drupal .
docker run --rm \
  -v $(pwd)/js/ckeditor5_plugins/viostreamVideo/build:/var/www/html/web/modules/contrib/viostream/js/ckeditor5_plugins/viostreamVideo/build \
  -v $(pwd)/css:/var/www/html/web/modules/contrib/viostream/css \
  -p 8080:80 viostream-drupal
```

See [docker/README.md](docker/README.md) for detailed Docker setup instructions.

### Building CKEditor 5 Plugin JS

Whenever you edit files in `js/ckeditor5_plugins/viostreamVideo/src/` (the CKEditor plugin source), you **must** re-run the JS build before running or committing your code:

```bash
cd js/ckeditor5_plugins/viostreamVideo
npm install   # if not already
npm run build
```

This command regenerates `build/viostreamVideo.js` (the actual plugin loaded by Drupal and CKEditor).

> **Important:** Docker image builds **do not** automatically rebuild the plugin JS. If you modify plugin sources but skip this step, your changes (including bugfixes, new features, or error handling improvements) **will not take effect**.

If you ever find that plugin changes don't appear in the editor UI, confirm you have built and committed updated `build/viostreamVideo.js`.

#### Pre-commit Safety Check

A `.git/hooks/pre-commit` script is provided and is enabled by default:

- It always runs `npm run build` in `js/ckeditor5_plugins/viostreamVideo` prior to commit.
- If the build bundle changes (`build/viostreamVideo.js` modified), your commit is blocked. Run:
  ```bash
  git add js/ckeditor5_plugins/viostreamVideo/build/viostreamVideo.js
  git commit
  ```
- This prevents stale frontend bundles from ever being committed.

If you are on Windows or need to re-install the hook, copy or symlink `.git/hooks/pre-commit` from the repository root.

## Support

For issues, questions, or contributions:

- GitHub Issues: https://github.com/Viostream/govcms-viostream-plugin/issues
- Viostream API Documentation: https://help.viostream.com/api/viostream-api

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

Developed for seamless integration of Viostream videos with Drupal and GovCMS.

## References

- [Drupal Documentation](https://www.drupal.org/documentation)
- [GovCMS](https://github.com/govCMS/govCMS)
- [Viostream API](https://help.viostream.com/api/viostream-api)
