document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('searchParam');
    var dropdown = document.getElementById('searchSuggestDropdown');
    var searchForm = document.getElementById('searchForm');
    var searchSubmit = document.getElementById('searchSubmit');
    var debounceTimer = null;
    var minChars = 2;
    var isActive = false;

    if (!searchInput) {
        return;
    }

    searchInput.addEventListener('input', function() {
        var query = this.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < minChars) {
            closeDropdown();
            return;
        }

        debounceTimer = setTimeout(function() {
            fetchSuggestions(query);
        }, 300);
    });

    searchInput.addEventListener('focus', function() {
        if (dropdown.querySelectorAll('.search-suggest-item').length > 0) {
            openDropdown();
        }
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-suggest-wrapper')) {
            closeDropdown();
        }
    });

    searchInput.addEventListener('keydown', function(e) {
        if (!isActive) return;

        var items = dropdown.querySelectorAll('.search-suggest-item');
        var activeItem = dropdown.querySelector('.search-suggest-item.active');
        var index = activeItem ? Array.prototype.indexOf.call(items, activeItem) : -1;

        if (e.keyCode === 40) {
            e.preventDefault();
            items.forEach(function(item) { item.classList.remove('active'); });
            if (index < items.length - 1) {
                items[index + 1].classList.add('active');
            } else {
                items[0].classList.add('active');
            }
        } else if (e.keyCode === 38) {
            e.preventDefault();
            items.forEach(function(item) { item.classList.remove('active'); });
            if (index > 0) {
                items[index - 1].classList.add('active');
            } else {
                items[items.length - 1].classList.add('active');
            }
        } else if (e.keyCode === 13) {
            e.preventDefault();
            var activeLink = dropdown.querySelector('.search-suggest-item.active a');
            if (activeLink) {
                window.location.href = activeLink.getAttribute('href');
            } else {
                closeDropdown();
                searchForm.submit();
            }
        } else if (e.keyCode === 27) {
            closeDropdown();
        }
    });

    searchSubmit.addEventListener('click', function() {
        closeDropdown();
        searchForm.submit();
    });

    function fetchSuggestions(query) {
        var baseUrl = searchInput.dataset.suggestUrl;
        var url = baseUrl + 'cl=searchsuggest&searchparam=' + encodeURIComponent(query);

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var items = JSON.parse(xhr.responseText);
                        renderSuggestions(items);
                    } catch (e) {
                        closeDropdown();
                    }
                } else {
                    closeDropdown();
                }
            }
        };
        xhr.send();
    }

    function renderSuggestions(items) {
        dropdown.innerHTML = '';

        if (!items || items.length === 0) {
            closeDropdown();
            return;
        }

        var list = document.createElement('div');
        list.className = 'search-suggest-list';

        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var itemEl = document.createElement('div');
            itemEl.className = 'search-suggest-item';
            var link = document.createElement('a');
            link.href = item.link;

            if (item.icon) {
                var iconSpan = document.createElement('span');
                iconSpan.className = 'search-suggest-icon';
                var img = document.createElement('img');
                img.src = item.icon;
                img.alt = item.title;
                iconSpan.appendChild(img);
                link.appendChild(iconSpan);
            }

            var infoSpan = document.createElement('span');
            infoSpan.className = 'search-suggest-info';
            var titleSpan = document.createElement('span');
            titleSpan.className = 'search-suggest-title';
            titleSpan.textContent = item.title;
            var priceSpan = document.createElement('span');
            priceSpan.className = 'search-suggest-price';
            priceSpan.textContent = item.price;
            infoSpan.appendChild(titleSpan);
            infoSpan.appendChild(priceSpan);
            link.appendChild(infoSpan);

            itemEl.appendChild(link);
            list.appendChild(itemEl);
        }

        dropdown.appendChild(list);
        openDropdown();

        var suggestItems = dropdown.querySelectorAll('.search-suggest-item');
        suggestItems.forEach(function(el) {
            el.addEventListener('mouseenter', function() {
                suggestItems.forEach(function(item) { item.classList.remove('active'); });
                el.classList.add('active');
            });
            el.addEventListener('mouseleave', function() {
                el.classList.remove('active');
            });
        });
    }

    function openDropdown() {
        dropdown.classList.add('show');
        isActive = true;
    }

    function closeDropdown() {
        dropdown.classList.remove('show');
        dropdown.innerHTML = '';
        isActive = false;
    }
});
