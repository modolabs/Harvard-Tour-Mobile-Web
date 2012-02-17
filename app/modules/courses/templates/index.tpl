{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/courses/templates/termselector.tpl"}

{capture assign=tabBody}
{if $courses}
  <h4 class="nonfocal">{$termTitle}</h4>
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$courses subTitleNewline=true}
{elseif $session_userID}
<div class="nonfocal">
{"NO_COURSES"|getLocalizedString}
</div>
{elseif $hasPersonalizedCourses}
<div class="nonfocal">
{"NOT_LOGGED_IN"|getLocalizedString}
<a href="{$loginLink}">{$loginText}</a>

</div>
{/if}

{if $catalogItems}
 <h4 class="nonfocal">{"COURSE_CATALOG_TEXT"|getLocalizedString}</h4>
 {include file="findInclude:common/templates/navlist.tpl" navlistItems=$catalogItems}
{/if}
{/capture}
{include file="findInclude:modules/courses/templates/courseTabs.tpl" tabBody=$tabBody}

{include file="findInclude:common/templates/footer.tpl"}
