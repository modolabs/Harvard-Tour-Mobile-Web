{$ellipsisCount = 0}
{$results = array()}
{foreach $stops as $i => $stop}
  {$result = array()}
  {capture name="title" assign="title"}
    <div class="listthumb">
      <img src="{$stop['thumbnail']}" alt="Approach photo" width="75" height="50" border="0" class="listphoto" />
    </div>
    <div class="listpin">
      {if $stop['current']}
        <img class="current" src="modules/tour/images/list-pin-current@2x.png" alt="pin" border="0" width="28" height="40" />
      {elseif $stop['visited']}
        <img class="visited" src="modules/tour/images/list-pin-past@2x.png" alt="pin" border="0" width="28" height="40" />
      {/if}
    </div>
    <div class="ellipsis listrow" id="ellipsis_{$ellipsisCount++}">
      <h2>{$stop['title']}</h2>
      {$stop['subtitle']}
    </div>
  {/capture}
  {$result['title'] = $title}
  {$result['url'] = $stop['url']}
  {$results[] = $result}
{/foreach}

{include file="findInclude:common/templates/results.tpl" results=$results}
