[{include file="headitem.tpl" title="O3_GUARANTEE_ADMIN_NAV_LABEL"|oxmultilangassign}]

[{* EU guarantee labels (#219) - two independent feature switches. *}]
[{* The label/notice artwork is fixed EU design (Reg. (EU) 2025/1960); *}]
[{* operators only decide WHETHER to render, never how it looks. *}]

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
            <br><small>[{oxmultilang ident="O3_GUARANTEE_ADMIN_CONFIG_SHOW_NOTICE_HINT"}]</small>
        </p>

        <p>
            <label>
                <input type="checkbox" name="blShowDurabilityGuaranteeLabel" value="1"
                       [{if $guarantee.blShowDurabilityGuaranteeLabel}]checked="checked"[{/if}]>
                [{oxmultilang ident="O3_GUARANTEE_ADMIN_CONFIG_SHOW_LABEL_LABEL"}]
            </label>
            <br><small>[{oxmultilang ident="O3_GUARANTEE_ADMIN_CONFIG_SHOW_LABEL_HINT"}]</small>
        </p>

        <p>
            <input type="submit" class="edittext" value="[{oxmultilang ident="GENERAL_SAVE"}]">
        </p>
    </fieldset>
</form>

[{include file="bottomitem.tpl"}]
