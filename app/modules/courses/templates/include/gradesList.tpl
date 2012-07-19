{if $grades}
<div class="pager-container">
<ul class="nav">
{if $previousURL}
<li><a href="{$previousURL}" onclick="switchPage(this, '{$previousURL}'); return false;">{"GRADE_PREV"|getLocalizedString:$previousCount}</a></li>
{/if}
{foreach $grades as $grade}
<li class="statusitem update update_{$grade.type}">
  {if $grade.url}
  <a href="{$grade.url}">
  {/if}
    {$grade.title}
    <div class="smallprint {if $grade.img}icon{/if}">
    {if $grade.img}<img src="{$grade.img}" width="16" height="16" alt="" class="listtype">{/if}
    {$grade.subtitle}
    </div>
  {if $grade.url}
  </a>
  {/if}
</li>
{/foreach}
{if $nextURL}
<li><a href="{$nextURL}" onclick="switchPage(this, '{$nextURL}'); return false;">{"GRADE_NEXT"|getLocalizedString:$nextCount}</a></li>
{/if}
</ul>
</div>
{/if}
