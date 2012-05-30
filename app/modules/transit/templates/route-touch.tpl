{extends file="findExtends:modules/{$moduleID}/templates/route.tpl"}

{block name="headerServiceInfo"}
  {$smarty.block.parent}
  &nbsp;(<a href="{$refreshURL}">refresh</a>)
{/block}

{block name="autoReload"}
{/block}
