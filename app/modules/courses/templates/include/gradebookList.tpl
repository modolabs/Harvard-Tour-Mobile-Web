{if $grades}
  {if $gradeListHeading}
    <div class="nonfocal">
      <h3>{$gradeListHeading}</h3>
    </div>
  {/if}
  <ul class="nav">
  {foreach $grades as $gradebook}
    <li class="statusitem">
      {if $gradebook.url}
      <a {block name="gradebooklinkAttrs"}href="{$gradebook.url}"{/block}>
      {/if}
        {$gradebook.title}
        <div class="smallprint {if $gradebook.img}icon{/if}">
        {block name="gradebookListItemSubtitle"}
          {$gradebook.subtitle}
        {/block}
        {block name="gradebookListItemGrades"}
          {foreach $gradebook.grades as $grade}
            <ul class="gradebookListItem">
              {include file="findInclude:modules/courses/templates/include/gradebookListItem.tpl"  grade=$grade}
            </ul>
          {/foreach}
        {/block}
        </div>
      {if $gradebook.url}
      </a>
      {/if}
  </li>
  {/foreach}
  </ul>
{/if}