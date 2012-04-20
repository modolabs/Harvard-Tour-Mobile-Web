{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/termselector.tpl"}

{$tabBodies=array()}
{foreach $tabs as $key}
{if $key=='index'}
{capture name="indexTab" assign="tabBody"}
    {block name="courseList"}
    {if $courses}
        {include file="findInclude:common/templates/navlist.tpl" navListHeading=$courseListHeading navlistItems=$courses subTitleNewline=true}
    {elseif $session_userID}
        <div>
        {"NO_COURSES"|getLocalizedString}
        </div>
    {elseif $hasPersonalizedCourses}
        {block name="loginText"}
            <div>
            {include file="findInclude:common/templates/navlist.tpl" navlistItems=$loginLink navListHeading=$loginText subTitleNewline=true}
            </div>
        {/block}
    {/if}
    {/block}
    {block name="courseCatalog"}
    {if $catalogItems}
        {include file="findInclude:common/templates/navlist.tpl" navListHeading=$courseCatalogText navlistItems=$catalogItems}
    {/if}
    {/block}
{/capture}
{else}
{capture name="tab" assign="tabBody"}
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
