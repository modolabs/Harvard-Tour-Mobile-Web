{capture name="termSelect" assign="termSelect"}
  <select class="coursesinput" id="section" name="section" onchange="loadSection(this, '{$page}');">
    {foreach $terms as $section}
      {if $section['selected']}
        <option value="{$section['value']}" selected="true">{$section['title']|escape}</option>
      {else}
        <option value="{$section['value']}">{$section['title']|escape}</option>
      {/if}
    {/foreach}
  </select>
{/capture}

{if count($terms) > 1}
	<div class="header" id="term-selector">
	  <div id="category-switcher" class="category-mode">
		<form method="get" action="index" id="category-form">
		  <table border="0" cellspacing="0" cellpadding="0">
			<tr>
			  <td class="inputfield"><div id="courses-category-select">{$termSelect}</div></td>
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
