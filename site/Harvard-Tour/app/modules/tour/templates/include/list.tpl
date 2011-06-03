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
        <img class="current" src="{$currentIcon}" alt="pin" border="0" />
      {elseif $stop['visited']}
        <img class="visited" src="{$visitedIcon}" alt="pin" border="0" />
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
