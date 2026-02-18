/**
 * @file
 * Main Viostream Video CKEditor 5 plugin.
 *
 * Combines the editing (schema/conversion) and UI (toolbar/modal) sub-plugins.
 */
import { Plugin } from 'ckeditor5/src/core';
import ViostreamVideoEditing from './viostreamvideoediting';
import ViostreamVideoUI from './viostreamvideoui';

export default class ViostreamVideo extends Plugin {
  static get requires() {
    return [ViostreamVideoEditing, ViostreamVideoUI];
  }

  static get pluginName() {
    return 'ViostreamVideo';
  }
}
