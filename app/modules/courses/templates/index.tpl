{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/include/termselector.tpl"}

{if $tabs}
    {block name="indexTabs"}
    {include file="findInclude:modules/courses/templates/indexTabs.tpl"}
    {/block}
{else}
    {include file="findInclude:modules/courses/templates/include/nocourses.tpl"}
    {if $catalogItems}
        {include file="findInclude:common/templates/navlist.tpl" navListHeading=$courseCatalogText navlistItems=$catalogItems}
    {/if}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
