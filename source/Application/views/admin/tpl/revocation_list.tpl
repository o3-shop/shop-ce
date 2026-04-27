[{include file="headitem.tpl" title="O3_REVOCATION_ADMIN_LIST_HEADING"|oxmultilangassign}]

[{* §356a BGB electronic revocation submissions — admin list view *}]

<form name="search" id="search" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="revocation_list">
    <input type="hidden" name="lstrt" value="[{$lstrt}]">

    [{if $oxidlistview}]
        <table class="adminListTable" cellpadding="0" cellspacing="0">
            <colgroup>
                <col width="20%">
                <col width="20%">
                <col width="25%">
                <col width="20%">
                <col width="15%">
            </colgroup>
            <thead>
                <tr class="listitem4">
                    <th>[{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_COL_SUBMITTED"}]</th>
                    <th>[{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_COL_NAME"}]</th>
                    <th>[{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_COL_EMAIL"}]</th>
                    <th>[{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_COL_ORDER"}]</th>
                    <th>[{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_COL_STATUS"}]</th>
                </tr>
            </thead>
            <tbody>
                [{foreach from=$oxidlistview->getItemList() item=row}]
                    <tr class="listitem[{if $row->getId() == $oxid}] listitem3[{/if}]"
                        onclick="document.getElementById('listEdit').oxid.value='[{$row->getId()}]';document.getElementById('listEdit').submit();">
                        <td>[{$row->oxrevocation__oxsubmitted->value|default:$row->o3revocation__oxsubmitted->value}]</td>
                        <td>[{$row->o3revocation__oxname->getRawValue()|escape:'html'}]</td>
                        <td>[{$row->o3revocation__oxemail->getRawValue()|escape:'html'}]</td>
                        <td>[{$row->o3revocation__oxorderident->getRawValue()|escape:'html'}]</td>
                        <td>
                            [{if $row->o3revocation__oxsendfailed->value}]
                                <span class="errorbox">[{oxmultilang ident="O3_REVOCATION_ADMIN_FLAG_SEND_FAILED"}]</span>
                            [{else}]
                                <span>[{oxmultilang ident="O3_REVOCATION_ADMIN_FLAG_SENT"}]</span>
                            [{/if}]
                        </td>
                    </tr>
                [{foreachelse}]
                    <tr><td colspan="5">[{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_EMPTY"}]</td></tr>
                [{/foreach}]
            </tbody>
        </table>
    [{else}]
        <p>[{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_EMPTY"}]</p>
    [{/if}]
</form>

<form id="listEdit" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="revocation_main">
    <input type="hidden" name="oxid" value="">
</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
