{extends file="findExtends:modules/{$moduleID}/templates/route.tpl"}

{block name="routeInfo"}
  {$smarty.block.parent}
  (<a href="{$refreshURL}">refresh</a>)
{/block}

{block name="autoReload"}
{/block}
