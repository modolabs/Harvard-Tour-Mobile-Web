{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/termselector.tpl"}

{$tabBodies=array()}
{foreach $tabs as $key}
    {if $key=='index'}
        {capture name="indexTab" assign="tabBody"}
        {include file="findInclude:modules/courses/templates/index-index.tpl"}
        {/capture}
    {/if}
    {if $key == 'updates'}
        {capture name="allupdatesTab" assign="tabBody"}
        {include file="findInclude:modules/courses/templates/index-updates.tpl"}
        {/capture}
    {/if}
    {if $key == 'tasks'}
        {capture name="alltasksTab" assign="tabBody"}
        {include file="findInclude:modules/courses/templates/index-tasks.tpl"}
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
