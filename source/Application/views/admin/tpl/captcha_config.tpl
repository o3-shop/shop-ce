[{include file="headitem.tpl" title="O3_CAPTCHA_ADMIN_NAV_LABEL"|oxmultilangassign}]

[{* CAPTCHA provider — admin configuration form. *}]

<form name="myedit" id="myedit" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="captcha_config">
    <input type="hidden" name="fnc" value="save">

    <fieldset>
        <legend>[{oxmultilang ident="O3_CAPTCHA_ADMIN_NAV_LABEL"}]</legend>

        <p>
            <label for="sCaptchaProvider">
                [{oxmultilang ident="O3_CAPTCHA_PROVIDER_LABEL"}]
            </label>
            <select id="sCaptchaProvider" name="sCaptchaProvider">
                <option value="">[{oxmultilang ident="O3_CAPTCHA_PROVIDER_NONE"}]</option>
                [{foreach from=$oView->getCaptchaProviders() key="provId" item="titleIdent"}]
                    <option value="[{$provId}]" [{if $provId == $oView->getActiveProviderId()}]selected="selected"[{/if}]>[{oxmultilang ident=$titleIdent}]</option>
                [{/foreach}]
            </select>
        </p>

        [{* One (hidden) field group per provider; JS reveals the selected one — no save needed to show fields. *}]
        [{foreach from=$oView->getAllProviderConfigFields() key="provId" item="fields"}]
            <div class="o3-captcha-provider-fields" data-provider="[{$provId}]"
                 [{if $provId != $oView->getActiveProviderId()}]style="display:none;"[{/if}]>
                [{foreach from=$fields item="field"}]
                    [{assign var="ftype" value=$field->getType()}]
                    [{assign var="fname" value="providerField_"|cat:$provId|cat:"_"|cat:$field->getKey()}]
                    <p>
                        <label for="[{$fname}]">
                            [{oxmultilang ident=$field->getLabelIdent()}]
                        </label>
                        [{if $ftype == "password"}]
                            <input type="password" id="[{$fname}]" name="[{$fname}]"
                                   value="[{$oView->getProviderSettingValueFor($provId, $field->getKey())|escape:'html'}]">
                        [{elseif $ftype == "number"}]
                            <input type="number" step="0.1" min="0" max="1" id="[{$fname}]" name="[{$fname}]"
                                   value="[{$oView->getProviderSettingValueFor($provId, $field->getKey())|escape:'html'}]">
                        [{else}]
                            <input type="text" id="[{$fname}]" name="[{$fname}]"
                                   value="[{$oView->getProviderSettingValueFor($provId, $field->getKey())|escape:'html'}]">
                        [{/if}]
                    </p>
                [{/foreach}]
            </div>
        [{/foreach}]

        <p>
            <label>
                <input type="checkbox" name="blCaptchaRequireConsent" value="1"
                       [{if $oView->isConsentRequired()}]checked="checked"[{/if}]>
                [{oxmultilang ident="O3_CAPTCHA_REQUIRE_CONSENT"}]
            </label>
        </p>

        <p>
            <strong>[{oxmultilang ident="O3_CAPTCHA_FORMS_LABEL"}]</strong>
        </p>

        [{foreach from=$oView->getCaptchaFormIds() item="formId"}]
            <p>
                <label>
                    <input type="checkbox" name="blCaptchaForm_[{$formId}]" value="1"
                           [{if $oView->isFormEnabled($formId)}]checked="checked"[{/if}]>
                    [{oxmultilang ident="O3_CAPTCHA_FORM_"|cat:$formId}]
                </label>
            </p>
        [{/foreach}]

        <p>
            <input type="submit" value="[{oxmultilang ident="GENERAL_SAVE"}]" class="edittext">
        </p>
    </fieldset>

    [{* Reveal only the selected provider's credential fields; switching the dropdown
       swaps them in without a save. *}]
    <script type="text/javascript">
        (function () {
            var providerSelect = document.getElementById('sCaptchaProvider');
            if (!providerSelect) {
                return;
            }
            function syncProviderFields() {
                var groups = document.querySelectorAll('.o3-captcha-provider-fields');
                for (var i = 0; i < groups.length; i++) {
                    groups[i].style.display =
                        (groups[i].getAttribute('data-provider') === providerSelect.value) ? '' : 'none';
                }
            }
            providerSelect.addEventListener('change', syncProviderFields);
            syncProviderFields();
        })();
    </script>
</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
