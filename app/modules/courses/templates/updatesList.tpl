{if $updates}
<ul class="nav">
{foreach $updates as $update}
<li class="update update_{$update.type}">
  <a href="{$update.url}">
    {$update.courseTitle|default:$update.title}
    <div class="smallprint">
    {if $update.img}<img src="{$update.img}" width="24" height="24" alt="">{/if}
    {if $update.lastUpdate}
       {$update.lastUpdate}<br>
       {$update.updated}
   {/if}
    </div> 
  </a>
</li>
{/foreach}
</ul>
{/if}