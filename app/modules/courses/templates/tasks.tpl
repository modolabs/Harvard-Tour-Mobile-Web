{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}

{if $contentTypes}
    <h2 class="nonfocal">{"CONTENT_TYPE_TITLE"|getLocalizedString}</h2>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$contentTypes} 
{/if}

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
