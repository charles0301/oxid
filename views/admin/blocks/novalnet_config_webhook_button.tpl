[{$smarty.block.parent}]
[{if $oModule->getInfo('id') === "novalnet"}]
    <input type="submit" class="confinput" name="save" value="[{oxmultilang ident="GENERAL_SAVE"}]" onClick="Javascript:document.module_configuration.fnc.value='save'" [{$readonly}]>
[{/if}]
