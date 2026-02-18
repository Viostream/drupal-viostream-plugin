# Viostream Video Embedding

## About

The Viostream module integrates your Drupal/GovCMS website with the Viostream video platform. It connects to the Viostream API to let content editors browse, search, and select videos from their Viostream media library, then embeds them as players in content.

## Features

- **API Integration**: Connects to Viostream API v3 using HTTP Basic Authentication
- **Admin Settings**: Configure API credentials with a one-click connection test
- **Media Browser Widget**: Browse, search, and select videos from a modal media browser
- **Standalone Media Browser**: Dedicated page for browsing your full Viostream library
- **Field Formatter**: Renders embedded video players with configurable options
- **Responsive Design**: Optional responsive video player with automatic aspect ratio
- **Flexible Configuration**: Customize player size, autoplay, mute, and controls

## Getting Started

### 1. Configure API Credentials

Before using the media browser, connect your site to the Viostream API:

1. Navigate to **Configuration > Media > Viostream Settings**
2. Enter your **Access Key** (starts with `VC-`) from Viostream Developer Tools
3. Enter your **API Key** from Viostream Developer Tools
4. Click **Test Connection** to verify credentials
5. Click **Save configuration**

### 2. Add a Video Field

To embed Viostream videos:

1. Navigate to **Structure > Content types > [Your content type] > Manage fields**
2. Add a new **Link** field
3. Save the field configuration

### 3. Configure the Widget

Set up the media browser widget for easy video selection:

1. Go to **Structure > Content types > [Your content type] > Manage form display**
2. For your video field, select **Viostream Browser** as the widget
3. Save

This adds a **Browse Viostream** button to the content editing form.

### 4. Configure the Formatter

1. Go to **Structure > Content types > [Your content type] > Manage display**
2. For your video field, select **Viostream Video** as the formatter
3. Click the settings icon to configure:
   - Player dimensions (width and height)
   - Responsive mode
   - Autoplay and mute options
   - Control visibility

### 5. Add Videos to Content

When creating or editing content:

1. Click the **Browse Viostream** button on the video field
2. A modal opens showing your Viostream media library
3. Search for videos by title, sort results, and paginate
4. Click a video thumbnail to select it
5. The field is automatically populated with the video's share URL
6. Save your content

Alternatively, you can manually enter a Viostream share URL:
- `https://share.viostream.com/VIDEO_ID`

## Permissions

| Permission | Description |
| --- | --- |
| Administer Viostream | Access settings form to configure API credentials |
| Browse Viostream media | Access the media browser to search and select videos |

## Configuration Options

### API Settings

- **Access Key**: Your Viostream Access Key (starts with `VC-`)
- **API Key**: Your Viostream API Key from Developer Tools

### Player Settings

- **Width**: Set the player width (e.g., 100%, 640px)
- **Height**: Set the player height in pixels (e.g., 400)
- **Responsive**: Enable for responsive 16:9 aspect ratio
- **Autoplay**: Automatically start video playback
- **Muted**: Mute video by default (recommended with autoplay)
- **Show Controls**: Display player controls

## Standalone Media Browser

A standalone media browser is available at **Content > Browse Viostream Media** (or `/admin/content/viostream/browse`). This allows administrators to browse the full Viostream media library outside of a content editing context.

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

### API Connection Not Working

- Verify your Access Key starts with `VC-`
- Check that your API Key is correct (from Viostream Developer Tools)
- Ensure your server can reach `https://api.app.viostream.com`
- Use the Test Connection button on the settings page for diagnostic messages

### Browse Viostream Button Not Showing

- Ensure the widget is set to **Viostream Browser** in Manage form display
- Clear Drupal cache (Admin > Configuration > Performance > Clear all caches)
- Check the user has the **Browse Viostream media** permission

### Media Browser Shows No Results

- Verify API credentials are configured at Configuration > Media > Viostream Settings
- Test the connection using the Test Connection button
- Ensure your Viostream account has media assets

### Video Not Displaying

If your video isn't showing:
- Verify the video URL is correct
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

- [Viostream API Documentation](https://help.viostream.com/api/viostream-api)
- [Drupal Field API](https://www.drupal.org/docs/drupal-apis/entity-api/fields)
- [Module Issue Queue](https://github.com/Viostream/govcms-viostream-plugin/issues)

## Support

For help and support:
- Check the [Quick Start Guide](../docs/QUICK_START.md)
- Visit the [GitHub repository](https://github.com/Viostream/govcms-viostream-plugin)
- Submit issues on [GitHub](https://github.com/Viostream/govcms-viostream-plugin/issues)
