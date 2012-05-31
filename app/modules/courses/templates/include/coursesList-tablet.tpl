{extends file="findExtends:modules/courses/templates/include/coursesList.tpl"}

{block name="courselinkJS"}
onclick="showCourse('{$course.url}'); return false"
{/block}