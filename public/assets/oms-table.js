/* ==========================================================================
   OMS – listing table helper
   --------------------------------------------------------------------------
   Search, page-size, dropdown filters and pagination for any container
   marked with [data-oms-table]:

     [data-oms-search]        text input, matched against the whole row
     [data-oms-entries]       page-size <select>
     [data-oms-filter="key"]  <select> matched against the row's data-key
     [data-oms-info]          "Showing x to y of z entries"
     [data-oms-pager]         pagination buttons
     tbody tr[data-row]       the rows that take part
     [data-oms-empty]         row shown when nothing matches
   ========================================================================== */

(function () {
    'use strict';

    function matches(row, term, filters) {
        if (term && row.textContent.toLowerCase().indexOf(term) === -1) {
            return false;
        }

        return filters.every(function (select) {
            var value = select.value;
            return !value || (row.getAttribute('data-' + select.getAttribute('data-oms-filter')) || '') === value;
        });
    }

    function pagerButton(label, page, ctx, opts) {
        var button = document.createElement('button');

        button.type        = 'button';
        button.textContent = label;
        button.disabled    = !!(opts && opts.disabled);

        if (opts && opts.active) {
            button.classList.add('active');
        }

        button.addEventListener('click', function () {
            ctx.page = page;
            render(ctx);
        });

        return button;
    }

    function renderPager(ctx, pages) {
        if (!ctx.pager) return;

        ctx.pager.innerHTML = '';

        if (pages < 2) return;

        ctx.pager.appendChild(pagerButton('Previous', ctx.page - 1, ctx, { disabled: ctx.page === 1 }));

        for (var page = 1; page <= pages; page++) {
            ctx.pager.appendChild(pagerButton(String(page), page, ctx, { active: page === ctx.page }));
        }

        ctx.pager.appendChild(pagerButton('Next', ctx.page + 1, ctx, { disabled: ctx.page === pages }));
    }

    function render(ctx) {
        var term    = ctx.search ? ctx.search.value.trim().toLowerCase() : '';
        var visible = ctx.rows.filter(function (row) { return matches(row, term, ctx.filters); });

        var size  = ctx.entries ? parseInt(ctx.entries.value, 10) : visible.length;
        var pages = Math.max(1, Math.ceil(visible.length / (size || 1)));

        if (ctx.page > pages) ctx.page = pages;
        if (ctx.page < 1)     ctx.page = 1;

        var start = (ctx.page - 1) * size;

        ctx.rows.forEach(function (row) { row.hidden = true; });
        visible.slice(start, start + size).forEach(function (row) { row.hidden = false; });

        if (ctx.empty) {
            ctx.empty.hidden = visible.length > 0;
        }

        if (ctx.info) {
            ctx.info.textContent = visible.length
                ? 'Showing ' + (start + 1) + ' to ' + Math.min(start + size, visible.length) + ' of ' + visible.length + ' entries'
                : 'Showing 0 to 0 of 0 entries';
        }

        renderPager(ctx, pages);
    }

    function init(root) {
        var ctx = {
            page:    1,
            rows:    Array.prototype.slice.call(root.querySelectorAll('tbody tr[data-row]')),
            search:  root.querySelector('[data-oms-search]'),
            entries: root.querySelector('[data-oms-entries]'),
            filters: Array.prototype.slice.call(root.querySelectorAll('[data-oms-filter]')),
            info:    root.querySelector('[data-oms-info]'),
            pager:   root.querySelector('[data-oms-pager]'),
            empty:   root.querySelector('[data-oms-empty]')
        };

        // Any control change sends the user back to the first page.
        [ctx.search, ctx.entries].concat(ctx.filters).forEach(function (control) {
            if (!control) return;

            control.addEventListener('input', function () {
                ctx.page = 1;
                render(ctx);
            });
        });

        render(ctx);
    }

    /*
     * Cells are held to one line and ellipsised, so anything too long for its
     * column would otherwise be unreadable. Hand the full text back as a
     * tooltip — only where it is actually cut off, so the rest stay clean.
     */
    function titleTruncatedCells(root) {
        Array.prototype.forEach.call(root.querySelectorAll('table.oms-table tbody td'), function (cell) {
            if (cell.title) return;

            var text = cell.textContent.trim();
            if (text && cell.scrollWidth > cell.clientWidth + 1) {
                cell.title = text;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(document.querySelectorAll('[data-oms-table]'), init);

        titleTruncatedCells(document);

        // "Check All / None" helpers inside listing tables.
        Array.prototype.forEach.call(document.querySelectorAll('[data-oms-check-all]'), function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                var scope = document.querySelector(link.getAttribute('data-oms-check-all'));
                var state = link.getAttribute('data-state') === 'all';

                if (!scope) return;

                Array.prototype.forEach.call(scope.querySelectorAll('input[type="checkbox"]'), function (box) {
                    box.checked = state;
                });
            });
        });
    });
})();
