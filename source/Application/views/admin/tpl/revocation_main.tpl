[{include file="headitem.tpl" title="O3_REVOCATION_ADMIN_DETAIL_HEADING"|oxmultilangassign}]

[{* §356a BGB electronic revocation submission — admin detail view + actions *}]

[{if $edit}]
    <form name="myedit" id="myedit" action="[{$oViewConf->getSelfLink()}]" method="post">
        [{$oViewConf->getHiddenSid()}]
        <input type="hidden" name="cl" value="revocation_main">
        <input type="hidden" name="oxid" value="[{$edit->getId()}]">

        <fieldset>
            <legend>[{oxmultilang ident="O3_REVOCATION_ADMIN_DETAIL_HEADING"}]</legend>

            <dl>
                <dt>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_OXID"}]</dt>
                <dd><code>[{$edit->getId()|escape:'html'}]</code></dd>

                <dt>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_SUBMITTED"}]</dt>
                <dd>[{$edit->o3revocation__oxsubmitted->value}]</dd>

                <dt>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_NAME"}]</dt>
                <dd>[{$edit->o3revocation__oxname->getRawValue()|escape:'html'}]</dd>

                <dt>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_ORDER_IDENT"}]</dt>
                <dd>[{$edit->o3revocation__oxorderident->getRawValue()|escape:'html'}]</dd>

                <dt>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_EMAIL"}]</dt>
                <dd>[{$edit->o3revocation__oxemail->getRawValue()|escape:'html'}]</dd>

                <dt>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_FREETEXT"}]</dt>
                <dd>
                    [{if $edit->o3revocation__oxfreetext->value}]
                        <pre>[{$edit->o3revocation__oxfreetext->getRawValue()|escape:'html'}]</pre>
                    [{else}]
                        <em>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_FREETEXT_EMPTY"}]</em>
                    [{/if}]
                </dd>

                <dt>[{oxmultilang ident="O3_REVOCATION_ADMIN_FIELD_STATUS"}]</dt>
                <dd>
                    [{if $edit->o3revocation__oxsendfailed->value}]
                        <span class="errorbox">[{oxmultilang ident="O3_REVOCATION_ADMIN_FLAG_SEND_FAILED"}]</span>
                    [{else}]
                        <span>[{oxmultilang ident="O3_REVOCATION_ADMIN_FLAG_SENT"}]</span>
                    [{/if}]
                </dd>
            </dl>
        </fieldset>

        <fieldset>
            <legend>[{oxmultilang ident="O3_REVOCATION_ADMIN_ACTIONS_HEADING"}]</legend>

            <button type="submit" name="fnc" value="resend" class="edittext">
                [{oxmultilang ident="O3_REVOCATION_ADMIN_RESEND_BUTTON"}]
            </button>

            <button type="submit"
                    name="fnc"
                    value="deleteEntry"
                    class="edittext"
                    onclick="return confirm('[{oxmultilang ident="O3_REVOCATION_ADMIN_DELETE_CONFIRM"}]');">
                [{oxmultilang ident="O3_REVOCATION_ADMIN_DELETE_BUTTON"}]
            </button>
        </fieldset>
    </form>
[{else}]
    <p>[{oxmultilang ident="O3_REVOCATION_ADMIN_LIST_EMPTY"}]</p>
[{/if}]

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
