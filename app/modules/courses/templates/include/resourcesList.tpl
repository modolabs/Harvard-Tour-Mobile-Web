{if $resources}
{if $resourcesListHeading}
<h3>{$resourcesListHeading}</h3>
{/if}
<ul class="nav">
{foreach $resources as $resource}
<li class="statusitem resource resource_{$resource.type}">
  {if $resource.url}
  <a href="{$resource.url}">
  {/if}
    {$resource.courseTitle|default:$resource.title}
    <div class="smallprint {if $resource.img}icon{/if}">
    {if $resource.img}<img src="{$resource.img}" width="16" height="16" alt="" class="listtype">{/if}
    {$resource.subtitle}
    </div>
  {if $resource.url}
  </a>
  {/if}
</li>
{/foreach}
</ul>
{/if}