{include file="findInclude:common/templates/header.tpl"}
{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}
{$tabBodies=array()}
{foreach $tabs as $key}
    {if $key=='updates'}
        {capture name="indexTab" assign="tabBody"}
        {include file="findInclude:modules/courses/templates/course-updates.tpl"}
        {/capture}
    {/if}
    {if $key == 'resources'}
        {capture name="allupdatesTab" assign="tabBody"}
        {include file="findInclude:modules/courses/templates/course-resources.tpl"}
        {/capture}
    {/if}
    {if $key == 'tasks'}
        {capture name="alltasksTab" assign="tabBody"}
        {include file="findInclude:modules/courses/templates/course-tasks.tpl"}
        {/capture}
    {/if}
    {if $key == 'info'}
        {capture name="alltasksTab" assign="tabBody"}
        {include file="findInclude:modules/courses/templates/course-info.tpl"}
        {/capture}
    {/if}
    {if $key == 'grades'}
        {capture name="alltasksTab" assign="tabBody"}
        {include file="findInclude:modules/courses/templates/course-grades.tpl"}
        {/capture}
    {/if}
    {$tabBodies[$key] = $tabBody}
{/foreach}
{block name="tabs"}
<div id="tabscontainer">
{include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies smallTabs=true}
</div>
{/block}

{include file="findInclude:common/templates/footer.tpl"}
