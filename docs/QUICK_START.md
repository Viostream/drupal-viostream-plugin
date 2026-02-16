# Quick Start Guide: Viostream for Drupal/GovCMS

This guide will help you get started with embedding Viostream videos in your Drupal or GovCMS website.

## Installation

### Step 1: Install the Module

Using Drush:
```bash
drush en viostream
```

Or via the Admin UI:
1. Go to **Admin → Extend**
2. Find and enable "Viostream"
3. Click **Install**

## Basic Setup

### Step 2: Add a Video Field

1. Navigate to **Admin → Structure → Content types → Article → Manage fields**
2. Click **Add field**
3. Select **Link** as the field type
4. Enter "Video" as the field label
5. Click **Save and continue**
6. Configure field settings (leave as default) and **Save**

### Step 3: Configure Display Settings

1. Go to **Admin → Structure → Content types → Article → Manage display**
2. Find your "Video" field
3. Change the **Format** dropdown to **Viostream Video**
4. Click the **⚙️ settings** icon
5. Configure your preferences:
   - ✅ **Responsive**: Yes (recommended)
   - **Width**: 100%
   - **Height**: 400
   - **Show controls**: Yes
6. Click **Update** and then **Save**

### Step 4: Add a Video to Content

1. Create or edit an Article
2. In the **Video** field, enter a Viostream video URL or ID:
   - Example URL: `https://play.viostream.com/abc123xyz`
   - Example ID: `abc123xyz`
3. **Save** the article
4. View the article - your video should now be embedded!

## Common URL Formats

The module accepts these Viostream URL formats:

| Format | Example |
|--------|---------|
| Player URL | `https://play.viostream.com/VIDEO_ID` |
| Video URL | `https://viostream.com/video/VIDEO_ID` |
| App URL | `https://app.viostream.com/video/VIDEO_ID` |
| Direct ID | `VIDEO_ID` |

## Tips & Tricks

### Making Videos Responsive

For responsive videos that scale with screen size:
1. Set **Width** to `100%`
2. Enable **Responsive** checkbox
3. The height will automatically maintain a 16:9 aspect ratio

### Auto-playing Videos

To auto-play videos (useful for background videos):
1. Enable **Autoplay**
2. Enable **Muted** (required for autoplay in most browsers)
3. Optionally disable **Show controls** for a cleaner look

### Multiple Videos on One Page

You can add multiple video fields to a content type or use a multi-value field:
1. When creating the field, set **Allowed number of values** to "Unlimited"
2. Each video will embed separately

## Troubleshooting

### Video not showing?
- ✓ Check the video ID is correct
- ✓ Verify the field formatter is set to "Viostream Video"
- ✓ Clear Drupal cache: `drush cr`

### Controls not working?
- ✓ Ensure "Show controls" is enabled in display settings
- ✓ Check for JavaScript errors in browser console

### Video too small/large?
- ✓ Adjust width and height in display settings
- ✓ Try enabling "Responsive" mode

## Advanced Usage

### Using with Paragraphs

Add Viostream videos to Paragraph types:
1. Create a new Paragraph type: **Admin → Structure → Paragraph types**
2. Add a Link field to your paragraph type
3. Configure it to use the Viostream Video formatter
4. Add the paragraph field to your content type

### Using with Media Entities

For advanced media management:
1. Create a custom Media type for Viostream videos
2. Add a Link field to store the video URL
3. Configure the display to use Viostream Video formatter
4. Use Media library for video selection

### Custom Theming

Override the template:
1. Copy `viostream-video.html.twig` to your theme
2. Place it in `themes/YOUR_THEME/templates/`
3. Customize the markup as needed
4. Clear cache: `drush cr`

## Need Help?

- 📖 [Full Documentation](../README.md)
- 🐛 [Report Issues](https://github.com/Viostream/govcms-viostream-plugin/issues)
- 🎥 [Viostream API](https://api.app.viostream.com/swagger/index.html)
