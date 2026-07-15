[{include file="headitem.tpl" title="O3_GUARANTEE_ADMIN_NAV_LABEL"|oxmultilangassign}]

[{* EU harmonised guarantee labels (EmpCo Directive (EU) 2024/825, issue #219). *}]
[{* Placement must be one of the allowed values; an invalid value rejects the *}]
[{* entire save (all-or-nothing), same rule as the revocation config page. *}]

[{if $guaranteeNoQualifyingProductsHint}]
    <div class="messagebox" role="status">
        [{oxmultilang ident="O3_GUARANTEE_ADMIN_NO_QUALIFYING_PRODUCTS_HINT"}]
    </div>
[{/if}]

<form name="myedit" id="myedit" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="guarantee_config">
    <input type="hidden" name="fnc" value="save">

    <fieldset>
        <legend>[{oxmultilang ident="O3_GUARANTEE_ADMIN_NAV_LABEL"}]</legend>

        <p>
            <label>
                <input type="checkbox" name="blShowLegalGuaranteeNotice" value="1"
                       [{if $guarantee.blShowLegalGuaranteeNotice}]checked="checked"[{/if}]>
                [{oxmultilang ident="O3_GUARANTEE_ADMIN_CONFIG_SHOW_NOTICE_LABEL"}]
            </label>
            [{oxinputhelp ident="O3_GUARANTEE_ADMIN_HELP_SHOW_NOTICE"}]
        </p>

        <p>
            <label>
                <input type="checkbox" name="blShowDurabilityGuaranteeLabel" value="1"
                       [{if $guarantee.blShowDurabilityGuaranteeLabel}]checked="checked"[{/if}]>
                [{oxmultilang ident="O3_GUARANTEE_ADMIN_CONFIG_SHOW_LABEL_LABEL"}]
            </label>
            [{oxinputhelp ident="O3_GUARANTEE_ADMIN_HELP_SHOW_LABEL"}]
        </p>

        <p>
            <label for="sLegalGuaranteeNoticePlacement">
                [{oxmultilang ident="O3_GUARANTEE_ADMIN_CONFIG_PLACEMENT_LABEL"}]
            </label>
            <select id="sLegalGuaranteeNoticePlacement" name="sLegalGuaranteeNoticePlacement"
                    [{if $guaranteeErrors.sLegalGuaranteeNoticePlacement}]aria-invalid="true" aria-describedby="o3guar_placement_err"[{/if}]>
                <option value="footer" [{if $guarantee.sLegalGuaranteeNoticePlacement eq "footer"}]selected="selected"[{/if}]>
                    [{oxmultilang ident="O3_GUARANTEE_ADMIN_CONFIG_PLACEMENT_FOOTER"}]
                </option>
                <option value="page" [{if $guarantee.sLegalGuaranteeNoticePlacement eq "page"}]selected="selected"[{/if}]>
                    [{oxmultilang ident="O3_GUARANTEE_ADMIN_CONFIG_PLACEMENT_PAGE"}]
                </option>
            </select>
            [{if $guaranteeErrors.sLegalGuaranteeNoticePlacement}]
                <span id="o3guar_placement_err" class="errorbox">
                    [{oxmultilang ident=$guaranteeErrors.sLegalGuaranteeNoticePlacement}]
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
