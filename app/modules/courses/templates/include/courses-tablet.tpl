{extends file="findExtends:modules/courses/templates/include/courses.tpl"}

{block name="coursesList"}
  <ul class="nav">
    <li><a id="course_all" class="loaded selected" href="javascript:void(0);" onclick="updateTabletDetail(this, '{$smarty.const.FULL_URL_PREFIX}{$configModule}/allCourses?ajax=1'); return false;">{$viewAllCoursesLink}</a></li>
  </ul>
  {$smarty.block.parent}
{/block}
