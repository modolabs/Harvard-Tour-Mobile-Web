{extends file="findExtends:modules/courses/templates/include/resourcesList.tpl"}

{block name="resourceItem"}
    {if $resource.img}<img src="{$resource.img}" width="16" height="16" alt="">{/if} 
    {$resource.courseTitle|default:$resource.title}
{/block}
