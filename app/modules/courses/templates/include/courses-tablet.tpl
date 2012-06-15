{extends file="findExtends:modules/courses/templates/include/courses.tpl"}

{block name="coursesList"}
  <ul class="nav">
    <li><a id="{$courseIdPrefix}{$coursesAllId}" class="loaded selected" href="javascript:void(0);" onclick="updateTabletDetail('{$courseIdPrefix}{$coursesAllId}', '{$smarty.const.FULL_URL_PREFIX}{$configModule}/allCourses', '{$selectedCourseCookie}', '{$smarty.const.COOKIE_PATH}'); return false;">{$viewAllCoursesLink}</a></li>
  </ul>
  {$smarty.block.parent}
{/block}
