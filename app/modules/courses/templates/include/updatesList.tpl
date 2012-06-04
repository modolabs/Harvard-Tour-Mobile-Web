{if $updates}
<div class="pager-container">
<ul class="nav">
{if $previousURL}
<li><a href="{$previousURL}" onclick="switchPage(this, '{$previousURL}'); return false;">{"UPDATE_PREV"|getLocalizedString:$previousCount}</a></li>
{/if}
{foreach $updates as $update}
<li class="statusitem update update_{$update.type}">
  {if $update.url}
  <a href="{$update.url}">
  {/if}
    {$update.title}
    <div class="smallprint {if $update.img}icon{/if}">
    {if $update.img}<img src="{$update.img}" width="16" height="16" alt="" class="listtype">{/if}
    {$update.subtitle}
    </div>
  {if $update.url}
  </a>
  {/if}
</li>
{/foreach}
{if $nextURL}
<li><a href="{$nextURL}" onclick="switchPage(this, '{$nextURL}'); return false;">{"UPDATE_NEXT"|getLocalizedString:$nextCount}</a></li>
{/if}
</ul>
</div>
{/if}
