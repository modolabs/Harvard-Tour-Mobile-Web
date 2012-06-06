{if $announcements}
<div class="pager-container">
<ul class="nav">
{if $previousURL}
<li><a href="{$previousURL}" onclick="switchPage(this, '{$previousURL}'); return false;">{"ANNOUNCEMENT_PREV"|getLocalizedString:$previousCount}</a></li>
{/if}
{foreach $announcements as $announcement}
<li class="statusitem announcement announcement_{$announcement.type}">
{if $announcement.url}<a href="{$announcement.url}">{/if}
    {$announcement.title}
    <div class="smallprint {if $announcement.img}icon{/if}">
    {if $announcement.img}<img src="{$announcement.img}" width="16" height="16" alt="" class="listtype">{/if}
    {$announcement.subtitle}
    </div>
{if $announcement.url}</a>{/if}
</li>
{/foreach}
{if $nextURL}
<li><a href="{$nextURL}" onclick="switchPage(this, '{$nextURL}'); return false;">{"ANNOUNCEMENT_NEXT"|getLocalizedString:$nextCount}</a></li>
{/if}
</ul>
</div>
{/if}
