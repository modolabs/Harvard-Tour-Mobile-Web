{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/include/termselector.tpl"}

{$tabBodies=array()}
{foreach $tabs as $key}
    {capture name=tab assign="tabBody"}
    <div id="{$key}-tabbody">
    {if $currentTab == $key}
    {include file="findInclude:modules/courses/templates/$key.tpl"}
    {else}
    Loading...
    {/if}
    </div>
    {/capture}

    {$tabBodies[$key] = $tabBody}
{/foreach}
{block name="tabs"}
<div id="tabscontainer">
{include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies smallTabs=true}
</div>
{/block}

{include file="findInclude:common/templates/footer.tpl"}
