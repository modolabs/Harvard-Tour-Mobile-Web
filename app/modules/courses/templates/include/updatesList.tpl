{if $updates}
<ul class="nav">
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
</ul>
{/if}