<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    [{block name="admin_home_head"}]
        <title>[{oxmultilang ident="MAIN_TITLE"}]</title>
        <link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]main.css">
        <link rel="stylesheet" href="[{$oViewConf->getResourceUrl()}]colors_[{$oViewConf->getEdition()|lower}].css">
        <meta http-equiv="Content-Type" content="text/html; charset=[{$charset}]">
    [{/block}]
</head>
<body>

<script type="text/javascript">
    parent.sShopTitle = "[{$actshop|oxaddslashes}]";
    parent.setTitle();
</script>

<h1>[{oxmultilang ident="NAVIGATION_HOME"}]</h1>
<p class="desc">
    <b>[{oxmultilang ident="HOME_DESC"}]</b>
</p>
<hr style="margin-bottom:20px">

[{assign var="hasNotices" value=false}]

[{if $aMessage}]
    [{assign var="hasNotices" value=true}]
    <div class="messagebox">
        <div style="margin-bottom:10px"><b>[{oxmultilang ident="MAIN_INFO"}]</b></div>
        [{foreach from=$aMessage item=sMessage key=class}]
            <p class="[{$class}]" style="font-weight:normal; margin:0 0 5px 0">[{$sMessage|replace:"<br>":"</p><p class=\"`$class`\" style=\"font-weight:normal; margin:0 0 5px 0\">"}]</p>
        [{/foreach}]
    </div>
[{/if}]

[{block name="admin_home_updatecheck"}]
[{if $updateCheckResult}]
    [{if $updateCheckResult->isCoreUpdateAvailable() || $updateCheckResult->getOutdatedModules()}]
    [{assign var="hasNotices" value=true}]
    <div class="messagebox">
        <div style="margin-bottom:10px"><b>[{oxmultilang ident="UPDATECHECK_TITLE"}]</b></div>
        [{if $updateCheckResult->isCoreUpdateAvailable()}]
            <p style="color:#e67e22; margin:0 0 5px 0">
                [{oxmultilang ident="UPDATECHECK_CORE_NOTICE" args=$updateCheckResult->getLatestCoreVersion()}]
            </p>
        [{/if}]
        [{if $updateCheckResult->getOutdatedModules()}]
            <table cellspacing="0" cellpadding="2" border="0">
                <tr>
                    <td style="padding-right:15px"><b>[{oxmultilang ident="UPDATECHECK_MODULE_ID"}]</b></td>
                    <td style="padding-right:15px; text-align:center"><b>[{oxmultilang ident="UPDATECHECK_MODULE_INSTALLED"}]</b></td>
                    <td style="padding-right:15px; text-align:center"><b>[{oxmultilang ident="UPDATECHECK_MODULE_LATEST"}]</b></td>
                    <td></td>
                </tr>
                [{foreach from=$updateCheckResult->getOutdatedModules() item=module}]
                <tr>
                    <td style="padding-right:15px">[{$module.id}]</td>
                    <td style="padding-right:15px; text-align:center">[{$module.installed_version}]</td>
                    <td style="padding-right:15px; text-align:center">[{$module.latest_version}]</td>
                    <td>[{if $module.url}]<a href="[{$module.url}]" target="_blank">[{oxmultilang ident="UPDATECHECK_MODULE_LINK"}]</a>[{/if}]</td>
                </tr>
                [{/foreach}]
            </table>
        [{/if}]
    </div>
    [{/if}]
[{/if}]
[{/block}]

[{if $hasNotices}]
    <hr>
[{/if}]

[{block name="admin_home_navigation_items"}]

    <table width="100%" height="84%">
    [{assign var="shMen" value=1}]

    [{foreach from=$menustructure item=menuholder}]
    [{if $shMen && $menuholder->nodeType == XML_ELEMENT_NODE && $menuholder->childNodes->length}]

        [{assign var="nrCol" value=1}]
        [{assign var="ttCol" value=1}]
        [{assign var="mxCol" value=3}]
        [{assign var="inCol" value=$menuholder->childNodes->length/$mxCol|round}]
        [{assign var="shMen" value=0}]
        [{assign var="mn" value=1}]
            <tr>
            <td valign="top" width="30%">
            [{foreach from=$menuholder->childNodes item=menuitem}]
            [{if $menuitem->nodeType == XML_ELEMENT_NODE && $menuitem->childNodes->length}]
                [{assign var="sb" value=1}]
                <dl [{if $nrCol == 1}]class="first"[{/if}]>
                    <dt>[{oxmultilang ident=$menuitem->getAttribute('name')|default:$menuitem->getAttribute('id')}]</dt>
                    <dd>
                        <ul>
                        [{strip}]
                        [{foreach from=$menuitem->childNodes item=submenuitem}]
                        [{if $submenuitem->nodeType == XML_ELEMENT_NODE}]
                            <li>
                                <a href="[{$submenuitem->getAttribute('link')}]" onclick="_homeExpAct('nav-1-[{$mn}]','nav-1-[{$mn}]-[{$sb}]');" target="basefrm"><b>[{oxmultilang ident=$submenuitem->getAttribute('name')|default:$submenuitem->getAttribute('id')}]</b></a>
                            </li>
                            [{assign var="sb" value=$sb+1}]
                        [{/if}]
                        [{/foreach}]
                        [{/strip}]
                        </ul>
                    </dd>
                </dl>
                [{assign var="mn" value=$mn+1}]
                [{if $nrCol == $inCol && $ttCol<$mxCol}]
                    </td><td width="5%"></td><td valign="top" width="30%">
                    [{assign var="nrCol" value=1}]
                    [{assign var="ttCol" value=$ttCol+1}]
                [{else}]
                    [{assign var="nrCol" value=$nrCol+1}]
                [{/if}]

            [{/if}]
            [{/foreach}]
            </td>
            </tr>
    [{/if}]
    [{/foreach}]
[{/block}]
</table>
<script type="text/javascript">
    <!--
    function _homeExpAct(mnid,sbid){
        top.navigation.adminnav._navExtExpAct(mnid,sbid);
    }
    //-->
    </script>
</body>
</html>