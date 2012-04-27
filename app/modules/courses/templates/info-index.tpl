{$defaultTemplateFile="findInclude:common/templates/listItem.tpl"}
{$listItemTemplateFile=$listItemTemplateFile|default:$defaultTemplateFile}
<ul class="nav">
{foreach $courseDetails as $sectionName=>$section}
	{foreach $section as $item}
		<li>
		<h3>{$item['label']}</h3>
		{if $item['url']}
		<a href="{$item['url']}" class="{$item['class']|default:''}"{if $linkTarget || $item['linkTarget']} target="{if $item['linkTarget']}{$item['linkTarget']}{else}{$linkTarget}{/if}"{/if}>
		{/if}	
			{$item['title']}
		    {if $item['subtitle']}
		      <div class="smallprint">
		        {$item['subtitle']}
		      </div>
		    {/if}
		{if $item['url']}    
		</a>
		{/if}
		</li>
	{/foreach}
{/foreach}
</ul>
