{foreach $routeInfo['stops'] as $stopID => $stopInfo}
  {if count($stopInfo['predictions'])}
    {capture name="subtitle" assign="subtitle"}
      {include file="findInclude:modules/transit/templates/include/predictions.tpl" predictions=$stopInfo['predictions']}
    {/capture}
    {$routeInfo['stops'][$stopID]['subtitle'] = $subtitle}
  {else}
    {capture name="label" assign="label"}
      <span class="smallprint stoptime">{$stopInfo['arrives']|date_format:"%l:%M%p"|lower}</span>
    {/capture}
    {$routeInfo['stops'][$stopID]['label'] = $label}
  {/if}
{/foreach}

{include file="findInclude:common/templates/results.tpl" results=$routeInfo['stops'] noResultsText="Stop information not available" labelColon=false}
