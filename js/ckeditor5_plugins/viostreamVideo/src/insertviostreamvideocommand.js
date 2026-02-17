/**
 * @file
 * Command to insert a Viostream video element into the editor.
 */
import { Command } from 'ckeditor5/src/core';

export default class InsertViostreamVideoCommand extends Command {
  execute({ videoKey, videoTitle, videoWidth, videoHeight }) {
    const editor = this.editor;

    editor.model.change((writer) => {
      const viostreamVideo = writer.createElement('viostreamVideo', {
        videoKey,
        videoTitle: videoTitle || '',
        videoWidth: videoWidth || '',
        videoHeight: videoHeight || '',
      });

      editor.model.insertObject(viostreamVideo, null, null, {
        setSelection: 'on',
      });
    });
  }

  refresh() {
    const model = this.editor.model;
    const selection = model.document.selection;
    const allowedIn = model.schema.findAllowedParent(
      selection.getFirstPosition(),
      'viostreamVideo',
    );

    this.isEnabled = allowedIn !== null;
  }
}
