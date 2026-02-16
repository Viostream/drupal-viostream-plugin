# Viostream for Drupal/GovCMS

A Drupal module that enables you to seamlessly embed Viostream videos in your Drupal or GovCMS website.

## Description

The Viostream module provides a field formatter that allows you to embed Viostream videos directly into your Drupal/GovCMS content. Simply add a Link or Text field to any content type and configure it to use the Viostream Video formatter.

## Features

- **Easy Integration**: Works with standard Drupal Link and Text fields
- **Responsive Design**: Optional responsive video player with 16:9 aspect ratio
- **Customizable Player**: Configure width, height, autoplay, mute, and controls
- **Multiple URL Formats**: Supports various Viostream URL formats or direct video IDs
- **GovCMS Compatible**: Fully compatible with GovCMS distributions

## Requirements

- Drupal 8, 9, 10, or 11
- Field module (core)
- Link module (core)

## Installation

### Using Composer (Recommended)

```bash
composer require viostream/govcms-viostream-plugin
```

### Manual Installation

1. Download the module
2. Extract to your `modules/contrib` directory
3. Enable the module:
   ```bash
   drush en viostream
   ```
   Or via the Drupal admin interface: Admin → Extend → Enable "Viostream"

## Usage

### Setting up a Video Field

1. **Add a Link Field** to your content type:
   - Navigate to: Admin → Structure → Content types → [Your content type] → Manage fields
   - Click "Add field"
   - Select "Link" as the field type
   - Name it (e.g., "Viostream Video")
   - Save the field settings

2. **Configure the Display Format**:
   - Go to: Admin → Structure → Content types → [Your content type] → Manage display
   - Find your Viostream video field
   - Change the Format to "Viostream Video"
   - Click the settings gear icon to configure:
     - **Width**: Set player width (e.g., 100%, 640px)
     - **Height**: Set player height in pixels (e.g., 400)
     - **Responsive**: Enable for responsive 16:9 aspect ratio
     - **Autoplay**: Auto-start video on page load
     - **Muted**: Mute video by default
     - **Show controls**: Display player controls
   - Save your settings

3. **Add Video Content**:
   - Create or edit content
   - In your Viostream video field, enter one of:
     - Full Viostream URL: `https://play.viostream.com/VIDEO_ID`
     - Video URL: `https://viostream.com/video/VIDEO_ID`
     - App URL: `https://app.viostream.com/video/VIDEO_ID`
     - Just the Video ID: `VIDEO_ID`
   - Save your content

### Supported URL Formats

The module recognizes these Viostream URL formats:
- `https://play.viostream.com/{VIDEO_ID}`
- `https://viostream.com/video/{VIDEO_ID}`
- `https://app.viostream.com/video/{VIDEO_ID}`
- Direct video ID: `{VIDEO_ID}`

## Configuration Options

### Field Formatter Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Width | Player width (%, px, or CSS units) | 100% |
| Height | Player height in pixels | 400 |
| Responsive | Enable responsive 16:9 aspect ratio | Yes |
| Autoplay | Auto-start video playback | No |
| Muted | Mute video by default | No |
| Show controls | Display player controls | Yes |

## Examples

### Responsive Video (Recommended)

```
Field configuration:
- Width: 100%
- Height: 400 (ignored when responsive)
- Responsive: ✓
- Autoplay: □
- Muted: □
- Show controls: ✓
```

### Fixed Size Video

```
Field configuration:
- Width: 640px
- Height: 360
- Responsive: □
- Autoplay: □
- Muted: □
- Show controls: ✓
```

### Auto-playing Muted Video

```
Field configuration:
- Width: 100%
- Height: 400
- Responsive: ✓
- Autoplay: ✓
- Muted: ✓
- Show controls: ✓
```

## Troubleshooting

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

## Support

For issues, questions, or contributions:
- GitHub Issues: https://github.com/Viostream/govcms-viostream-plugin/issues
- Viostream API Documentation: https://api.app.viostream.com/swagger/index.html

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

Developed for seamless integration of Viostream videos with Drupal and GovCMS.

## References

- [Drupal Documentation](https://www.drupal.org/documentation)
- [GovCMS](https://github.com/govCMS/govCMS)
- [Viostream API](https://api.app.viostream.com/swagger/index.html)
