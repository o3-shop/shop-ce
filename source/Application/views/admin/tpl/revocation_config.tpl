[{include file="headitem.tpl" title="O3_REVOCATION_ADMIN_NAV_LABEL"|oxmultilangassign}]

[{* §356a BGB electronic revocation feature — admin configuration form. *}]
[{* Cross-field rule: when blRevocationNotifyOperator is on, *}]
[{* sRevocationOperatorEmail must be non-empty AND syntactically valid. *}]
[{* Template-presence gate: cannot enable the feature unless every page *}]
[{* template, every per-language email template, and every translation *}]
[{* key resolves. The activation save is rejected all-or-nothing per D11. *}]

[{if $revocationMissingAssets}]
    <div class="errorbox" role="alert">
        [{* OXID lang convention forbids trailing colons in lang values *}]
        [{* (LangIntegrityTest::testColonsAtTheEnd). The colon is added in the template. *}]
        <strong>[{oxmultilang ident="O3_REVOCATION_ADMIN_GATE_HEADING"}]:</strong>
        <ul>
            [{foreach from=$revocationMissingAssets item=asset}]
                <li>
                    <code>[{$asset.path|escape:'html'}]</code>
                    [{if $asset.lang !== null}]
                        <small>[{oxmultilang ident="O3_REVOCATION_ADMIN_GATE_LANG_TAG"}] [{$asset.lang}]</small>
                    [{/if}]
                    <br>
                    <em>[{$asset.hint|escape:'html'}]</em>
                </li>
            [{/foreach}]
        </ul>
    </div>
[{/if}]

<form name="myedit" id="myedit" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="revocation_config">
    <input type="hidden" name="fnc" value="save">

    <fieldset>
        <legend>[{oxmultilang ident="O3_REVOCATION_ADMIN_NAV_LABEL"}]</legend>

        <p>
            <label>
                <input type="checkbox" name="blShowRevocationForm" value="1"
                       [{if $revocation.blShowRevocationForm}]checked="checked"[{/if}]>
                [{oxmultilang ident="O3_REVOCATION_CONFIG_SHOW_LABEL"}]
            </label>
        </p>

        <p>
            <label>
                <input type="checkbox" name="blRevocationRequireLogin" value="1"
                       [{if $revocation.blRevocationRequireLogin}]checked="checked"[{/if}]>
                [{oxmultilang ident="O3_REVOCATION_CONFIG_REQUIRELOGIN_LABEL"}]
            </label>
        </p>

        <p>
            <label>
                <input type="checkbox" name="blRevocationNotifyOperator" value="1"
                       [{if $revocation.blRevocationNotifyOperator}]checked="checked"[{/if}]>
                [{oxmultilang ident="O3_REVOCATION_CONFIG_NOTIFY_LABEL"}]
            </label>
        </p>

        <p>
            <label for="sRevocationOperatorEmail">
                [{oxmultilang ident="O3_REVOCATION_CONFIG_OPERATOR_EMAIL_LABEL"}]
            </label>
            <input type="email"
                   id="sRevocationOperatorEmail"
                   name="sRevocationOperatorEmail"
                   value="[{$revocation.sRevocationOperatorEmail|escape:'html'}]"
                   [{if $revocationErrors.sRevocationOperatorEmail}]aria-invalid="true" aria-describedby="o3rev_operator_email_err"[{/if}]>
            [{if $revocationErrors.sRevocationOperatorEmail}]
                <span id="o3rev_operator_email_err" class="errorbox">
                    [{oxmultilang ident=$revocationErrors.sRevocationOperatorEmail}]
                </span>
            [{/if}]
        </p>

        <p>
            <input type="submit" value="[{oxmultilang ident="GENERAL_SAVE"}]" class="edittext">
        </p>
    </fieldset>
</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
