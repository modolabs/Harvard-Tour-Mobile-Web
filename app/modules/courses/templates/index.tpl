{include file="findInclude:common/templates/header.tpl"}

{block name="navList"}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$courses subTitleNewline=true}
{/block}

{if $catalogItems}
 {include file="findInclude:common/templates/navlist.tpl" navlistItems=$catalogItems}
{/if}
{include file="findInclude:common/templates/footer.tpl"}
