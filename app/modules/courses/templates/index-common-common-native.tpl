{extends file="findExtends:modules/courses/templates/index.tpl"}

{block name="showCoursesLoginLink"}
{if $session_userID}
<div class="loginstatus">
    <ul class="nav secondary loginbuttons">
    <li{if $footerLoginClass} class="{$footerLoginClass}"{/if}><a href="{$footerLoginLink}">{$footerLoginText}</a></li>
    </ul>
</div>
{/if}
{/block}