[{include file="headitem.tpl" title="O3_REVOCATION_ADMIN_DETAIL_HEADING"|oxmultilangassign}]

[{* §356a BGB electronic revocation submission — admin detail view + actions *}]

[{* OXID admin contract: every *_main.tpl ships a hidden transfer form *}]
[{* that top.oxid.admin.editThis(sID) writes oxid+cl into and submits to *}]
[{* navigate the edit frame to the selected row (out/admin/src/oxid.js).  *}]
<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="cl" value="revocation_main">
</form>

[{if $edit}]
    [{* Sibling-template pattern: hidden fnc starts empty, the action *}]
    [{* button's onClick sets it before submit. Buttons use input[type=submit] *}]
    [{* with class=edittext so wave admin CSS picks them up. *}]
    <form name="myedit" id="myedit" action="[{$oViewConf->getSelfLink()}]" method="post">
        [{$oViewConf->getHiddenSid()}]
        <input type="hidden" name="cl"   value="revocation_main">
        <input type="hidden" name="fnc"  value="">
        <input type="hidden" name="oxid" value="[{$edit->getId()}]">

        <fieldset>
            <legend>[{oxmultilang ident="O3_REVOCATION_ADMIN_DETAIL_HEADING"}]</legend>

            [{* Canonical OXID admin detail layout — 2-column table with class="edittext" on *}]
            [{* every cell. Colgroup pins the label column at 280px so long labels like *}]
            [{* "Bestellnummer (vom Kunden eingegeben)" don't crowd the value cell. *}]
            <table cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:6px 0 4px 4px;">
                <colgroup>
                    <col style="width:280px;">
                    <col>
                </colgroup>
                <tr>
                    <td class="edittext" valign="top" style="padding:4px 16px 4px 0;"><b>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_OXID"}]</b></td>
                    <td class="edittext" style="padding:4px 0;"><code>[{$edit->getId()|escape:'html'}]</code></td>
                </tr>
                <tr>
                    <td class="edittext" valign="top" style="padding:4px 16px 4px 0;"><b>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_SUBMITTED"}]</b></td>
                    <td class="edittext" style="padding:4px 0;">[{$edit->o3revocation__oxsubmitted|oxformdate}]</td>
                </tr>
                <tr>
                    <td class="edittext" valign="top" style="padding:4px 16px 4px 0;"><b>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_NAME"}]</b></td>
                    <td class="edittext" style="padding:4px 0;">[{$edit->o3revocation__oxname->getRawValue()|escape:'html'}]</td>
                </tr>
                <tr>
                    <td class="edittext" valign="top" style="padding:4px 16px 4px 0;"><b>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_ORDER_IDENT"}]</b></td>
                    <td class="edittext" style="padding:4px 0;">[{$edit->o3revocation__oxorderident->getRawValue()|escape:'html'}]</td>
                </tr>
                <tr>
                    <td class="edittext" valign="top" style="padding:4px 16px 4px 0;"><b>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_EMAIL"}]</b></td>
                    <td class="edittext" style="padding:4px 0;">
                        [{assign var="revEmail" value=$edit->o3revocation__oxemail->getRawValue()}]
                        <a href="mailto:[{$revEmail|escape:'html'}]" class="edittext">[{$revEmail|escape:'html'}]</a>
                    </td>
                </tr>
                [{* Freetext breaks the 2-column grid — full-width row matches OXID's *}]
                [{* long-description pattern; <pre> preserves customer-entered newlines. *}]
                <tr>
                    <td class="edittext" colspan="2" valign="top" style="padding:10px 0 4px 0;"><b>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_FREETEXT"}]</b></td>
                </tr>
                <tr>
                    <td class="edittext" colspan="2" style="padding:0 0 14px 0;">
                        [{if $edit->o3revocation__oxfreetext->value}]
                            <pre style="white-space:pre-wrap; margin:0; padding:6px 8px; background:#f7f7f7; border:1px solid #e3e3e3; border-radius:2px;">[{$edit->o3revocation__oxfreetext->getRawValue()|escape:'html'}]</pre>
                        [{else}]
                            <em>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_FREETEXT_EMPTY"}]</em>
                        [{/if}]
                    </td>
                </tr>
                <tr>
                    <td class="edittext" valign="top" style="padding:4px 16px 4px 0;"><b>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_STATUS"}]</b></td>
                    <td class="edittext" style="padding:4px 0;">
                        [{if $edit->o3revocation__oxsendfailed->value}]
                            <span class="errorbox">[{oxmultilang ident="O3_REVOCATION_ADMIN_FLAG_SEND_FAILED"}]</span>
                        [{else}]
                            [{oxmultilang ident="O3_REVOCATION_ADMIN_FLAG_SENT"}]
                        [{/if}]
                    </td>
                </tr>
            </table>
        </fieldset>

        <fieldset>
            <legend>[{oxmultilang ident="O3_REVOCATION_ADMIN_ACTIONS_HEADING"}]</legend>

            <input type="submit"
                   class="edittext"
                   value="[{oxmultilang ident="O3_REVOCATION_ADMIN_RESEND_BUTTON"}]"
                   onClick="Javascript:document.myedit.fnc.value='resend';">

            [{* Delegate delete to OXID's canonical top.oxid.admin.deleteThis JS *}]
            [{* (out/admin/src/oxid.js) — it submits the list-frame's search form *}]
            [{* with fnc=deleteentry, which invokes RevocationList::deleteEntry() *}]
            [{* and re-renders the LIST automatically. Submitting from the detail *}]
            [{* form here would only re-render the detail and leave the list stale. *}]
            <input type="button"
                   class="edittext"
                   value="[{oxmultilang ident="O3_REVOCATION_ADMIN_DELETE_BUTTON"}]"
                   onClick="if(confirm('[{oxmultilang ident="O3_REVOCATION_ADMIN_DELETE_CONFIRM"}]')) top.oxid.admin.deleteThis('[{$edit->getId()}]');">
        </fieldset>
    </form>
[{else}]
    <p>[{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_EMPTY"}]</p>
[{/if}]

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
