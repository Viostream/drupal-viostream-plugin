# Viostream Module Examples

This directory contains examples of how to use the Viostream module in various scenarios.

## Example 1: Media Browser Widget (Recommended)

**Content Type:** Article
**Field Type:** Link
**Field Name:** field_viostream_video

### Setup

1. Add a Link field to the Article content type:
   - Field Label: "Viostream Video"
   - Machine name: field_viostream_video
   - Allowed number of values: 1

2. Configure the form widget (Manage form display):
   - Widget: **Viostream Browser**

3. Configure the display (Manage display):
   - Formatter: Viostream Video
   - Width: 100%
   - Height: 400
   - Responsive: Yes
   - Autoplay: No
   - Muted: No
   - Show controls: Yes

### Prerequisites

API credentials must be configured at **Admin > Configuration > Media > Viostream Settings** before the media browser will work.

### Usage

When creating an article:
1. Click the **Browse Viostream** button
2. A modal opens showing your Viostream media library
3. Search for a video by title
4. Click a video thumbnail to select it
5. The field is populated with the video's share URL

### Result

The video will be displayed as a responsive iframe with 16:9 aspect ratio, and editors get a visual media browser instead of manually entering URLs.

---

## Example 2: Basic Video Embedding (Manual URL)

**Content Type:** Article
**Field Type:** Link
**Field Name:** field_viostream_video

### Configuration

1. Add a Link field to the Article content type:
   - Field Label: "Viostream Video"
   - Machine name: field_viostream_video
   - Allowed number of values: 1

2. Configure the display:
   - Formatter: Viostream Video
   - Width: 100%
   - Height: 400
   - Responsive: Yes
   - Autoplay: No
   - Muted: No
   - Show controls: Yes

### Usage

When creating an article, enter a share URL in the video field:
```
https://share.viostream.com/abc123xyz
```

### Result

The video will be displayed as a responsive iframe with 16:9 aspect ratio.

---

## Example 3: Auto-playing Background Video

**Content Type:** Landing Page
**Field Type:** Link
**Field Name:** field_background_video

### Configuration

1. Add a Link field to the Landing Page content type
2. Configure the form widget:
   - Widget: **Viostream Browser** (or default Link widget for manual entry)
3. Configure the display:
   - Formatter: Viostream Video
   - Width: 100%
   - Height: 600
   - Responsive: Yes
   - Autoplay: Yes
   - Muted: Yes (required for autoplay)
   - Show controls: No

### Usage

Select a video via the media browser, or enter manually:
```
https://share.viostream.com/def456uvw
```

### Result

The video auto-plays on page load, muted, without controls - perfect for hero/background sections.

---

## Example 4: Multiple Videos (Gallery)

**Content Type:** Video Gallery
**Field Type:** Link
**Field Name:** field_video_gallery
**Allowed values:** Unlimited

### Configuration

1. Add a Link field with unlimited values
2. Configure the form widget:
   - Widget: **Viostream Browser**
3. Configure the display:
   - Formatter: Viostream Video
   - Width: 100%
   - Height: 300
   - Responsive: Yes
   - Autoplay: No
   - Muted: No
   - Show controls: Yes

### Usage

Click **Browse Viostream** for each field slot to select multiple videos. Each field instance gets its own Browse button.

### Result

Multiple videos displayed in a gallery format, each with its own player.

---

## Example 5: Using with Paragraphs

**Paragraph Type:** Video Section
**Field Type:** Link
**Field Name:** field_paragraph_video

### Configuration

1. Create a paragraph type "Video Section"
2. Add fields:
   - field_title (Text)
   - field_description (Text Long)
   - field_paragraph_video (Link)
3. Configure form display for field_paragraph_video:
   - Widget: **Viostream Browser**
4. Configure display for field_paragraph_video:
   - Formatter: Viostream Video
   - Responsive: Yes

### Usage in Content

Add the paragraph to a page and fill in:
- Title: "Product Demo"
- Description: "Watch our product in action"
- Video: Click **Browse Viostream** and select a video

### Result

Structured content with video, title, and description in a reusable component.

---

## Example 6: Fixed-Size Video

**Content Type:** Blog Post
**Field Type:** String
**Field Name:** field_video_id

### Configuration

1. Add a Plain Text field
2. Configure the display:
   - Formatter: Viostream Video
   - Width: 640px
   - Height: 360
   - Responsive: No
   - Autoplay: No
   - Muted: No
   - Show controls: Yes

### Usage

Enter a share URL:
```
https://share.viostream.com/bcd890efg
```

### Result

Fixed-size video player at 640x360px.

---

## Example 7: Custom Theming

**Override template in your theme:**

1. Copy the template:
```bash
cp modules/contrib/viostream/templates/viostream-video.html.twig themes/YOUR_THEME/templates/
```

2. Customize it:
```twig
{# Custom wrapper with additional markup #}
<div class="my-custom-video-wrapper">
  <div class="video-title">{{ video_title }}</div>
  {% if responsive %}
  <div class="viostream-video-wrapper viostream-responsive my-responsive-class">
    <iframe
      src="{{ embed_url }}"
      class="viostream-video-iframe"
      frameborder="0"
      allowfullscreen
      allow="autoplay; fullscreen; picture-in-picture"
      title="{{ 'Viostream video player'|t }}"
    ></iframe>
  </div>
  {% endif %}
</div>
```

3. Clear cache:
```bash
drush cr
```

---

## Example 8: Programmatic Video Embedding

**In a custom module or theme:**

```php
<?php
use Drupal\Core\Url;

// In a controller or template preprocess function
$video_id = 'hij123klm';
$embed_url = Url::fromUri("https://share.viostream.com/{$video_id}", [
  'query' => [
    'autoplay' => '1',
    'muted' => '1',
  ],
])->toString();

$build = [
  '#theme' => 'viostream_video',
  '#video_id' => $video_id,
  '#embed_url' => $embed_url,
  '#width' => '100%',
  '#height' => '400',
  '#responsive' => TRUE,
  '#attached' => [
    'library' => ['viostream/viostream'],
  ],
];

return $build;
```

---

## Example 9: Programmatic API Client Usage

**Using the Viostream API client in custom code:**

```php
<?php

// Get the API client service.
$client = \Drupal::service('viostream.client');

// List media with search and pagination.
$results = $client->listMedia([
  'search' => 'product demo',
  'limit' => 10,
  'offset' => 0,
]);

// Get details for a specific media item.
$media = $client->getMedia('media-key-here');

// List channels.
$channels = $client->listChannels();

// Test the connection (returns account info).
$account = $client->getAccount();
```

---

## Example 10: Media Entity Integration

**Create a custom Media Source for Viostream:**

While the field formatter and media browser widget work great for most use cases, for advanced media management you can create a custom Media Source plugin that integrates with Drupal's Media module.

This allows you to:
- Store videos in the Media library
- Add metadata (tags, descriptions)
- Reuse videos across content
- Control access permissions

*Note: This requires additional custom development beyond this module.*

---

## Tips

1. **Configure API credentials first** before using the media browser widget
2. **Use the Viostream Browser widget** for the best editorial experience
3. **Always test video IDs** before publishing content
4. **Use responsive mode** for mobile-friendly videos
5. **Enable muted with autoplay** (browser requirement)
6. **Consider performance** with multiple videos on one page
7. **Use appropriate dimensions** for your theme layout
8. **Test across browsers** for autoplay compatibility

## Need Help?

See the main [README.md](../README.md) for more information.
