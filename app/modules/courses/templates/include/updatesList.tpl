{if $updates}
<ul class="nav">
{foreach $updates as $update}
<li class="statusitem update update_{$update.type}">
  <a href="{$update.url}">
    {$update.title}
    <div class="smallprint {if $update.img}icon{/if}">
    {if $update.img}<img src="{$update.img}" width="16" height="16" alt="" class="listtype">{/if}
    {$update.subtitle}
    </div> 
  </a>
</li>
{/foreach}
</ul>
{/if}