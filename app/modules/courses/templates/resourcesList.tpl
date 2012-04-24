{if $resources}
{if $resourcesListHeading}
<div class="nonfocal">
  <h3>{$resourcesListHeading}</h3>
</div>
{/if}
<ul class="nav">
{foreach $resources as $resource}
<li class="resource resource_{$resource.type}">
  <a href="{$resource.url}">
    {$resource.courseTitle|default:$resource.title}
    <div class="smallprint">
    {if $resource.img}<img src="{$resource.img}" width="16" height="16" alt="" class="listtype">{/if}
    {$resource.updated}
    </div> 
  </a>
</li>
{/foreach}
</ul>
{/if}