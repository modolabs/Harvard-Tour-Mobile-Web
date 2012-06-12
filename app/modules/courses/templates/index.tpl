{include file="findInclude:common/templates/header.tpl"}

{block name="termselector"}
{include file="findInclude:modules/courses/templates/include/termselector.tpl"}
{/block}

{if $tabs && $hasCourses}
    {block name="indexTabs"}
    {include file="findInclude:modules/courses/templates/include/indexTabs.tpl"}
    {/block}
{else}
    {include file="findInclude:modules/courses/templates/include/nocourses.tpl"}
    {if $catalogItems}
        {include file="findInclude:common/templates/navlist.tpl" navListHeading=$courseCatalogText navlistItems=$catalogItems}
    {/if}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
