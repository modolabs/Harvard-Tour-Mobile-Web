{block name="courseList"}
{if $coursesLinks}
    {include file="findInclude:common/templates/navlist.tpl" navListHeading=$courseListHeading navlistItems=$coursesLinks subTitleNewline=true}
{elseif $session_userID}
    <div>
    {"NO_COURSES"|getLocalizedString}
    </div>
{elseif $hasPersonalizedCourses}
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
