# Viostream Video Embedding

## About

The Viostream module provides a field formatter to embed Viostream videos in your Drupal/GovCMS website. It allows you to easily add video content from your Viostream account to any content type.

## Features

- **Easy Integration**: Works seamlessly with Drupal's Link and Text fields
- **Flexible Configuration**: Customize player size, responsiveness, and behavior
- **Multiple URL Formats**: Accepts various Viostream URL formats or direct video IDs
- **Responsive Design**: Optional responsive video player with automatic aspect ratio

## Getting Started

### 1. Add a Video Field

To embed Viostream videos:

1. Navigate to **Structure > Content types > [Your content type] > Manage fields**
2. Add a new **Link** field
3. Save the field configuration

### 2. Configure the Formatter

1. Go to **Structure > Content types > [Your content type] > Manage display**
2. For your video field, select **Viostream Video** as the formatter
3. Click the settings icon to configure:
   - Player dimensions (width and height)
   - Responsive mode
   - Autoplay and mute options
   - Control visibility

### 3. Add Videos to Content

When creating or editing content:

1. Enter a Viostream video URL or ID in your video field
2. Supported formats:
   - `https://play.viostream.com/VIDEO_ID`
   - `https://viostream.com/video/VIDEO_ID`
   - `https://app.viostream.com/video/VIDEO_ID`
   - Just the video ID: `VIDEO_ID`
3. Save your content

## Configuration Options

### Player Settings

- **Width**: Set the player width (e.g., 100%, 640px)
- **Height**: Set the player height in pixels (e.g., 400)
- **Responsive**: Enable for responsive 16:9 aspect ratio
- **Autoplay**: Automatically start video playback
- **Muted**: Mute video by default (recommended with autoplay)
- **Show Controls**: Display player controls

## Best Practices

### Responsive Videos

For the best responsive experience:
- Set width to `100%`
- Enable the **Responsive** option
- The player will automatically maintain a 16:9 aspect ratio

### Autoplay Videos

When using autoplay:
- Always enable **Muted** (required by most browsers)
- Consider hiding controls for background videos
- Be mindful of user experience and accessibility

### Performance

- Use responsive mode for mobile-friendly videos
- Consider lazy-loading videos for pages with multiple embeds
- Optimize your Viostream account settings for web delivery

## Troubleshooting

### Video Not Displaying

If your video isn't showing:
- Verify the video ID or URL is correct
- Check that the video is publicly accessible in Viostream
- Ensure the field is configured to use "Viostream Video" formatter
- Clear Drupal cache (Admin > Configuration > Performance > Clear all caches)

### Player Issues

If the player isn't working correctly:
- Check browser console for JavaScript errors
- Verify "Show controls" is enabled in formatter settings
- Test in a different browser to rule out browser-specific issues

### Styling Issues

If the video doesn't look right:
- Check for conflicting CSS in your theme
- Verify responsive mode settings
- Test with the default theme to isolate theme-specific issues

## Additional Resources

- [Viostream API Documentation](https://api.app.viostream.com/swagger/index.html)
- [Drupal Field API](https://www.drupal.org/docs/drupal-apis/entity-api/fields)
- [Module Issue Queue](https://github.com/Viostream/govcms-viostream-plugin/issues)

## Support

For help and support:
- Check the [Quick Start Guide](../docs/QUICK_START.md)
- Visit the [GitHub repository](https://github.com/Viostream/govcms-viostream-plugin)
- Submit issues on [GitHub](https://github.com/Viostream/govcms-viostream-plugin/issues)
