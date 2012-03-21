{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/termselector.tpl"}

{capture assign=tabBody}
{if $tasks}
<ul class="tabstrip threetabs">
{foreach $groupLinks as $index => $groupLink}
<li{if $group == $index} class="active"{/if}><a href="{$groupLink.url}">By {$groupLink.title}</a>
{/foreach}
</ul>
{foreach $tasks as $group}
    {if $group.title}<h3 class="nonfocal">{$group.title}</h3>{/if}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$group.items subTitleNewline=true}
{/foreach}
{else}
{"NO_TASKS"|getLocalizedString}
{/if}
{/capture}
{include file="findInclude:modules/courses/templates/courseTabs.tpl" tabBody=$tabBody}

{include file="findInclude:common/templates/footer.tpl"}
