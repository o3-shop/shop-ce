[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

[{if $readonly}]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]

<style type="text/css">
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
    <input type="hidden" name="editval[oxarticles__oxid]" value="[{$oxid}]">

    <table>
        <tr>
            <td>
                <label for="user">
                    Benutzer:
                </label>
            </td>
            <td>
                <select name="user" id="user" onchange="document.getElementById('fnc').value=''; document.getElementById('myedit').submit();">
                    <option value="">Bitte Benutzer ausw&auml;hlen</option>
                    <option value="reducedview">reduzierte Ansicht für alle Benutzer</option>
                    [{foreach from=$oView->getMallAdminUsers() item="user"}]
                        <option value="[{$user.oxid}]" [{if $user.selected}] selected[{/if}]>[{$user.oxusername}] ([{$user.oxfname}] [{$user.oxlname}])</option>
                    [{/foreach}]
                </select>
            </td>
        </tr>
        [{if $oView->showSelectableMenuItems()}]
            <tr>
                <td colspan="2">
                    Eintr&auml;ge mit gesetztem H&auml;kchen werden im Shop-Admin ausgeblendet:
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    [{foreach from=$adminNaviRights->getNaviElements() item="naviItem"}]
                        <div class="indent[{$naviItem.layer}]" style="color:#3c3c3b; font-weight:bold;font-size:12px;">
                            <input type="checkbox" name="emadminnavirightsnavi[]" value="[{$naviItem.id}]" [{if $naviItem.checked}]checked[{/if}]>
                            [{oxmultilang ident=$naviItem.name}]
                        </div>
                    [{/foreach}]
                </td>
            </tr>
            <tr>
                <td><button style="height:30px;background: #ebebeb;border: 1px solid #d3d3d3;color: #be1522;border-radius:4px;" type="submit"><b>Speichern</b></button></td>
                <td></td>
            </tr>
        [{/if}]
    </table>
</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
