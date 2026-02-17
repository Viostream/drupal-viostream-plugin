/**
 * @file
 * JavaScript for the Viostream media browser page.
 */
(function (Drupal) {
  'use strict';

  Drupal.behaviors.viostreamMediaBrowser = {
    attach: function (context) {
      var browser = context.querySelector('.viostream-media-browser');
      if (!browser || browser.dataset.viostreamInit) {
        return;
      }
      browser.dataset.viostreamInit = '1';

      var searchUrl = browser.dataset.searchUrl;
      var searchInput = browser.querySelector('.viostream-search-input');
      var searchBtn = browser.querySelector('.viostream-search-btn');
      var sortSelect = browser.querySelector('.viostream-sort-select');
      var grid = browser.querySelector('.viostream-media-grid');
      var resultCount = browser.querySelector('.viostream-result-count');
      var loading = browser.querySelector('.viostream-loading');
      var currentPage = 1;

      function showLoading() {
        if (loading) loading.style.display = '';
      }

      function hideLoading() {
        if (loading) loading.style.display = 'none';
      }

      function doSearch(page) {
        currentPage = page || 1;
        var sortVal = sortSelect ? sortSelect.value.split('-') : ['CreatedDate', 'desc'];
        var params = new URLSearchParams({
          search: searchInput ? searchInput.value : '',
          page: currentPage,
          page_size: 24,
          sort: sortVal[0],
          order: sortVal[1]
        });

        showLoading();

        fetch(searchUrl + '?' + params.toString(), {
          headers: { 'Accept': 'application/json' }
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            hideLoading();
            renderGrid(data);
          })
          .catch(function (err) {
            hideLoading();
            console.error('Viostream search error:', err);
          });
      }

      function renderGrid(data) {
        var items = data.items || [];
        var totalItems = data.totalItems || 0;
        var totalPages = data.totalPages || 0;

        if (resultCount) {
          resultCount.textContent = Drupal.t('Showing @count videos', { '@count': totalItems });
        }

        var html = '';
        if (items.length === 0) {
          html = '<div class="viostream-empty">' + Drupal.t('No videos found.') + '</div>';
        }
        else {
          items.forEach(function (item) {
            html += '<div class="viostream-media-card"'
              + ' data-media-id="' + escapeAttr(item.id || '') + '"'
              + ' data-media-key="' + escapeAttr(item.key || '') + '"'
              + ' data-media-title="' + escapeAttr(item.title || '') + '">';
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
            html += '<div class="viostream-card-meta">';
            if (item.status) {
              html += '<span class="viostream-card-status viostream-status-' + escapeAttr(item.status.toLowerCase()) + '">' + escapeHtml(item.status) + '</span>';
            }
            if (typeof item.totalViews !== 'undefined') {
              html += '<span class="viostream-card-views">' + escapeHtml(String(item.totalViews)) + ' ' + Drupal.t('views') + '</span>';
            }
            html += '</div></div></div>';
          });
        }

        grid.innerHTML = html;

        // Render pagination.
        var paginationEl = browser.querySelector('.viostream-pagination');
        if (totalPages > 1) {
          if (!paginationEl) {
            paginationEl = document.createElement('div');
            paginationEl.className = 'viostream-pagination';
            browser.appendChild(paginationEl);
          }
          paginationEl.innerHTML = '<button type="button" class="button viostream-page-prev"' + (currentPage <= 1 ? ' disabled' : '') + '>'
            + Drupal.t('&laquo; Previous')
            + '</button>'
            + '<span class="viostream-page-info">' + Drupal.t('Page @current of @total', { '@current': currentPage, '@total': totalPages }) + '</span>'
            + '<button type="button" class="button viostream-page-next"' + (currentPage >= totalPages ? ' disabled' : '') + '>'
            + Drupal.t('Next &raquo;')
            + '</button>';
          paginationEl.style.display = '';
        }
        else if (paginationEl) {
          paginationEl.style.display = 'none';
        }

        // Re-bind card clicks and pagination.
        bindCardClicks();
        bindPagination();
      }

      function bindCardClicks() {
        var cards = grid.querySelectorAll('.viostream-media-card');
        cards.forEach(function (card) {
          card.addEventListener('click', function () {
            cards.forEach(function (c) { c.classList.remove('is-selected'); });
            card.classList.add('is-selected');
          });
        });
      }

      function bindPagination() {
        var prevBtn = browser.querySelector('.viostream-page-prev');
        var nextBtn = browser.querySelector('.viostream-page-next');
        if (prevBtn) {
          prevBtn.addEventListener('click', function () {
            if (currentPage > 1) doSearch(currentPage - 1);
          });
        }
        if (nextBtn) {
          nextBtn.addEventListener('click', function () {
            doSearch(currentPage + 1);
          });
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

      // Bind events.
      if (searchBtn) {
        searchBtn.addEventListener('click', function () { doSearch(1); });
      }
      if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            doSearch(1);
          }
        });
      }
      if (sortSelect) {
        sortSelect.addEventListener('change', function () { doSearch(1); });
      }

      // Initial card click binding.
      bindCardClicks();
      bindPagination();
    }
  };

})(Drupal);
