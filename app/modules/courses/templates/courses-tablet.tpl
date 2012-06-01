<ul class="nav">
    <li><a href="" onclick="updateTabletDetail('indexTabs?ajax=1'); return false;">View All Classes</a></li>
</ul>    

{foreach $coursesListLinks as $coursesLink}
    {include file="findInclude:modules/courses/templates/include/coursesList.tpl"  courseListHeading = $coursesLink['courseListHeading'] courses=$coursesLink['coursesLinks']}    
{/foreach}

{if $catalogItems}
    {include file="findInclude:common/templates/navlist.tpl" navListHeading=$courseCatalogText navlistItems=$catalogItems}
{/if}
