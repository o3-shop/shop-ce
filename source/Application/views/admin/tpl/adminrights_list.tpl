[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign box="list"}]
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
        [{include file="_formparams.tpl" cl="adminrights_list" lstrt=$lstrt actedit=$actedit oxid=$oxid fnc="" language=$actlang editlanguage=$actlang}]
        <table cellspacing="0" cellpadding="0" border="0" width="100%">
            <colgroup>
                [{block name="admin_rights_list_colgroup"}]
                    <col width="3%">
                    <col width="95%">
                    <col width="2%">
                [{/block}]
            </colgroup>
            <tr class="listitem">
                [{block name="admin_rights_list_filter"}]
                    <td style="vertical-align: top; text-align: right" class="listfilter first">
                        <div class="r1"><div class="b1">&nbsp;</div></div>
                    </td>
                    <td style="vertical-align: top; text-align: right" class="listfilter">
                        <div class="r1">
                            <div class="b1">
                                <input class="listedit" type="text" size="50" maxlength="128" name="where[o3rightsroles][title]" value="[{$where.o3rightsroles.title}]">
                            </div>
                        </div>
                    </td>
                    <td style="vertical-align: top" class="listfilter" nowrap>
                        <div class="r1">
                            <div class="b1">
                                <div class="find">
                                    <select name="changelang" class="editinput" onChange="top.oxid.admin.changeLanguage();">
                                        [{foreach from=$languages item=lang}]
                                            <option value="[{$lang->id}]" [{if $lang->selected}]SELECTED[{/if}]>[{$lang->name}]</option>
                                        [{/foreach}]
                                    </select>
                                    <input class="listedit" type="submit" name="submitit" value="[{oxmultilang ident="GENERAL_SEARCH"}]" onClick="document.search.lstrt.value=0;">
                                </div>
                            </div>
                        </div>
                    </td>
                [{/block}]
            </tr>
            <tr class="listitem">
                [{block name="admin_rights_list_sorting"}]
                    <td class="listheader first" style="height: 15px; width: 30px; text-align: center"><a href="Javascript:top.oxid.admin.setSorting( document.search, 'o3rightsroles', 'oxactive', 'asc');document.search.submit();" class="listheader">[{oxmultilang ident="GENERAL_ACTIVTITLE"}]</a></td>
                    <td class="listheader first" style="height: 15px; width: 30px; text-align: center"><a href="Javascript:top.oxid.admin.setSorting( document.search, 'o3rightsroles', 'title', 'asc');document.search.submit();" class="listheader">[{oxmultilang ident="GENERAL_TITLE"}]</a></td>
                    <td class="listheader"></td>
                [{/block}]
            </tr>

            [{assign var="blWhite" value=""}]
            [{assign var="_cnt" value=0}]
            [{foreach from=$mylist item=listitem}]
                [{assign var="_cnt" value=$_cnt+1}]
                <tr id="row.[{$_cnt}]">

                    [{block name="admin_rights_list_item"}]
                        [{if $listitem->blacklist == 1}]
                            [{assign var="listclass" value=listitem3}]
                        [{else}]
                            [{assign var="listclass" value="listitem"|cat:$blWhite}]
                        [{/if}]
                        [{if $listitem->getId() == $oxid}]
                            [{assign var="listclass" value=listitem4}]
                        [{/if}]
                        <td valign="top" class="[{$listclass}][{if $listitem->getFieldData('active') == 1}] active[{/if}]" height="15">
                            <div class="listitemfloating">
                                <a href="Javascript:top.oxid.admin.editThis('[{$listitem->getId()}]');" class="[{$listclass}]">
                                    &nbsp
                                </a>
                            </div>
                        </td>
                        <td valign="top" class="[{$listclass}]">
                            <div class="listitemfloating">
                                <a href="Javascript:top.oxid.admin.editThis('[{$listitem->getId()}]');" class="[{$listclass}]">
                                    [{$listitem->getFieldData('title')}]
                                </a>
                            </div>
                        </td>
                        <td class="[{$listclass}]">
                            [{if !$readonly}]
                                <a href="Javascript:top.oxid.admin.deleteThis('[{$listitem->getId()}]');" class="delete" id="del.[{$_cnt}]" title="" [{include file="help.tpl" helpid=item_delete}]></a>
                            [{/if}]
                        </td>
                    [{/block}]
                </tr>
                [{if $blWhite == "2"}]
                    [{assign var="blWhite" value=""}]
                [{else}]
                    [{assign var="blWhite" value="2"}]
                [{/if}]
            [{/foreach}]
            [{include file="pagenavisnippet.tpl" colspan="5"}]
        </table>
    </form>
</div>

[{include file="pagetabsnippet.tpl"}]

<script type="text/javascript">
    if (parent.parent)
    {   parent.parent.sShopTitle   = "[{$actshopobj->oxshops__oxname->getRawValue()|oxaddslashes}]";
        parent.parent.sMenuItem    = "[{oxmultilang ident="ADMINRIGHTS_LIST_MENUITEM"}]";
        parent.parent.sMenuSubItem = "[{oxmultilang ident="ADMINRIGHTS_LIST_MENUSUBITEM"}]";
        parent.parent.sWorkArea    = "[{$_act}]";
        parent.parent.setTitle();
    }
</script>
</body>
</html>
