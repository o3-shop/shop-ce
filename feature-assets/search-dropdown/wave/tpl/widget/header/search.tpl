[{block name="widget_header_search_form"}]
    [{if $oView->showSearch()}]
        <form class="form search" id="searchForm" role="form" action="[{$oViewConf->getSelfActionLink()}]" method="get" name="search">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="cl" value="search">

            [{block name="dd_widget_header_search_form_inner"}]
                <div class="input-group search-suggest-wrapper">
                    [{block name="header_search_field"}]
                        <input class="form-control" type="text" id="searchParam" name="searchparam" value="[{$oView->getSearchParamForHtml()}]" placeholder="[{oxmultilang ident="SEARCH"}]" autocomplete="off">
                    [{/block}]

                    [{block name="dd_header_search_button"}]
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button" id="searchSubmit" title="[{oxmultilang ident="SEARCH_SUBMIT"}]"><i class="fas fa-search"></i></button>
                    </div>
                    [{/block}]

                    <div id="searchSuggestDropdown" class="search-suggest-dropdown"></div>
                </div>
            [{/block}]
        </form>

        [{oxscript include="js/widgets/oxsearchsuggest.js"}]
        [{oxstyle include="css/search-suggest.css"}]
    [{/if}]
[{/block}]
