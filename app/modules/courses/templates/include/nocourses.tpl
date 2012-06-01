{if $hasPersonalizedCourses && !$session_userID}
    <div>
    {block name="welcomeInfo"}
        <h3>{$moduleStrings.COURSES_WELCOME_TITLE}</h3>
        <p>{$moduleStrings.COURSES_WELCOME_DESCRIPTION}</p>
    {/block}
    {block name="loginText"}
        <div>
        {include file="findInclude:common/templates/navlist.tpl" navlistItems=$loginLink navListHeading=$loginText subTitleNewline=true}
        </div>
    {/block}
    </div>
{/if}
