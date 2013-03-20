{if !$session_userID && $hasPersonalizedCourses}
    {block name="welcomeInfo"}
        <div class="nonfocal">
            <h3>{$moduleStrings.COURSES_WELCOME_TITLE}</h3>
            <p>{$moduleStrings.COURSES_WELCOME_DESCRIPTION}</p>
        </div>
    {/block}
    {block name="loginText"}
        {include file="findInclude:common/templates/navlist.tpl" navlistItems=$loginLink navListHeading=$loginText subTitleNewline=true}
    {/block}
{else}
    {block name="noCoursesText"}
    {if $noCoursesText}<div class="focal">{$noCoursesText}</div>{/if}
    {/block}
{/if}
