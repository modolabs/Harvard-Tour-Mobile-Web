{extends file="findExtends:modules/courses/templates/index.tpl"}

{block name="indexTabs"}
<div id="tabletCourses" class="courses-splitview">
  <div id="coursesListWrapper" class="courses-splitview-listcontainer">
    <div id="courseList">
      {include file="findInclude:modules/courses/templates/courses.tpl"}
    </div>
  </div>
  <div id="courseDetailWrapper" class="courses-splitview-detailwrapper">
    <div id="courseDetail">
      {include file="findInclude:modules/courses/templates/indexTabs.tpl"}
    </div>
  </div>
</div>
{/block}
