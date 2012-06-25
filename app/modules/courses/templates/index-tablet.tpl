{extends file="findExtends:modules/courses/templates/index.tpl"}

{block name="indexTabs"}
<div id="tabletCourses" class="courses-splitview">
  <div id="coursesListWrapper" class="courses-splitview-listcontainer">
    <div id="courseList">
      {$courseLinkCount = 0}
      {include file="findInclude:modules/courses/templates/include/courses.tpl"}
    </div>
  </div>
  <div id="courseDetailWrapper" class="courses-splitview-detailwrapper">
    <div id="courseDetail">
      <div id="{$coursesAllId}_detail" class="loaded">
        <div class="nonfocal coursetitle"><h2>{$viewAllCoursesHeading}</h2></div>
        {include file="findInclude:modules/courses/templates/include/indexTabs.tpl"}
      </div>
      {for $i = 0 to ($courseLinkCount-1)}
        <div id="{$courseIdPrefix}{$i}_detail"></div>
      {/for}
  </div>
</div>
{/block}
