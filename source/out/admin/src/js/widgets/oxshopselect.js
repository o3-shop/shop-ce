/**
 * This file is part of O3-Shop Community Edition.
 *
 * O3-Shop Community Edition is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * O3-Shop Community Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with O3-Shop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2015
 * @version   O3-Shop CE
 */

var oxShopSelect = (function ($) {

    return {
        options: {
            'width': '165px',
            'disable_search_threshold': 9,
            'no_results_text': '-'
        },
        init: function () {
            var oSelectShop = $('#selectshop');
            oSelectShop.chosen(oxShopSelect.options);
        }
    };

})(jQuery);

jQuery.noConflict();

jQuery(document).ready(function () {
    oxShopSelect.init();
});
