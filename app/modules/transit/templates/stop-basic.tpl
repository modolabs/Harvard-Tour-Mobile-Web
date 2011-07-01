{extends file="findExtends:modules/{$moduleID}/templates/stop.tpl"}

{block name="refreshButton"}
{/block}

{block name="headerServiceLogo"}
{/block}

{block name="stopInfo"}
  {$smarty.block.parent}
  &nbsp;(<a href="{$refreshURL}">refresh</a>)
{/block}

{block name="autoReload"}
{/block}
