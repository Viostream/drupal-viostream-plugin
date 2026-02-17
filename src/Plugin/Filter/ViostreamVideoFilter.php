<?php

namespace Drupal\viostream\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to convert <viostream-video> tags to embedded iframes.
 *
 * @Filter(
 *   id = "viostream_video",
 *   title = @Translation("Viostream Video Embed"),
 *   description = @Translation("Converts &lt;viostream-video&gt; tags inserted by the CKEditor plugin into embedded video iframes."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   weight = 10
 * )
 */
class ViostreamVideoFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (strpos($text, '<viostream-video') === FALSE) {
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $elements = $xpath->query('//viostream-video');

    if ($elements->length === 0) {
      return $result;
    }

    foreach ($elements as $element) {
      $video_key = $element->getAttribute('data-video-key');
      if (empty($video_key)) {
        continue;
      }

      $title = $element->getAttribute('data-video-title') ?: 'Viostream Video';
      $video_width = $element->getAttribute('data-video-width');
      $video_height = $element->getAttribute('data-video-height');

      // Compute the padding-bottom percentage for the aspect ratio.
      // Falls back to 56.25% (16:9) if dimensions are not available.
      $padding_bottom = '56.25%';
      if (!empty($video_width) && !empty($video_height)) {
        $w = (int) $video_width;
        $h = (int) $video_height;
        if ($w > 0 && $h > 0) {
          $padding_bottom = round(($h / $w) * 100, 4) . '%';
        }
      }

      // Build the embed URL.
      $embed_url = 'https://share.viostream.com/' . Html::escape($video_key);

      // Create a wrapper div with responsive aspect ratio.
      $wrapper = $dom->createElement('div');
      $wrapper->setAttribute('class', 'viostream-embed-wrapper');
      $wrapper->setAttribute('style', 'position:relative;padding-bottom:' . $padding_bottom . ';height:0;overflow:hidden;max-width:100%;');

      // Create the iframe.
      $iframe = $dom->createElement('iframe');
      $iframe->setAttribute('src', $embed_url);
      $iframe->setAttribute('title', Html::escape($title));
      $iframe->setAttribute('width', '100%');
      $iframe->setAttribute('height', '100%');
      $iframe->setAttribute('style', 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;');
      $iframe->setAttribute('frameborder', '0');
      $iframe->setAttribute('allowfullscreen', 'true');
      $iframe->setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');

      $wrapper->appendChild($iframe);

      // Replace the <viostream-video> element with the wrapper.
      $element->parentNode->replaceChild($wrapper, $element);
    }

    $result->setProcessedText(Html::serialize($dom));

    // Attach the video embed CSS library.
    $result->addAttachments([
      'library' => ['viostream/viostream'],
    ]);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('You can embed Viostream videos using the Viostream button in the editor toolbar. Videos are inserted as &lt;viostream-video&gt; elements and automatically converted to embedded video players on display.');
    }
    return $this->t('Viostream videos can be embedded using the editor toolbar button.');
  }

}
