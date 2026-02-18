# Quick Start Guide: Viostream for Drupal/GovCMS

This guide will help you get started with browsing and embedding Viostream videos in your Drupal or GovCMS website.

## Installation

### Step 1: Install the Module

Using Drush:
```bash
drush en viostream
```

Or via the Admin UI:
1. Go to **Admin > Extend**
2. Find and enable "Viostream"
3. Click **Install**

## API Setup

### Step 2: Configure Viostream API Credentials

You need to connect your Drupal site to the Viostream API before you can browse and select videos.

1. Go to **Admin > Configuration > Media > Viostream Settings**
2. Enter your **Access Key** (starts with `VC-`)
   - Find this in your Viostream account under Developer Tools
3. Enter your **API Key**
   - Also found in Viostream Developer Tools
4. Click **Test Connection** to verify credentials
   - A green message confirms the connection is working
   - A red message indicates invalid credentials or a network issue
5. Click **Save configuration**

## Basic Setup

### Step 3: Add a Video Field

1. Navigate to **Admin > Structure > Content types > Article > Manage fields**
2. Click **Add field**
3. Select **Link** as the field type
4. Enter "Video" as the field label
5. Click **Save and continue**
6. Configure field settings (leave as default) and **Save**

### Step 4: Configure the Widget (Form Display)

1. Go to **Admin > Structure > Content types > Article > Manage form display**
2. Find your "Video" field
3. Change the **Widget** dropdown to **Viostream Browser**
4. Click **Save**

This enables the Browse Viostream button on the content editing form.

### Step 5: Configure Display Settings

1. Go to **Admin > Structure > Content types > Article > Manage display**
2. Find your "Video" field
3. Change the **Format** dropdown to **Viostream Video**
4. Click the **settings** icon
5. Configure your preferences:
   - **Responsive**: Yes (recommended)
   - **Width**: 100%
   - **Height**: 400
   - **Show controls**: Yes
6. Click **Update** and then **Save**

### Step 6: Add a Video to Content

1. Create or edit an Article
2. Click the **Browse Viostream** button on the Video field
3. A modal window opens showing your Viostream media library
4. **Search** for a video by title using the search box
5. **Sort** results by title, date, or duration
6. **Click** on a video thumbnail to select it
7. The field is automatically populated with the video's share URL
8. **Save** the article
9. View the article - your video should now be embedded!

## Alternative: Manual URL Entry

If you prefer not to use the media browser widget:

1. Keep the default **Link** widget in Manage form display (skip Step 4)
2. When creating content, manually enter a Viostream share URL:
   - `https://share.viostream.com/VIDEO_ID`
3. Save and view - the Viostream Video formatter renders the embedded player

## Standalone Media Browser

You can also browse your full Viostream library from a dedicated page:

1. Go to **Admin > Content > Browse Viostream Media**
   (or visit `/admin/content/viostream/browse`)
2. Search and browse your entire Viostream media library
3. This is useful for finding video IDs or reviewing your video catalogue

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

### API connection not working?
- Verify your Access Key starts with `VC-`
- Check your API Key is correct
- Ensure your server can reach `https://api.app.viostream.com`
- Use the **Test Connection** button for diagnostic messages

### Browse Viostream button not showing?
- Check the widget is set to **Viostream Browser** in Manage form display
- Clear Drupal cache: `drush cr`
- Ensure the user has the **Browse Viostream media** permission

### Video not showing?
- Check the video URL is correct
- Verify the field formatter is set to "Viostream Video"
- Clear Drupal cache: `drush cr`

### Controls not working?
- Ensure "Show controls" is enabled in display settings
- Check for JavaScript errors in browser console

### Video too small/large?
- Adjust width and height in display settings
- Try enabling "Responsive" mode

## Advanced Usage

### Using with Paragraphs

Add Viostream videos to Paragraph types:
1. Create a new Paragraph type: **Admin > Structure > Paragraph types**
2. Add a Link field to your paragraph type
3. Set the widget to **Viostream Browser** in form display
4. Configure it to use the Viostream Video formatter in display settings
5. Add the paragraph field to your content type

### Using with Media Entities

For advanced media management:
1. Create a custom Media type for Viostream videos
2. Add a Link field to store the video URL
3. Set the widget to **Viostream Browser**
4. Configure the display to use Viostream Video formatter
5. Use Media library for video selection

### Custom Theming

Override the template:
1. Copy `viostream-video.html.twig` to your theme
2. Place it in `themes/YOUR_THEME/templates/`
3. Customize the markup as needed
4. Clear cache: `drush cr`

## Need Help?

- [Full Documentation](../README.md)
- [Report Issues](https://github.com/Viostream/govcms-viostream-plugin/issues)
- [Viostream API](https://help.viostream.com/api/viostream-api)
