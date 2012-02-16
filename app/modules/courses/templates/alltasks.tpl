{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/termselector.tpl"}

{capture assign=tabBody}
{if $tasks}
<ul class="tabstrip threetabs">
<li{if $group == 'date'} class="active"{/if}><a href="{$groupLinks.date}">By Date</a>
<li{if $group == 'priority'} class="active"{/if}><a href="{$groupLinks.priority}">By Priority</a>
<li{if $group == 'course'} class="active"{/if}><a href="{$groupLinks.course}">By Course</a>
</ul>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$tasks subTitleNewline=true}
{else}
{"NO_TASKS"|getLocalizedString}
{/if}
{/capture}
{include file="findInclude:modules/courses/templates/courseTabs.tpl" tabBody=$tabBody}

{include file="findInclude:common/templates/footer.tpl"}
