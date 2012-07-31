{include file="findInclude:common/templates/header.tpl"}

{block name="termselector"}
{if $showTermSelector}
{include file="findInclude:modules/courses/templates/include/termselector.tpl"}
{/if}
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

{block name="showCoursesLoginLink"}{/block}

{include file="findInclude:common/templates/footer.tpl"}
