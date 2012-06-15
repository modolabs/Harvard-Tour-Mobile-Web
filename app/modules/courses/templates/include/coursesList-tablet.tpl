{extends file="findExtends:modules/courses/templates/include/coursesList.tpl"}

{block name="courselinkAttrs"}
id="{$courseIdPrefix}{$courseLinkCount}" href="javascript:void(0);" onclick="updateTabletDetail('{$courseIdPrefix}{$courseLinkCount}', '{$course.url}', '{$selectedCourseCookie}', '{$smarty.const.COOKIE_PATH}'); return false;"
{$courseLinkCount++}
{/block}

{block name="courseListItemSubtitle"}
<div class="course-subtitle"><img src="/common/images/blank.png" style="position: absolute;visibility:hidden;" onload="loadCourseUpdateIcons(this, '{$course.updateIconsURL}');"/></div>
{/block}
