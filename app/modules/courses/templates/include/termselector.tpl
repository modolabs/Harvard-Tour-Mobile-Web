{capture name="categorySelect" assign="categorySelect"}
  <select class="coursesinput" id="section" name="section" onchange="loadSection(this, '{$page}');">
    {foreach $sections as $section}
      {if $section['selected']}
        <option value="{$section['value']}" selected="true">{$section['title']|escape}</option>
      {else}
        <option value="{$section['value']}">{$section['title']|escape}</option>
      {/if}
    {/foreach}
  </select>
{/capture}

{if $session_isLoggedIn}
    {if count($sections) > 1}
        <div class="header">
          <div id="category-switcher" class="category-mode">
            <form method="get" action="index" id="category-form">
              <table border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td class="inputfield"><div id="courses-category-select">{$categorySelect}</div></td>
                </tr>
              </table>
              {foreach $hiddenArgs as $arg => $value}
                <input type="hidden" name="{$arg}" value="{$value}" />
              {/foreach}
              {foreach $breadcrumbSamePageArgs as $arg => $value}
                <input type="hidden" name="{$arg}" value="{$value}" />
              {/foreach}
            </form>
          </div>
        </div>
    {elseif $termTitle}
        <div class="nonfocal"><h3>{$termTitle}</h3></div>
    {/if}
{/if}