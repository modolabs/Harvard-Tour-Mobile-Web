{include file="findInclude:common/templates/header.tpl"}
{if $resources}
{block name="resources"}
{foreach $resources as $itemname =>$item}
<h2>{$itemname}</h2>
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$item}
{/foreach}
{/block}
{/if}
{include file="findInclude:common/templates/footer.tpl"}
