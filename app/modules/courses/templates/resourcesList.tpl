{if $resources}
{if $resourcesListHeading}
<h3>{$resourcesListHeading}</h3>
{/if}
<ul class="nav">
{foreach $resources as $resource}
<li class="resource resource_{$resource.type}">
  <a href="{$resource.url}">
    {$resource.courseTitle|default:$resource.title}
    <div class="smallprint">
    {if $resource.img}<img src="{$resource.img}" width="16" height="16" alt="" class="listtype">{/if}
    {$resource.subtitle}
    </div> 
  </a>
</li>
{/foreach}
</ul>
{/if}