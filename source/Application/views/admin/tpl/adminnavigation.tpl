[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

<script type="text/javascript">
    <!--
    function editThis( sID )
    {
        let oTransfer = top.basefrm.edit.document.getElementById( "transfer" );
        oTransfer.oxid.value = sID;
        oTransfer.cl.value = top.basefrm.list.sDefClass;

        //forcing edit frame to reload after submit
        top.forceReloadingEditFrame();

        let oSearch = top.basefrm.list.document.getElementById( "search" );
        oSearch.oxid.value = sID;
        oSearch.actedit.value = 0;
        oSearch.submit();
    }

    window.onload = function ()
    {
        [{if $updatelist == 1}]
            top.oxid.admin.updateList('[{$oxid}]');
        [{/if}]
        let oField = top.oxid.admin.getLockTarget();
        oField.onchange = oField.onkeyup = oField.onmouseout = top.oxid.admin.unlockSave;
    }

    function toggle(reference) {
        let i = 0;
        let toggled = document.querySelectorAll('#' + reference.parentNode.id + ' > ul');
        while (i < toggled.length) {
            if (toggled[i].classList.contains("expanded")) {
                toggled[i].classList.remove('expanded');
                toggled[i].parentNode.classList.remove('expandedChild');
            } else {
                toggled[i].classList.add('expanded');
                toggled[i].parentNode.classList.add('expandedChild');
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
    ul#nav li,
    ul#nav li li {
        list-style: none;
        background: none;
    }

    #nav > li {
        padding-left: 0;
    }

    #nav li ul {
        padding: 0 0 0 5px;
        display: none;
        position: relative;
    }

    #nav li ul.expanded {
        display: block;
    }

    #nav input {
        margin-right: 5px;
    }
    .vatop {
        vertical-align: top;
    }

    #nav ul li{
        margin-left: 3px;
        border-left : 1px solid #AAA;
    }

    #nav ul li:last-child{
        border-color : transparent;
    }

    #nav ul li::before{
        content: '';
        display: block;
        position: absolute;
        margin-top: -3px;
        left: 8px;
        width: 10px;
        height: 10px;
        border: solid #AAA;
        border-width: 0 0 1px 1px;
        float: left;
    }

    #nav ul li a::before {
        content: '+';
        width: 8px;
        height: 9px;
        border: 1px solid #AAA;
        display: block;
        position: absolute;
        background-color: white;
        line-height: 7px;
        left: 3px;
        color: #AAA;
        margin-top: -14px;
        text-align: center;
        padding-right: 1px;
    }

    #nav ul li.expandedChild > a::before {
        content: '−';
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
    <input type="hidden" id="fnc" name="fnc" value="">
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="editval[o3rightsroles__oxid]" value="[{$oxid}]">

    <h1>[{oxmultilang ident="mxadminnavigation"}]</h1>

    <p>
        [{oxmultilang ident="ADMINNAVIGATION_DESC"}]
    </p>

    <table style="border: 0; border-collapse: collapse; border-spacing: 0; width:98%;">
        <tr>
            <td class="vatop edittext" style="width: 50%; padding-top:10px;padding-left:10px;">
                <table>
                    [{block name="admin_adminrights_main_left"}]
                        <tr>
                            <td class="vatop">
                                [{oxmultilang ident="ADMINNAVIGATION_ITEMS"}]
                            </td>
                            <td>
                                [{assign var="selectedElements" value=$roleElementsList->getElementsIdsByObjectId($oxid)}]
                                [{assign var="cssClass" value="nav"}]
                                <ul id="nav">
                                    [{assign var="deepLevel" value=0}]
                                    [{defun name="tree" root=$oView->getMenuTree() cssClass=$cssClass}]
                                        [{assign var="index" value=0}]
                                        [{foreach name="loop" from=$root item="menuitem"}]
                                            [{assign var="deepLevel" value=$deepLevel+1}]
                                            [{if $menuitem->nodeType == XML_ELEMENT_NODE && $menuitem->nodeName != "BTN"}]
                                                [{assign var="index" value=$index+1}]
                                                [{assign var="currCssClass" value=$cssClass|cat:"-"|cat:$index}]
                                                <li id="[{$currCssClass}]">
                                                    [{assign var="menuid" value=$menuitem->getAttribute('id')}]
                                                    <input type="hidden" name="roleElements[[{$menuid}]]" value="0">
                                                    <input onclick="selectChilds(this);" id="[{$menuid}]" type="checkbox" name="roleElements[[{$menuid}]]" value="2" [{if $selectedElements.$menuid == '2'}]checked[{/if}]>
                                                    <label for="[{$menuid}]"></label>
                                                    [{if $menuitem->childNodes->length}]<a onclick="toggle(this)" href="#" class="rc">[{/if}]
                                                        [{oxmultilang ident=$menuitem->getAttribute('name')|default:$menuid noerror=true}]
                                                    [{if $menuitem->childNodes->length}]</a>[{/if}]
                                                    [{if $menuitem->childNodes->length}]
                                                        <ul class="[{if $deepLevel == 1}]expanded[{/if}]">
                                                            [{fun name="tree" root=$menuitem->childNodes cssClass=$currCssClass}]
                                                        </ul>
                                                    [{/if}]
                                                </li>
                                                [{assign var="deepLevel" value=$deepLevel-1}]
                                            [{/if}]
                                        [{/foreach}]
                                    [{/defun}]
                                </ul>
                            </td>
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
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
