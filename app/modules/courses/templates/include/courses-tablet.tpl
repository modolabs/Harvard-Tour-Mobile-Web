{extends file="findExtends:modules/courses/templates/include/courses.tpl"}

{block name="coursesList"}
  <ul class="nav">
    <li><a id="courses_all" class="loaded selected" href="javascript:void(0);" onclick="updateTabletDetail(this, '{$smarty.const.FULL_URL_PREFIX}{$configModule}/allCourses?ajax=1'); return false;">{"COURSES_VIEW_ALL_CLASSES_TEXT"|getLocalizedString}</a></li>
  </ul>
  {$smarty.block.parent}
{/block}
