{block name="coursesList"}
{foreach $coursesListLinks as $coursesLink}
    {include file="findInclude:modules/courses/templates/include/coursesList.tpl"  courseListHeading = $coursesLink['courseListHeading'] courses=$coursesLink['coursesLinks']}    
{/foreach}
{/block}

{block name="courseCatalog"}
{if $catalogItems}
    {include file="findInclude:common/templates/navlist.tpl" navListHeading=$courseCatalogText navlistItems=$catalogItems}
{/if}
{/block}
