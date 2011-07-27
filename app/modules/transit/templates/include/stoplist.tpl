{foreach $routeInfo['stops'] as $stopID => $stopInfo}
  {capture name="subtitle" assign="subtitle"}
    {include file="findInclude:modules/transit/templates/include/predictions.tpl" predictions=$stopInfo['predictions']}
  {/capture}
  {if $subtitle}
    {$routeInfo['stops'][$stopID]['subtitle'] = $subtitle}
  {/if}
{/foreach}

{include file="findInclude:common/templates/results.tpl" results=$routeInfo['stops'] noResultsText="Stop information not available"}
