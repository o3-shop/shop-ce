$(document).ready(function() {
    var searchInput = $('#searchParam');
    var dropdown = $('#searchSuggestDropdown');
    var searchForm = $('#searchForm');
    var searchSubmit = $('#searchSubmit');
    var debounceTimer = null;
    var minChars = 2;
    var isActive = false;

    if (!searchInput.length) {
        return;
    }

    searchInput.on('input', function() {
        var query = $(this).val().trim();
        clearTimeout(debounceTimer);

        if (query.length < minChars) {
            closeDropdown();
            return;
        }

        debounceTimer = setTimeout(function() {
            fetchSuggestions(query);
        }, 300);
    });

    searchInput.on('focus', function() {
        if (dropdown.find('.search-suggest-item').length > 0) {
            openDropdown();
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-suggest-wrapper').length) {
            closeDropdown();
        }
    });

    searchInput.on('keydown', function(e) {
        if (!isActive) return;

        var items = dropdown.find('.search-suggest-item');
        var activeItem = items.filter('.active');
        var index = items.index(activeItem);

        if (e.keyCode === 40) {
            e.preventDefault();
            items.removeClass('active');
            if (index < items.length - 1) {
                items.eq(index + 1).addClass('active');
            } else {
                items.eq(0).addClass('active');
            }
        } else if (e.keyCode === 38) {
            e.preventDefault();
            items.removeClass('active');
            if (index > 0) {
                items.eq(index - 1).addClass('active');
            } else {
                items.eq(items.length - 1).addClass('active');
            }
        } else if (e.keyCode === 13) {
            e.preventDefault();
            var activeLink = dropdown.find('.search-suggest-item.active a');
            if (activeLink.length) {
                window.location.href = activeLink.attr('href');
            } else {
                closeDropdown();
                searchForm.submit();
            }
        } else if (e.keyCode === 27) {
            closeDropdown();
        }
    });

    searchSubmit.on('click', function() {
        closeDropdown();
        searchForm.submit();
    });

    function fetchSuggestions(query) {
        var actionUrl = searchForm.attr('action');
        $.ajax({
            url: actionUrl,
            type: 'GET',
            data: {
                cl: 'searchsuggest',
                searchparam: query,
                fnc: ''
            },
            dataType: 'json',
            success: function(data) {
                renderSuggestions(data);
            },
            error: function() {
                closeDropdown();
            }
        });
    }

    function renderSuggestions(items) {
        dropdown.empty();

        if (!items || items.length === 0) {
            closeDropdown();
            return;
        }

        var list = $('<div class="search-suggest-list"></div>');

        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var itemEl = $('<div class="search-suggest-item"></div>');
            var link = $('<a></a>').attr('href', item.link);

            if (item.icon) {
                var iconSpan = $('<span class="search-suggest-icon"></span>');
                var img = $('<img>').attr('src', item.icon).attr('alt', item.title);
                iconSpan.append(img);
                link.append(iconSpan);
            }

            var infoSpan = $('<span class="search-suggest-info"></span>');
            infoSpan.append($('<span class="search-suggest-title"></span>').text(item.title));
            infoSpan.append($('<span class="search-suggest-price"></span>').text(item.price));
            link.append(infoSpan);

            itemEl.append(link);
            list.append(itemEl);
        }

        dropdown.append(list);
        openDropdown();

        dropdown.find('.search-suggest-item').on('mouseenter', function() {
            dropdown.find('.search-suggest-item').removeClass('active');
            $(this).addClass('active');
        });

        dropdown.find('.search-suggest-item').on('mouseleave', function() {
            $(this).removeClass('active');
        });
    }

    function openDropdown() {
        dropdown.addClass('show');
        isActive = true;
    }

    function closeDropdown() {
        dropdown.removeClass('show').empty();
        isActive = false;
    }
});
