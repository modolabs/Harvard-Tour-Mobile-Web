{if $courses}
{if $courseListHeading}
  <div class="nonfocal">
    <h3>{$courseListHeading}</h3>
  </div>
{/if}
<div class="focal">
{foreach $courses as $course}
<p class="statusitem update update_{$course.type}">
  <a {block name="courselinkAttrs"}href="{$course.url}"{/block}>{$course.title}</a>
    <br/>
    <span class="smallprint courseListUpdates {if $course.img}icon{/if}">
    {if $course.img}<img src="{$course.img}" width="16" height="16" alt="" class="listtype">{/if}
    {block name="courseListItemSubtitle"}
      {$course.subtitle}
    {/block}
    </span>
</span>
</p>
{/foreach}
</div>
{/if}
