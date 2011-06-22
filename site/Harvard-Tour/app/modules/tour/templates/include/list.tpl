{$currentStopIndex = 0}
{foreach $stops as $i => $stop}
  {if $stop['current']}
    {$currentStopIndex = $i}
  {/if}
{/foreach}
<ul class="results">
  {foreach $stops as $i => $stop}
    {$direction = ($i < $currentStopIndex) ? 'back' : 'ahead'}
    {$count = abs($currentStopIndex - $i)}
    <li>
      <a href="{$stop['url']}"{if !$stop['current']} onclick="return confirm('Are you sure you want to jump {$direction} {$count} stop{if $count > 1}s{/if}?');"{/if}>
        <div class="listthumb">
          <img src="{$stop['thumbnail']}" alt="Approach photo" width="75" height="50" border="0" class="listphoto" />
        </div>
        <div class="listpin">
          {if $stop['current']}
            <img class="current" src="modules/tour/images/map-pin-current.png" alt="pin" border="0" width="18" height="25" />
          {elseif $stop['visited']}
            <img class="visited" src="modules/tour/images/map-pin-past.png" alt="pin" border="0" width="18" height="25" />
          {/if}
        </div>
        <div class="listrow">
          <h2>{$stop['title']}</h2>
          {$stop['subtitle']}
        </div>
      </a>
    </li>
  {/foreach}
</ul>
