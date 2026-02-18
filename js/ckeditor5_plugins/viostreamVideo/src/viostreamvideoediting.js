/**
 * @file
 * Editing plugin for Viostream Video.
 *
 * Defines the model schema for viostreamVideo elements and handles
 * conversion between model <-> view (editing) <-> data (HTML output).
 */
import { Plugin } from 'ckeditor5/src/core';
import { Widget, toWidget } from 'ckeditor5/src/widget';
import InsertViostreamVideoCommand from './insertviostreamvideocommand';

export default class ViostreamVideoEditing extends Plugin {
  static get requires() {
    return [Widget];
  }

  static get pluginName() {
    return 'ViostreamVideoEditing';
  }

  init() {
    this._defineSchema();
    this._defineConverters();

    this.editor.commands.add(
      'insertViostreamVideo',
      new InsertViostreamVideoCommand(this.editor),
    );
  }

  /**
   * Defines the model schema for the viostreamVideo element.
   */
  _defineSchema() {
    const schema = this.editor.model.schema;

    schema.register('viostreamVideo', {
      inheritAllFrom: '$blockObject',
      allowAttributes: ['videoKey', 'videoTitle', 'videoWidth', 'videoHeight'],
    });
  }

  /**
   * Defines converters between model, editing view, and data view.
   */
  _defineConverters() {
    const conversion = this.editor.conversion;

    // --- Upcast: data HTML -> model ---
    // Converts <viostream-video data-video-key="..." data-video-title="...">
    // from the HTML source into the model element.
    conversion.for('upcast').elementToElement({
      view: {
        name: 'viostream-video',
        attributes: {
          'data-video-key': true,
        },
      },
      model: (viewElement, { writer }) => {
        const videoKey = viewElement.getAttribute('data-video-key') || '';
        const videoTitle = viewElement.getAttribute('data-video-title') || '';
        const videoWidth = viewElement.getAttribute('data-video-width') || '';
        const videoHeight = viewElement.getAttribute('data-video-height') || '';

        return writer.createElement('viostreamVideo', {
          videoKey,
          videoTitle,
          videoWidth,
          videoHeight,
        });
      },
    });

    // --- Data downcast: model -> data HTML ---
    // Converts the model element back to <viostream-video> in the saved HTML.
    conversion.for('dataDowncast').elementToElement({
      model: 'viostreamVideo',
      view: (modelElement, { writer }) => {
        const videoKey = modelElement.getAttribute('videoKey') || '';
        const videoTitle = modelElement.getAttribute('videoTitle') || '';
        const videoWidth = modelElement.getAttribute('videoWidth') || '';
        const videoHeight = modelElement.getAttribute('videoHeight') || '';

        const attrs = {
          'data-video-key': videoKey,
          'data-video-title': videoTitle,
        };

        if (videoWidth && videoHeight) {
          attrs['data-video-width'] = videoWidth;
          attrs['data-video-height'] = videoHeight;
        }

        return writer.createContainerElement('viostream-video', attrs);
      },
    });

    // --- Editing downcast: model -> editing view ---
    // Renders a visual preview widget inside the editor.
    conversion.for('editingDowncast').elementToElement({
      model: 'viostreamVideo',
      view: (modelElement, { writer }) => {
        const videoKey = modelElement.getAttribute('videoKey') || '';
        const videoTitle = modelElement.getAttribute('videoTitle') || '';
        const videoWidth = modelElement.getAttribute('videoWidth') || '';
        const videoHeight = modelElement.getAttribute('videoHeight') || '';
        const thumbnailUrl = `https://share.viostream.com/${encodeURIComponent(videoKey)}/thumbnail`;

        // Compute aspect ratio for preview.
        let aspectLabel = '';
        if (videoWidth && videoHeight) {
          const w = parseInt(videoWidth, 10);
          const h = parseInt(videoHeight, 10);
          if (w > 0 && h > 0) {
            aspectLabel = w + ' \u00d7 ' + h;
          }
        }

        // Outer container element.
        const container = writer.createContainerElement('div', {
          class: 'viostream-ckeditor-widget',
          'data-video-key': videoKey,
        });

        // Build the inner preview structure.
        const preview = writer.createRawElement('div', {
          class: 'viostream-ckeditor-preview',
        }, (domElement) => {
          domElement.innerHTML =
            '<div class="viostream-ckeditor-preview-inner">' +
              '<div class="viostream-ckeditor-thumb">' +
                '<img src="' + _escapeAttr(thumbnailUrl) + '" alt="" onerror="this.style.display=\'none\'" />' +
                '<div class="viostream-ckeditor-play-icon">' +
                  '<svg width="48" height="48" viewBox="0 0 48 48" fill="none">' +
                    '<circle cx="24" cy="24" r="24" fill="rgba(0,0,0,0.6)"/>' +
                    '<polygon points="18,12 38,24 18,36" fill="white"/>' +
                  '</svg>' +
                '</div>' +
              '</div>' +
              '<div class="viostream-ckeditor-info">' +
                '<span class="viostream-ckeditor-label">Viostream Video' +
                  (aspectLabel ? ' <span class="viostream-ckeditor-dimensions">(' + _escapeHtml(aspectLabel) + ')</span>' : '') +
                '</span>' +
                '<span class="viostream-ckeditor-title">' + _escapeHtml(videoTitle || videoKey) + '</span>' +
              '</div>' +
            '</div>';
        });

        writer.insert(writer.createPositionAt(container, 0), preview);

        return toWidget(container, writer, {
          label: 'Viostream video widget',
        });
      },
    });
  }
}

/**
 * Escapes HTML entities in a string.
 */
function _escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/**
 * Escapes a string for use in an HTML attribute value.
 */
function _escapeAttr(str) {
  return _escapeHtml(str).replace(/'/g, '&#39;');
}
