{extends file="findExtends:modules/{$moduleID}/templates/stop.tpl"}

{block name="stopInfo"}
  {$smarty.block.parent}
  &nbsp;(<a href="{$refreshURL}">refresh</a>)
{/block}

{block name="autoReload"}
{/block}
