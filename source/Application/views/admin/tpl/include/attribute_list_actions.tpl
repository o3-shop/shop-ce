[{if !$readonly}]
    <a href="Javascript:top.oxid.admin.deleteThis('[{$listitem->oxattribute__oxid->value}]');" class="delete" id="del.[{$_cnt}]" title="" [{include file="help.tpl" helpid=item_delete}]></a>
[{/if}]