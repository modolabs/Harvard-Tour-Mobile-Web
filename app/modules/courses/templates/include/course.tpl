{include file="findInclude:modules/courses/templates/include/coursedetailhead.tpl"}
{$tabBodies=array()}
{foreach $tabs as $key}
    {capture name=tab assign="tabBody"}
      {if $currentTab == $key}
        {include file="findInclude:modules/courses/templates/$key.tpl" ajaxContentLoad=true}
      {/if}
    {/capture}

    {$tabBodies[$key] = $tabBody}
{/foreach}
{block name="tabs"}
<div id="tabscontainer" class="tabscount-{count($tabBodies)}">
{include file="findInclude:common/templates/tabs.tpl" tabBodies=$tabBodies smallTabs=true}
</div>
{/block}
