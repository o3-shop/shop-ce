[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

<script type="text/javascript">
    <!--
    function editThis( sID )
    {
        var oTransfer = top.basefrm.edit.document.getElementById( "transfer" );
        oTransfer.oxid.value = sID;
        oTransfer.cl.value = top.basefrm.list.sDefClass;

        //forcing edit frame to reload after submit
        top.forceReloadingEditFrame();

        var oSearch = top.basefrm.list.document.getElementById( "search" );
        oSearch.oxid.value = sID;
        oSearch.actedit.value = 0;
        oSearch.submit();
    }

    window.onload = function ()
    {
        [{if $updatelist == 1}]
        top.oxid.admin.updateList('[{$oxid}]');
        [{/if}]
        var oField = top.oxid.admin.getLockTarget();
        oField.onchange = oField.onkeyup = oField.onmouseout = top.oxid.admin.unlockSave;
    }

    function toggle(reference) {
        let i = 0;
        let toggled = document.querySelectorAll('#' + reference.parentNode.id + ' > ul');
        while (i < toggled.length) {
            if (toggled[i].style.display === "none" || toggled[i].style.display === '') {
                toggled[i].style.display = "block";
            } else {
                toggled[i].style.display = "none";
            }
            i++;
        }
    }

    function selectChilds(reference) {
        let i = 0;
        let childs = document.querySelectorAll("#" + reference.parentNode.id + " [type='checkbox']");
        while (i < childs.length) {
            childs[i].checked = reference.checked;
            i++;
        }

        selectParent(reference);
    }

    function selectParent(reference) {
        if (reference.parentNode && reference.parentNode.parentNode && reference.parentNode.parentNode.parentNode && reference.parentNode.parentNode.parentNode.id) {
            let parent = document.querySelectorAll("#" + reference.parentNode.parentNode.parentNode.id + " > input[type='checkbox']")[0];
            if (parent && reference.checked) {
                parent.checked = reference.checked
                selectParent(parent);
            }
        }
    }

    //-->
</script>

[{if $readonly}]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]

<style>
    .indent1 {
        margin-left: 20px;
    }
    .indent2 {
        margin-left: 40px;
    }
    .indent3 {
        margin-left: 60px;
    }
    .indent4 {
        margin-left: 80px;
    }

    ul#nav li,
    ul#nav li li {
        list-style: none;
        background: none;
    }

    #nav li ul {
        padding: 0 0 0 5px;
        display: none;
    }

    #nav input {
        margin-right: 5px;
    }
    .vatop {
        vertical-align: top;
    }
</style>

<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="oxidCopy" value="[{$oxid}]">
    <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
    <input type="hidden" name="editlanguage" value="[{$editlanguage}]">
</form>

