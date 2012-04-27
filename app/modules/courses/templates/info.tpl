{include file="findInclude:common/templates/header.tpl"}
{include file="findInclude:modules/courses/templates/coursedetailhead.tpl"}
<div class="bookmarkicon">
{include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
</div>
{$tabBodies=array()}
{foreach $tabs as $key}
    {if $key=='index'}
        {capture name="indexTab" assign="tabBody"}
        {include file="findInclude:modules/courses/templates/info-index.tpl"}
        {/capture}
    {/if}
    {if $key == 'staff'}
        {capture name="staffTab" assign="tabBody"}
        {include file="findInclude:modules/courses/templates/info-staff.tpl"}
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
