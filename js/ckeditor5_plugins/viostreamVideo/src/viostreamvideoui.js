/**
 * @file
 * UI plugin for Viostream Video.
 *
 * Adds a toolbar button that opens a modal to browse and select
 * Viostream videos from the connected account.
 */
import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import viostreamIcon from './icon.svg';

export default class ViostreamVideoUI extends Plugin {
  static get pluginName() {
    return 'ViostreamVideoUI';
  }

  init() {
    const editor = this.editor;

    editor.ui.componentFactory.add('viostreamVideo', (locale) => {
      const button = new ButtonView(locale);

      button.set({
        label: editor.t('Viostream Video'),
        icon: viostreamIcon,
        tooltip: true,
      });

      button.on('execute', () => {
        this._openBrowser();
      });

      // Bind enabled state to command (guard for admin toolbar config page
      // where the editing plugin may not be fully initialised).
      const command = editor.commands.get('insertViostreamVideo');
      if (command) {
        button.bind('isEnabled').to(command);
      }

      return button;
    });
  }

  /**
   * Opens the Viostream media browser modal.
   */
  _openBrowser() {
    const editor = this.editor;
    const config = editor.config.get('viostreamVideo') || {};
    const searchUrl = config.searchUrl || '';
    const detailUrlBase = config.detailUrlBase || '';

    if (!searchUrl) {
      // eslint-disable-next-line no-alert
      alert('Viostream API is not configured. Please configure it in admin settings.');
      return;
    }

    // Create modal overlay.
    const overlay = document.createElement('div');
    overlay.className = 'viostream-modal-overlay';

    const modal = document.createElement('div');
    modal.className = 'viostream-modal';

    // Header.
    const header = document.createElement('div');
    header.className = 'viostream-modal-header';
    header.innerHTML =
      '<h3>Select a Viostream Video</h3>' +
      '<button type="button" class="viostream-modal-close">&times;</button>';
    modal.appendChild(header);

    // Body.
    const body = document.createElement('div');
    body.className = 'viostream-modal-body';
    body.innerHTML =
      '<div class="viostream-media-browser">' +
        '<div class="viostream-browser-toolbar">' +
          '<div class="viostream-search-wrapper">' +
            '<input type="text" class="form-text viostream-search-input" placeholder="Search videos..." />' +
            '<button type="button" class="button viostream-search-btn">Search</button>' +
          '</div>' +
          '<div class="viostream-sort-wrapper">' +
            '<label>Sort by:</label>' +
            '<select class="form-select viostream-sort-select">' +
              '<option value="CreatedDate-desc">Newest first</option>' +
              '<option value="CreatedDate-asc">Oldest first</option>' +
              '<option value="Title-asc">Title A-Z</option>' +
              '<option value="Title-desc">Title Z-A</option>' +
            '</select>' +
          '</div>' +
        '</div>' +
        '<div class="viostream-browser-status">' +
          '<span class="viostream-result-count"></span>' +
          '<span class="viostream-loading">Loading...</span>' +
        '</div>' +
        '<div class="viostream-media-grid"></div>' +
      '</div>';
    modal.appendChild(body);

    // Footer.
    const footer = document.createElement('div');
    footer.className = 'viostream-modal-footer';
    footer.innerHTML =
      '<button type="button" class="button viostream-modal-cancel">Cancel</button>' +
      '<button type="button" class="button button--primary viostream-modal-select" disabled>Select Video</button>';
    modal.appendChild(footer);

    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    // State.
    let currentPage = 1;
    let selectedItem = null;

    const browserEl = body.querySelector('.viostream-media-browser');
    const searchInput = browserEl.querySelector('.viostream-search-input');
    const searchBtn = browserEl.querySelector('.viostream-search-btn');
    const sortSelect = browserEl.querySelector('.viostream-sort-select');
    const grid = browserEl.querySelector('.viostream-media-grid');
    const resultCount = browserEl.querySelector('.viostream-result-count');
    const loadingEl = browserEl.querySelector('.viostream-loading');
    const selectBtn = footer.querySelector('.viostream-modal-select');

    // Close handlers.
    const closeModal = () => {
      document.removeEventListener('keydown', onEscape);
      overlay.remove();
    };

    const onEscape = (e) => {
      if (e.key === 'Escape') {
        closeModal();
      }
    };

    header.querySelector('.viostream-modal-close').addEventListener('click', closeModal);
    footer.querySelector('.viostream-modal-cancel').addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        closeModal();
      }
    });
    document.addEventListener('keydown', onEscape);

    // Select handler - fetch video dimensions, then insert into the editor.
    selectBtn.addEventListener('click', () => {
      if (!selectedItem) {
        return;
      }

      selectBtn.disabled = true;
      selectBtn.textContent = 'Loading...';

      // Build the detail URL for the selected video.
      const detailUrl = detailUrlBase
        ? detailUrlBase.replace('__MEDIA_ID__', encodeURIComponent(selectedItem.id))
        : '';

      const insertVideo = (width, height) => {
        editor.execute('insertViostreamVideo', {
          videoKey: selectedItem.key,
          videoTitle: selectedItem.title,
          videoWidth: width ? String(width) : '',
          videoHeight: height ? String(height) : '',
        });
        closeModal();
        editor.editing.view.focus();
      };

      if (!detailUrl) {
        // No detail endpoint configured; insert without dimensions.
        insertVideo(null, null);
        return;
      }

      fetch(detailUrl, { headers: { Accept: 'application/json' } })
        .then((res) => res.json())
        .then((data) => {
          insertVideo(data.videoWidth || null, data.videoHeight || null);
        })
        .catch((err) => {
          console.warn('Viostream: could not fetch video dimensions:', err);
          // Insert anyway without dimensions.
          insertVideo(null, null);
        });
    });

    // Search logic.
    const doSearch = (page) => {
      currentPage = page || 1;
      const sortVal = sortSelect.value.split('-');
      const params = new URLSearchParams({
        search: searchInput.value,
        page: currentPage,
        page_size: 24,
        sort: sortVal[0],
        order: sortVal[1],
      });

      loadingEl.style.display = '';
      selectedItem = null;
      selectBtn.disabled = true;

      fetch(searchUrl + '?' + params.toString(), {
        headers: { Accept: 'application/json' },
      })
        .then((res) => res.json())
        .then((data) => {
          loadingEl.style.display = 'none';
          renderResults(data);
        })
        .catch((err) => {
          loadingEl.style.display = 'none';
          grid.innerHTML =
            '<div class="viostream-empty">Error loading videos.</div>';
          console.error('Viostream error:', err);
        });
    };

    const renderResults = (data) => {
      const items = data.items || [];
      const totalItems = data.totalItems || 0;
      const totalPages = data.totalPages || 0;

      resultCount.textContent = `Showing ${totalItems} videos`;

      let html = '';
      if (items.length === 0) {
        html = '<div class="viostream-empty">No videos found.</div>';
      } else {
        items.forEach((item) => {
          html +=
            '<div class="viostream-media-card"' +
              ' data-media-id="' + _escapeAttr(item.id || '') + '"' +
              ' data-media-key="' + _escapeAttr(item.key || '') + '"' +
              ' data-media-title="' + _escapeAttr(item.title || '') + '"' +
              ' data-media-thumbnail="' + _escapeAttr(item.thumbnail || '') + '">' +
              '<div class="viostream-card-thumb">';
          if (item.thumbnail) {
            html +=
              '<img src="' + _escapeAttr(item.thumbnail) + '" alt="' + _escapeAttr(item.title || '') + '" loading="lazy" />';
          } else {
            html +=
              '<div class="viostream-card-no-thumb"><span>No thumbnail</span></div>';
          }
          if (item.duration) {
            html +=
              '<span class="viostream-card-duration">' + _escapeHtml(item.duration) + '</span>';
          }
          html += '</div>';
          html += '<div class="viostream-card-info">';
          html +=
            '<h4 class="viostream-card-title">' + _escapeHtml(item.title || '') + '</h4>';
          if (item.description) {
            const desc =
              item.description.length > 80
                ? item.description.substring(0, 80) + '...'
                : item.description;
            html +=
              '<p class="viostream-card-desc">' + _escapeHtml(desc) + '</p>';
          }
          html += '</div></div>';
        });
      }

      grid.innerHTML = html;

      // Pagination.
      const existingPag = browserEl.querySelector('.viostream-pagination');
      if (existingPag) {
        existingPag.remove();
      }

      if (totalPages > 1) {
        const pag = document.createElement('div');
        pag.className = 'viostream-pagination';
        pag.innerHTML =
          '<button type="button" class="button viostream-page-prev"' +
            (currentPage <= 1 ? ' disabled' : '') +
            '>&laquo; Previous</button>' +
          '<span class="viostream-page-info">Page ' + currentPage + ' of ' + totalPages + '</span>' +
          '<button type="button" class="button viostream-page-next"' +
            (currentPage >= totalPages ? ' disabled' : '') +
            '>Next &raquo;</button>';
        browserEl.appendChild(pag);

        pag.querySelector('.viostream-page-prev').addEventListener('click', () => {
          if (currentPage > 1) {
            doSearch(currentPage - 1);
          }
        });
        pag.querySelector('.viostream-page-next').addEventListener('click', () => {
          doSearch(currentPage + 1);
        });
      }

      // Card click to select.
      const cards = grid.querySelectorAll('.viostream-media-card');
      cards.forEach((card) => {
        card.addEventListener('click', () => {
          cards.forEach((c) => c.classList.remove('is-selected'));
          card.classList.add('is-selected');
          selectedItem = {
            id: card.dataset.mediaId,
            key: card.dataset.mediaKey,
            title: card.dataset.mediaTitle,
            thumbnail: card.dataset.mediaThumbnail,
          };
          selectBtn.disabled = false;
        });
      });
    };

    searchBtn.addEventListener('click', () => doSearch(1));
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        doSearch(1);
      }
    });
    sortSelect.addEventListener('change', () => doSearch(1));

    // Focus search input.
    searchInput.focus();

    // Initial load.
    doSearch(1);
  }
}

/**
 * Escapes HTML entities.
 */
function _escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/**
 * Escapes a string for HTML attribute use.
 */
function _escapeAttr(str) {
  return _escapeHtml(str).replace(/'/g, '&#39;');
}