<form name="myedit" id="myedit" action="[{$oViewConf->getSelfLink()}]" method="post" style="padding: 0;margin: 0;height:0;">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
    <input type="hidden" id="fnc" name="fnc" value="save">
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="editval[o3rightsroles__oxid]" value="[{$oxid}]">

    <table cellspacing="0" cellpadding="0" border="0" style="width:98%;">
        <tr>
            <td class="vatop edittext" style="width: 50%; padding-top:10px;padding-left:10px;">
                <table>
                    [{block name="admin_adminrights_main_left"}]
                        <tr>
                            <td class="edittext" width="120">
                                <label for="o3rightsroles__active">
                                    [{oxmultilang ident="RIGHTSROLES_ACTIVE"}]
                                </label>
                            </td>
                            <td class="edittext">
                                <input type="hidden" name="editval[o3rightsroles__active]" value="0">
                                <input type="hidden" name="editval[o3rightsroles__active]" value='0' [{$readonly}]>
                                <input id="o3rightsroles__active" class="edittext" type="checkbox" name="editval[o3rightsroles__active]" value='1' [{if $edit->o3rightsroles__active->value == 1}]checked[{/if}] [{$readonly}]>
                                [{oxinputhelp ident="HELP_RIGHTSROLES_ACTIVE"}]
                            </td>
                        </tr>
                        <tr>
                            <td class="edittext">
                                <label for="o3rightsroles__title">
                                    [{oxmultilang ident="RIGHTSROLES_TITLE"}]
                                </label>
                            </td>
                            <td class="edittext">
                                <input id="o3rightsroles__title" type="text" class="editinput" size="25" maxlength="[{$edit->o3rightsroles__title->fldmax_length}]" name="editval[o3rightsroles__title]" value="[{$edit->o3rightsroles__title->value}]" [{$readonly}]>
                                [{oxinputhelp ident="HELP_RIGHTSROLES_TITLE"}]
                            </td>
                        </tr>
                        [{if $oxid != '-1'}]
                            <tr>
                                <td class="vatop">
                                    [{oxmultilang ident="RIGHTSROLES_ITEMS"}]
                                </td>
                                <td>
                                    [{assign var="selectedElements" value=$roleElementsList->getElementsIdsByRole($oxid)}]
                                    [{block name="admin_navigation_menustructure"}]
                                        [{assign var='mh' value=0}]
                                        [{foreach from=$oView->getMenuTree() item=menuholder}]
                                            [{if $menuholder->nodeType == XML_ELEMENT_NODE && $menuholder->childNodes->length}]
                                                [{assign var='mh' value=$mh+1}]
                                                [{assign var='mn' value=0}]
                                                <ul id="nav">
                                                    [{strip}]
                                                        [{foreach from=$menuholder->childNodes item=menuitem name=menuloop}]
                                                            [{assign var='actClass' value=$menuitem->childNodes->length}]
                                                            [{if $menuitem->nodeType == XML_ELEMENT_NODE}]
                                                                [{assign var='mn' value=$mn+1}]
                                                                [{assign var='sm' value=0}]
                                                                <li id="nav-[{$mh}]-[{$mn}]">
                                                                    [{assign var="menuid" value=$menuitem->getAttribute('id')}]
                                                                    <input onclick="selectChilds(this);" id="[{$menuid}]" type="checkbox" name="roleElements[]" value="[{$menuid}]" [{if $menuid|in_array:$selectedElements}]checked[{/if}]>
                                                                    <label for="[{$menuid}]"></label>
                                                                    <a onclick="toggle(this)" href="#" class="rc">
                                                                        [{if $menuitem->childNodes->length}]&raquo; [{/if}]
                                                                        [{oxmultilang ident=$menuitem->getAttribute('name')|default:$menuid noerror=true}]
                                                                    </a>
                                                                    [{if $menuitem->childNodes->length}]
                                                                        <ul>
                                                                            [{foreach from=$menuitem->childNodes item=submenuitem}]
                                                                                [{if $submenuitem->nodeType == XML_ELEMENT_NODE}]
                                                                                    [{assign var='sm' value=$sm+1}]
                                                                                    [{assign var='xs' value=0}]
                                                                                    [{if $submenuitem->getAttribute('linkicon')}] [{assign var='linkicon' value=$submenuitem->getAttribute('linkicon')}][{/if}]
                                                                                    <li id="nav-[{$mh}]-[{$mn}]-[{$sm}]" rel="nav-[{$mh}]-[{$mn}]">
                                                                                        [{assign var="tabs" value=$oView->getTabs($submenuitem->getAttribute('cl'))}]
                                                                                        [{assign var="menuid" value=$submenuitem->getAttribute('id')}]
                                                                                        <input onclick="selectChilds(this);" id="[{$menuid}]" type="checkbox" name="roleElements[]" value="[{$menuid}]" [{if $menuid|in_array:$selectedElements}]checked[{/if}]>
                                                                                        <label for="[{$menuid}]"></label>
                                                                                        [{if $tabs->count()}]
                                                                                            <a  onclick="toggle(this);" href="#" class="rc">
                                                                                            [{if $linkicon}]<span class="[{$linkicon}]">[{/if}]
                                                                                            [{if $tabs}]&raquo; [{/if}]
                                                                                        [{/if}]
                                                                                            [{oxmultilang ident=$submenuitem->getAttribute('name')|default:$submenuitem->getAttribute('id') noerror=true}]
                                                                                            [{if $linkicon}]</span>[{/if}]
                                                                                        [{if $tabs}]
                                                                                            </a>
                                                                                        [{/if}]

                                                                                        [{if $tabs->count()}]
                                                                                            <ul>
                                                                                                [{foreach from=$tabs item="tab"}]
                                                                                                    [{if $tab->nodeType == XML_ELEMENT_NODE}]
                                                                                                        [{assign var='xs' value=$xs+1}]
                                                                                                        <li id="nav-[{$mh}]-[{$mn}]-[{$sm}]-[{$xs}]">
                                                                                                            [{assign var="tabid" value=$tab->getAttribute('id')}]
                                                                                                            <input onclick="selectChilds(this);" id="[{$tabid}]" type="checkbox" name="roleElements[]" value="[{$tabid}]" [{if $tabid|in_array:$selectedElements}]checked[{/if}]>
                                                                                                            <label for="[{$tabid}]"></label>
                                                                                                            [{oxmultilang ident="RIGHTSROLES_TAB" suffix="COLON"}] [{oxmultilang ident=$tab->getAttribute('name')|default:$tab->getAttribute('id') noerror=true}]
                                                                                                        </li>
                                                                                                    [{/if}]
                                                                                                [{/foreach}]
                                                                                            </ul>

    [{*                                                                                        [{assign var="buttons" value=$oView->getButtons($tab->getAttribute('cl'))}]*}]
    [{*                                                                                        [{if $buttons}]*}]
    [{*                                                                                            <ul>*}]
    [{*                                                                                                [{foreach from=$buttons item="btn" key="btnid"}]*}]
    [{*                                                                                                    <li>*}]
    [{*                                                                                                        <input id="[{$btnid}]" type="checkbox" name="roleElements[]" value="[{$btnid}]" [{if $btnid|in_array:$selectedElements}]checked[{/if}]>*}]
    [{*                                                                                                        <label for="[{$btnid}]"></label>*}]
    [{*                                                                                                        [{oxmultilang ident="RIGHTSROLES_BUTTON" suffix="COLON"}] [{oxmultilang ident=$btnid noerror=true}]*}]
    [{*                                                                                                    </li>*}]
    [{*                                                                                                [{/foreach}]*}]
    [{*                                                                                            </ul>*}]
    [{*                                                                                            [{oxscript add="toggle(document.querySelectorAll('#nav-$mh-$mn-$sm > ul')[0]);"}]*}]
    [{*                                                                                        [{/if}]*}]
                                                                                        [{/if}]
                                                                                    </li>
                                                                                    [{assign var='linkicon' value=''}]
                                                                                [{/if}]
                                                                            [{/foreach}]
                                                                        </ul>
                                                                    [{/if}]
                                                                </li>
                                                            [{/if}]
                                                        [{/foreach}]
                                                    [{/strip}]
                                                </ul>
                                            [{/if}]
                                        [{/foreach}]
                                    [{/block}]
                                </td>
                            [{/if}]
                        </tr>
                    [{/block}]
                    <tr>
                        <td>
                            <input type="submit" class="edittext" id="oLockButton" name="saveArticle" value="[{oxmultilang ident="ARTICLE_MAIN_SAVE"}]" onClick="document.myedit.fnc.value='save'" [{$readonly}]>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="vatop edittext" style="width: 50%; padding-top:10px;padding-left:10px;">

                <!-- Starting right column -->
                <table>
                    <tr>
                        <td class="edittext">
                            [{block name="admin_adminrights_main_assign_users"}]
                                [{if $oxid != "-1"}]
                                    <input [{$readonly}] type="button" value="[{oxmultilang ident="GENERAL_ASSIGNUSERS"}]" class="edittext" onclick="showDialog('&cl=adminrights_main&aoc=1&oxid=[{$oxid}]');">
                                [{/if}]
                            [{/block}]
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
