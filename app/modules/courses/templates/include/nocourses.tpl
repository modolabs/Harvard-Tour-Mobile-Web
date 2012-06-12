{if $hasPersonalizedCourses && !$session_userID}
    {block name="welcomeInfo"}
        <div class="nonfocal">
            <h3>{$moduleStrings.COURSES_WELCOME_TITLE}</h3>
            <p>{$moduleStrings.COURSES_WELCOME_DESCRIPTION}</p>
        </div>
    {/block}
    {block name="loginText"}
        {include file="findInclude:common/templates/navlist.tpl" navlistItems=$loginLink navListHeading=$loginText subTitleNewline=true}
    {/block}
{elseif $session_userID}
    {block name="noCoursesText"}
        {include file="findInclude:common/templates/navlist.tpl" navlistItems=$noCoursesText}
    {/block}
{/if}
