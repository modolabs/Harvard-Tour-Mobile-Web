{extends file="findExtends:modules/courses/templates/index.tpl"}

{block name="indexTabs"}
<div id="tabletCourses" class="splitview">
<div id="courses" class="listcontainer">
{include file="findInclude:modules/courses/templates/courses.tpl"}
</div>
<div id="courseDetailWrapper" class="splitview-detailwrapper">
<div id="courseDetail">
{include file="findInclude:modules/courses/templates/indexTabs.tpl"}
</div>
</div>
</div>
{/block}