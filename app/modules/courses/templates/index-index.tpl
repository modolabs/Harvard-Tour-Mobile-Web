{block name="courseList"}
{if $coursesListLinks}
    {foreach $coursesListLinks as $coursesLink}
        {include file="findInclude:modules/courses/templates/coursesList.tpl"  courseListHeading = $coursesLink['courseListHeading'] courses=$coursesLink['coursesLinks']}    
    {/foreach}
{elseif $session_userID}
    <div class="nonfocal">
    {"NO_COURSES"|getLocalizedString}
    </div>
{elseif $hasPersonalizedCourses}
    {block name="welcomeInfo"}
        <h3>{$moduleStrings.COURSES_WELCOME_TITLE}</h3>
        <p>{$moduleStrings.COURSES_WELCOME_DESCRIPTION}</p>
    {/block}
    {block name="loginText"}
        <div>
        {include file="findInclude:common/templates/navlist.tpl" navlistItems=$loginLink navListHeading=$loginText subTitleNewline=true}
        </div>
    {/block}
{/if}
{/block}
{block name="courseCatalog"}
{if $catalogItems}
    {include file="findInclude:common/templates/navlist.tpl" navListHeading=$courseCatalogText navlistItems=$catalogItems}
{/if}
{/block}
