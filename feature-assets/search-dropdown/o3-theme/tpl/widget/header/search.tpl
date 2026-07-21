[{block name="widget_header_search_form"}]
    [{if $oView->showSearch()}]
        <form class="form search" id="searchForm" role="form" action="[{$oViewConf->getSelfActionLink()}]" method="get" name="search">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="cl" value="search">

            [{block name="dd_widget_header_search_form_inner"}]
                <div class="input-group search-suggest-wrapper">
                    [{block name="dd_widget_header_categorylist_navbar_header"}]
                        <button class="btn btn-primary d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvas__mainnav" aria-controls="offcanvas__mainnav">
                            <svg width="26" height="14" viewBox="0 0 26 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="26" height="2" fill="white"/>
                                <rect y="6" width="26" height="2" fill="white"/>
                                <rect y="12" width="26" height="2" fill="white"/>
                            </svg>
                        </button>
                    [{/block}]
                    [{block name="header_search_field"}]
                        <input class="form-control" type="text" id="searchParam" name="searchparam" value="[{$oView->getSearchParamForHtml()}]" placeholder="[{oxmultilang ident="SEARCH"}]" autocomplete="off" data-suggest-url="[{$oViewConf->getSelfLink()}]">
                    [{/block}]

                    [{block name="dd_header_search_button"}]
                        <button type="button" class="btn header__search-button" id="searchSubmit" title="[{oxmultilang ident="SEARCH_SUBMIT"}]">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12.6289 12.4365C15.1673 9.89812 15.2031 5.81829 12.7087 3.32397C10.2144 0.829655 6.1346 0.865398 3.59619 3.40381C1.05779 5.94221 1.02204 10.022 3.51636 12.5164C6.01068 15.0107 10.0905 14.9749 12.6289 12.4365ZM12.6289 12.4365L16.3241 16.1317" stroke="#112211" stroke-width="1.5" stroke-linecap="square"/>
                            </svg>
                        </button>
                    [{/block}]

                    <div id="searchSuggestDropdown" class="search-suggest-dropdown"></div>
                </div>
            [{/block}]
        </form>

        [{oxscript include="js/widget/oxsearchsuggest.js"}]
        [{oxstyle include="css/search-suggest.css"}]
    [{/if}]
[{/block}]
