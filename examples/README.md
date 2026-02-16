# Viostream Module Examples

This directory contains examples of how to use the Viostream module in various scenarios.

## Example 1: Basic Video Embedding

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

When creating an article, enter one of these in the video field:
```
https://play.viostream.com/abc123xyz
```

### Result

The video will be displayed as a responsive iframe with 16:9 aspect ratio.

---

## Example 2: Auto-playing Background Video

**Content Type:** Landing Page
**Field Type:** Link
**Field Name:** field_background_video

### Configuration

1. Add a Link field to the Landing Page content type
2. Configure the display:
   - Formatter: Viostream Video
   - Width: 100%
   - Height: 600
   - Responsive: Yes
   - Autoplay: Yes
   - Muted: Yes (required for autoplay)
   - Show controls: No

### Usage

```
https://viostream.com/video/def456uvw
```

### Result

The video auto-plays on page load, muted, without controls - perfect for hero/background sections.

---

## Example 3: Multiple Videos (Gallery)

**Content Type:** Video Gallery
**Field Type:** Link
**Field Name:** field_video_gallery
**Allowed values:** Unlimited

### Configuration

1. Add a Link field with unlimited values
2. Configure the display:
   - Formatter: Viostream Video
   - Width: 100%
   - Height: 300
   - Responsive: Yes
   - Autoplay: No
   - Muted: No
   - Show controls: Yes

### Usage

Add multiple video IDs or URLs:
```
ghi789rst
https://play.viostream.com/jkl012mno
https://app.viostream.com/video/pqr345stu
```

### Result

Multiple videos displayed in a gallery format, each with its own player.

---

## Example 4: Using with Paragraphs

**Paragraph Type:** Video Section
**Field Type:** Link
**Field Name:** field_paragraph_video

### Configuration

1. Create a paragraph type "Video Section"
2. Add fields:
   - field_title (Text)
   - field_description (Text Long)
   - field_paragraph_video (Link)
3. Configure display for field_paragraph_video:
   - Formatter: Viostream Video
   - Responsive: Yes

### Usage in Content

Add the paragraph to a page and fill in:
- Title: "Product Demo"
- Description: "Watch our product in action"
- Video: `https://play.viostream.com/vwx678yzabc`

### Result

Structured content with video, title, and description in a reusable component.

---

## Example 5: Fixed-Size Video

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

Enter just the video ID:
```
bcd890efg
```

### Result

Fixed-size video player at 640x360px.

---

## Example 6: Custom Theming

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

## Example 7: Programmatic Video Embedding

**In a custom module or theme:**

```php
<?php
use Drupal\Core\Url;

// In a controller or template preprocess function
$video_id = 'hij123klm';
$embed_url = Url::fromUri("https://play.viostream.com/{$video_id}", [
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

## Example 8: Media Entity Integration

**Create a custom Media Source for Viostream:**

While the field formatter works great for simple use cases, for advanced media management you can create a custom Media Source plugin that integrates with Drupal's Media module.

This allows you to:
- Store videos in the Media library
- Add metadata (tags, descriptions)
- Reuse videos across content
- Control access permissions

*Note: This requires additional custom development beyond this module.*

---

## Tips

1. **Always test video IDs** before publishing content
2. **Use responsive mode** for mobile-friendly videos
3. **Enable muted with autoplay** (browser requirement)
4. **Consider performance** with multiple videos on one page
5. **Use appropriate dimensions** for your theme layout
6. **Test across browsers** for autoplay compatibility

## Need Help?

See the main [README.md](../README.md) for more information.
