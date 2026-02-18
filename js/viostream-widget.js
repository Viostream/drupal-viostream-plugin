/**
 * @file
 * JavaScript for the Viostream browser field widget.
 *
 * Opens a modal dialog with the media browser, allowing content editors
 * to search and select a Viostream video.
 */
(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.viostreamWidget = {
    attach: function (context) {
      // Get URLs from drupalSettings.
      var settings = drupalSettings.viostream || {};
      var searchUrl = settings.searchUrl || '';
      var detailUrlBase = settings.detailUrlBase || '';

      var browseButtons = context.querySelectorAll('.viostream-browse-btn');
      browseButtons.forEach(function (btn) {
        if (btn.dataset.viostreamBound) return;
        btn.dataset.viostreamBound = '1';

        btn.addEventListener('click', function (e) {
          e.preventDefault();
          openModal(btn, searchUrl, detailUrlBase);
        });
      });

      var clearButtons = context.querySelectorAll('.viostream-clear-btn');
      clearButtons.forEach(function (btn) {
        if (btn.dataset.viostreamBound) return;
        btn.dataset.viostreamBound = '1';

        btn.addEventListener('click', function (e) {
          e.preventDefault();
          var widget = btn.closest('.viostream-browser-widget');
          var valueInput = widget.querySelector('.viostream-selected-value');
          if (valueInput) valueInput.value = '';
          var preview = widget.querySelector('.viostream-preview');
          if (preview) {
            preview.innerHTML = '<div class="viostream-preview-empty">' + Drupal.t('No video selected') + '</div>';
          }
          btn.style.display = 'none';
        });
      });
    }
  };

  function openModal(triggerBtn, searchUrl, detailUrl) {
    var widget = triggerBtn.closest('.viostream-browser-widget');

    // Create modal overlay.
    var overlay = document.createElement('div');
    overlay.className = 'viostream-modal-overlay';

    var modal = document.createElement('div');
    modal.className = 'viostream-modal';

    // Header.
    var header = document.createElement('div');
    header.className = 'viostream-modal-header';
    header.innerHTML = '<h3>' + Drupal.t('Select a Viostream Video') + '</h3>'
      + '<button type="button" class="viostream-modal-close">&times;</button>';
    modal.appendChild(header);

    // Body.
    var body = document.createElement('div');
    body.className = 'viostream-modal-body';
    body.innerHTML = '<div class="viostream-media-browser">'
      + '<div class="viostream-browser-toolbar">'
      + '<div class="viostream-search-wrapper">'
      + '<input type="text" class="form-text viostream-search-input" placeholder="' + Drupal.t('Search videos...') + '" />'
      + '<button type="button" class="button viostream-search-btn">' + Drupal.t('Search') + '</button>'
      + '</div>'
      + '<div class="viostream-sort-wrapper">'
      + '<label>' + Drupal.t('Sort by') + ':</label>'
      + '<select class="form-select viostream-sort-select">'
      + '<option value="CreatedDate-desc">' + Drupal.t('Newest first') + '</option>'
      + '<option value="CreatedDate-asc">' + Drupal.t('Oldest first') + '</option>'
      + '<option value="Title-asc">' + Drupal.t('Title A-Z') + '</option>'
      + '<option value="Title-desc">' + Drupal.t('Title Z-A') + '</option>'
      + '</select></div></div>'
      + '<div class="viostream-browser-status">'
      + '<span class="viostream-result-count"></span>'
      + '<span class="viostream-loading">' + Drupal.t('Loading...') + '</span>'
      + '</div>'
      + '<div class="viostream-media-grid"></div>'
      + '</div>';
    modal.appendChild(body);

    // Footer.
    var footer = document.createElement('div');
    footer.className = 'viostream-modal-footer';
    footer.innerHTML = '<button type="button" class="button viostream-modal-cancel">' + Drupal.t('Cancel') + '</button>'
      + '<button type="button" class="button button--primary viostream-modal-select" disabled>' + Drupal.t('Select Video') + '</button>';
    modal.appendChild(footer);

    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    // Initialize the browser within the modal.
    var browserEl = body.querySelector('.viostream-media-browser');
    var currentPage = 1;
    var selectedItem = null;

    // Close handlers.
    header.querySelector('.viostream-modal-close').addEventListener('click', closeModal);
    footer.querySelector('.viostream-modal-cancel').addEventListener('click', closeModal);
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal();
    });
    // Close on Escape key.
    document.addEventListener('keydown', onEscape);

    function onEscape(e) {
      if (e.key === 'Escape') closeModal();
    }

    // Select handler.
    footer.querySelector('.viostream-modal-select').addEventListener('click', function () {
      if (selectedItem) {
        applySelection(widget, selectedItem);
        closeModal();
      }
    });

    function closeModal() {
      document.removeEventListener('keydown', onEscape);
      overlay.remove();
    }

    // Search logic.
    var searchInput = browserEl.querySelector('.viostream-search-input');
    var searchBtn = browserEl.querySelector('.viostream-search-btn');
    var sortSelect = browserEl.querySelector('.viostream-sort-select');
    var grid = browserEl.querySelector('.viostream-media-grid');
    var resultCount = browserEl.querySelector('.viostream-result-count');
    var loadingEl = browserEl.querySelector('.viostream-loading');
    var selectBtn = footer.querySelector('.viostream-modal-select');

    function doSearch(page) {
      currentPage = page || 1;
      var sortVal = sortSelect.value.split('-');
      var params = new URLSearchParams({
        search: searchInput.value,
        page: currentPage,
        page_size: 24,
        sort: sortVal[0],
        order: sortVal[1]
      });

      loadingEl.style.display = '';
      selectedItem = null;
      selectBtn.disabled = true;

      fetch(searchUrl + '?' + params.toString(), {
        headers: { 'Accept': 'application/json' }
      })
        .then(function (res) {
          if (!res.ok) {
            // Try to parse JSON body; fall back to plain object on failure.
            return res.json()
              .catch(function () { return {}; })
              .then(function (data) {
                throw { status: res.status, data: data };
              });
          }
          return res.json();
        })
        .then(function (data) {
          loadingEl.style.display = 'none';
          renderResults(data);
        })
        .catch(function (err) {
          loadingEl.style.display = 'none';
          resultCount.textContent = '';

          if (err.status === 403) {
            showNotConfigured();
          }
          else {
            grid.innerHTML = '<div class="viostream-empty">' + Drupal.t('Error loading videos.') + '</div>';
          }
          console.error('Viostream error:', err);
        });
    }

    function showNotConfigured() {
      // Hide the toolbar and status bar — they are not useful when the
      // API has not been set up yet.
      var toolbar = browserEl.querySelector('.viostream-browser-toolbar');
      var statusBar = browserEl.querySelector('.viostream-browser-status');
      if (toolbar) toolbar.style.display = 'none';
      if (statusBar) statusBar.style.display = 'none';

      grid.innerHTML = '<div class="viostream-not-configured">'
        + '<div class="viostream-not-configured-icon" aria-hidden="true">&#9888;</div>'
        + '<h3>' + Drupal.t('Viostream API not configured') + '</h3>'
        + '<p>' + Drupal.t('The Viostream API credentials have not been set up.') + '</p>'
        + '<p>' + Drupal.t('An administrator needs to configure the API keys at') + ' '
        + '<strong>' + Drupal.t('Configuration &rarr; Media &rarr; Viostream Settings') + '</strong>.</p>'
        + '</div>';
    }

    function renderResults(data) {
      var items = data.items || [];
      var totalItems = data.totalItems || 0;
      var totalPages = data.totalPages || 0;

      resultCount.textContent = Drupal.t('Showing @count videos', { '@count': totalItems });

      var html = '';
      if (items.length === 0) {
        html = '<div class="viostream-empty">' + Drupal.t('No videos found.') + '</div>';
      }
      else {
        items.forEach(function (item) {
          html += '<div class="viostream-media-card"'
            + ' data-media-id="' + escapeAttr(item.id || '') + '"'
            + ' data-media-key="' + escapeAttr(item.key || '') + '"'
            + ' data-media-title="' + escapeAttr(item.title || '') + '"'
            + ' data-media-thumbnail="' + escapeAttr(item.thumbnail || '') + '">';
          html += '<div class="viostream-card-thumb">';
          if (item.thumbnail) {
            html += '<img src="' + escapeAttr(item.thumbnail) + '" alt="' + escapeAttr(item.title || '') + '" loading="lazy" />';
          }
          else {
            html += '<div class="viostream-card-no-thumb"><span>' + Drupal.t('No thumbnail') + '</span></div>';
          }
          if (item.duration) {
            html += '<span class="viostream-card-duration">' + escapeHtml(item.duration) + '</span>';
          }
          html += '</div>';
          html += '<div class="viostream-card-info">';
          html += '<h4 class="viostream-card-title">' + escapeHtml(item.title || '') + '</h4>';
          if (item.description) {
            var desc = item.description.length > 80 ? item.description.substring(0, 80) + '...' : item.description;
            html += '<p class="viostream-card-desc">' + escapeHtml(desc) + '</p>';
          }
          html += '</div></div>';
        });
      }

      grid.innerHTML = html;

      // Pagination.
      var existingPag = browserEl.querySelector('.viostream-pagination');
      if (existingPag) existingPag.remove();

      if (totalPages > 1) {
        var pag = document.createElement('div');
        pag.className = 'viostream-pagination';
        pag.innerHTML = '<button type="button" class="button viostream-page-prev"' + (currentPage <= 1 ? ' disabled' : '') + '>'
          + '&laquo; ' + Drupal.t('Previous')
          + '</button>'
          + '<span class="viostream-page-info">' + Drupal.t('Page @current of @total', { '@current': currentPage, '@total': totalPages }) + '</span>'
          + '<button type="button" class="button viostream-page-next"' + (currentPage >= totalPages ? ' disabled' : '') + '>'
          + Drupal.t('Next') + ' &raquo;'
          + '</button>';
        browserEl.appendChild(pag);

        pag.querySelector('.viostream-page-prev').addEventListener('click', function () {
          if (currentPage > 1) doSearch(currentPage - 1);
        });
        pag.querySelector('.viostream-page-next').addEventListener('click', function () {
          doSearch(currentPage + 1);
        });
      }

      // Card click to select.
      var cards = grid.querySelectorAll('.viostream-media-card');
      cards.forEach(function (card) {
        card.addEventListener('click', function () {
          cards.forEach(function (c) { c.classList.remove('is-selected'); });
          card.classList.add('is-selected');
          selectedItem = {
            id: card.dataset.mediaId,
            key: card.dataset.mediaKey,
            title: card.dataset.mediaTitle,
            thumbnail: card.dataset.mediaThumbnail
          };
          selectBtn.disabled = false;
        });
      });
    }

    searchBtn.addEventListener('click', function () { doSearch(1); });
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        doSearch(1);
      }
    });
    sortSelect.addEventListener('change', function () { doSearch(1); });

    // Focus search input.
    searchInput.focus();

    // Initial load.
    doSearch(1);
  }

  function applySelection(widget, item) {
    var valueInput = widget.querySelector('.viostream-selected-value');
    if (valueInput) {
      // Store the share URL as the field value.
      valueInput.value = 'https://share.viostream.com/' + encodeURIComponent(item.key);
      // Trigger change so Drupal knows the value changed.
      valueInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    var preview = widget.querySelector('.viostream-preview');
    if (preview) {
      var html = '<div class="viostream-preview-content">';
      if (item.thumbnail) {
        html += '<img src="' + escapeAttr(item.thumbnail) + '" alt="" class="viostream-preview-thumb" />';
      }
      html += '<span class="viostream-preview-title">' + escapeHtml(item.title || item.key) + '</span>';
      html += '</div>';
      preview.innerHTML = html;
    }

    // Show clear button if hidden.
    var clearBtn = widget.querySelector('.viostream-clear-btn');
    if (clearBtn) {
      clearBtn.style.display = '';
    }
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function escapeAttr(str) {
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

})(Drupal, drupalSettings);
