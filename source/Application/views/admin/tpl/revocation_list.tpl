[{include file="headitem.tpl" title="O3_REVOCATION_ADMIN_LIST_HEADING"|oxmultilangassign box="list"}]
[{assign var="where" value=$oView->getListFilter()}]

[{if $readonly}]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]

<script type="text/javascript">
<!--
window.onload = function ()
{
    top.reloadEditFrame();
    [{if $updatelist == 1}]
        top.oxid.admin.updateList('[{$oxid}]');
    [{/if}]
}
//-->
</script>

<div id="liste">

<form name="search" id="search" action="[{$oViewConf->getSelfLink()}]" method="post">
[{include file="_formparams.tpl" cl="revocation_list" lstrt=$lstrt actedit=$actedit oxid=$oxid fnc="" language=$actlang editlanguage=$actlang}]
<table cellspacing="0" cellpadding="0" border="0" width="100%">
    <colgroup>
        [{block name="admin_revocation_list_colgroup"}]
            <col width="20%">
            <col width="20%">
            <col width="25%">
            <col width="20%">
            <col width="13%">
            <col width="2%">
        [{/block}]
    </colgroup>
    <tr>
        [{block name="admin_revocation_list_sorting"}]
            <td class="listheader first" height="15">
                <a href="Javascript:top.oxid.admin.setSorting( document.search, 'o3revocation', 'oxsubmitted', 'desc');document.search.submit();" class="listheader">
                    [{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_COL_SUBMITTED"}]
                </a>
            </td>
            <td class="listheader" height="15">
                <a href="Javascript:top.oxid.admin.setSorting( document.search, 'o3revocation', 'oxname', 'asc');document.search.submit();" class="listheader">
                    [{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_COL_NAME"}]
                </a>
            </td>
            <td class="listheader" height="15">
                <a href="Javascript:top.oxid.admin.setSorting( document.search, 'o3revocation', 'oxemail', 'asc');document.search.submit();" class="listheader">
                    [{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_COL_EMAIL"}]
                </a>
            </td>
            <td class="listheader" height="15">
                <a href="Javascript:top.oxid.admin.setSorting( document.search, 'o3revocation', 'oxorderident', 'asc');document.search.submit();" class="listheader">
                    [{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_COL_ORDER"}]
                </a>
            </td>
            <td class="listheader" colspan="2" height="15">
                <a href="Javascript:top.oxid.admin.setSorting( document.search, 'o3revocation', 'oxsendfailed', 'asc');document.search.submit();" class="listheader">
                    [{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_COL_STATUS"}]
                </a>
            </td>
        [{/block}]
    </tr>

    [{assign var="blWhite" value=""}]
    [{assign var="_cnt" value=0}]
    [{foreach from=$mylist item=listitem}]
        [{assign var="_cnt" value=$_cnt+1}]
        <tr id="row.[{$_cnt}]">
            [{block name="admin_revocation_list_item"}]
                [{if $listitem->getId() == $oxid}]
                    [{assign var="listclass" value=listitem4}]
                [{else}]
                    [{assign var="listclass" value="listitem"|cat:$blWhite}]
                [{/if}]
                <td valign="top" class="[{$listclass}]" height="15">
                    <div class="listitemfloating">
                        <a href="Javascript:top.oxid.admin.editThis('[{$listitem->getId()}]');" class="[{$listclass}]">
                            [{$listitem->o3revocation__oxsubmitted->value}]
                        </a>
                    </div>
                </td>
                <td valign="top" class="[{$listclass}]" height="15">
                    <div class="listitemfloating">
                        <a href="Javascript:top.oxid.admin.editThis('[{$listitem->getId()}]');" class="[{$listclass}]">
                            [{$listitem->o3revocation__oxname->getRawValue()|escape:'html'}]
                        </a>
                    </div>
                </td>
                <td valign="top" class="[{$listclass}]" height="15">
                    <div class="listitemfloating">
                        <a href="Javascript:top.oxid.admin.editThis('[{$listitem->getId()}]');" class="[{$listclass}]">
                            [{$listitem->o3revocation__oxemail->getRawValue()|escape:'html'}]
                        </a>
                    </div>
                </td>
                <td valign="top" class="[{$listclass}]" height="15">
                    <div class="listitemfloating">
                        <a href="Javascript:top.oxid.admin.editThis('[{$listitem->getId()}]');" class="[{$listclass}]">
                            [{$listitem->o3revocation__oxorderident->getRawValue()|escape:'html'}]
                        </a>
                    </div>
                </td>
                <td valign="top" class="[{$listclass}]" colspan="2" height="15">
                    <div class="listitemfloating">
                        <a href="Javascript:top.oxid.admin.editThis('[{$listitem->getId()}]');" class="[{$listclass}]">
                            [{if $listitem->o3revocation__oxsendfailed->value}]
                                [{oxmultilang ident="O3_REVOCATION_ADMIN_FLAG_SEND_FAILED"}]
                            [{else}]
                                [{oxmultilang ident="O3_REVOCATION_ADMIN_FLAG_SENT"}]
                            [{/if}]
                        </a>
                    </div>
                </td>
            [{/block}]
        </tr>
        [{if $blWhite == "2"}]
            [{assign var="blWhite" value=""}]
        [{else}]
            [{assign var="blWhite" value="2"}]
        [{/if}]
    [{foreachelse}]
        <tr>
            <td class="listitem" colspan="6">[{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_EMPTY"}]</td>
        </tr>
    [{/foreach}]
    [{include file="pagenavisnippet.tpl" colspan="6"}]
</table>
</form>
</div>

[{include file="pagetabsnippet.tpl"}]

<script type="text/javascript">
if (parent.parent)
{
    parent.parent.sShopTitle   = "[{$actshopobj->oxshops__oxname->getRawValue()|oxaddslashes}]";
    parent.parent.sMenuItem    = "[{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_MENUITEM"}]";
    parent.parent.sMenuSubItem = "[{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_MENUSUBITEM"}]";
    parent.parent.sWorkArea    = "[{$_act}]";
    parent.parent.setTitle();
}
</script>
</body>
</html>
