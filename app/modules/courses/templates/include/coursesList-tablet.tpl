{extends file="findExtends:modules/courses/templates/include/coursesList.tpl"}

{block name="courselinkAttrs"}
id="course_{$courseLinkCount++}" href="javascript:void(0);" onclick="showCourse(this, '{$course.url}'); return false;"
{/block}

{block name="courseListItemSubtitle"}
<div class="course-subtitle"><img src="/common/images/blank.png" style="position: absolute;visibility:hidden;" onload="loadCourseUpdateIcons(this, '{$course.updateIconsURL}');"/></div>
{/block}
