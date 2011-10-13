{if $routeInfo['scheduleView'] && isset($routeInfo['directions'], $routeInfo['directions'][$direction])}
  {$directionInfo = $routeInfo['directions'][$direction]}
  {block name="maxColumns"}
    {$maxColumns = 4}
  {/block}
  {$columnCount = max(1, min(count($directionInfo['segments']), $maxColumns))}
  {$columnWidth = 100}
  {if $columnCount > 1}
    {$columnWidth = ceil(100 / $columnCount)}
  {/if}
  
  <table id="scheduleView"{block name="tableAttrs"}{/block}>
    <tr class="stopicons">
      {block name="stopIconSuffix"}
        {$stopIcon = "shuttle@2x.png"}
      {/block}
      {foreach $directionInfo['segments'] as $i => $segmentInfo}
        {if $segmentInfo@index < $maxColumns}
          <td align="center" class="{if $segmentInfo@first}first{/if}" width="{$columnWidth}%">
            <div class="smallprint">
              {if $stopIcon}
                <img src="/modules/transit/images/{$stopIcon}" width="24" height="24" />
              {/if}
              {$subtitleText = "BUS_COLUMN_TEXT"|getLocalizedString:($i+1)}
              {if $subtitleText && $stopIcon}<br />{/if}
              {$subtitleText}
            </div>
          </td>
        {/if}
      {/foreach}
    </tr>
    {foreach $directionInfo['stops'] as $i => $stopInfo}
      {$stopURL = $routeInfo['stops'][$stopInfo['id']]['url']}
      <tr class="stopnames">
        <td {if $columnCount > 1}colspan="{$columnCount}"{/if}{if $stopInfo@first} class="first"{/if} width="100%">
          <a href="{$stopURL}">{$stopInfo['name']}</a>
        </td>
      </tr>
      <tr class="stoptimes">
        {if count($directionInfo['segments'])}
          {foreach $directionInfo['segments'] as $j => $segmentInfo}
            {if $segmentInfo@index < $maxColumns}
              {$hasArrivalTime = isset($segmentInfo['stops'][$i]['arrives']) && $segmentInfo['stops'][$i]['arrives'] >= $now}
              <td align="center" class="{if !$hasArrivalTime}skipped{if $segmentInfo@first} {/if}{/if}{if $segmentInfo@first}first{/if}" width="{$columnWidth}%">
                {if $hasArrivalTime}
                  {$arrivalFormat = "ARRIVAL_DATE_FORMAT"|getLocalizedString}
                  {$segmentInfo['stops'][$i]['arrives']|date_format:$arrivalFormat|lower}
                {else}
                  &mdash;
                {/if}
              </td>
            {/if}
          {/foreach}
        {else}
          <td align="center" class="skipped first" width="100%">&mdash;</td>
        {/if}
      </tr>
    {/foreach}
  </table>
{else}
  {foreach $routeInfo['stops'] as $stopID => $stopInfo}
    {if count($stopInfo['predictions'])}
      {capture name="subtitle" assign="subtitle"}
        {include file="findInclude:modules/transit/templates/include/predictions.tpl" predictions=$stopInfo['predictions']}
      {/capture}
      {$routeInfo['stops'][$stopID]['subtitle'] = $subtitle}
    {elseif $stopInfo['arrives'] && $routeInfo['running']}
      {capture name="label" assign="label"}
        <span class="smallprint stoptime">{$stopInfo['arrives']|date_format:"%l:%M%p"|lower}</span>
      {/capture}
      {$routeInfo['stops'][$stopID]['label'] = $label}
    {/if}
  {/foreach}
  
  {if count($routeInfo['stops'])}
    {block name="flatList"}
      {include file="findInclude:common/templates/results.tpl" results=$routeInfo['stops'] labelColon=false resultslistID="listView"}
    {/block}
  {else}
    <div class="nonfocal">
      {"NO_STOP_INFO"|getLocalizedString}
    </div>
  {/if}
{/if}
