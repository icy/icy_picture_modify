{* Use the files copied from admin/themes/default/template/include *}
{* Need: $template->set_template_dir(ICY_PICTURE_MODIFY_PATH.'template/'); *}

{combine_css path= "$ICY_PICTURE_MODIFY_PATH/template/"|@cat:'icy_picture_modify.css'}

{* Heavily copied from Piwigo distribution: picture_modify.tpl *}

{combine_script id='jquery.chosen.z' load='footer' path="$ICY_PICTURE_MODIFY_PATH/template/chosen.min.js"}
{combine_css path= "$ICY_PICTURE_MODIFY_PATH/template/"|@cat:'chosen.css'}

{footer_script}{literal}
jQuery(document).ready(function() {
  jQuery(".chzn-select").chosen();
});
{/literal}{/footer_script}

{combine_script id='jquery.tokeninput' load='async' require='jquery' path='themes/default/js/plugins/jquery.tokeninput.js'}

{footer_script require='jquery.tokeninput'}
jQuery(document).ready(function() {ldelim}
  jQuery("#tags").tokenInput(
    [{foreach from=$tags item=tag name=tags}{ldelim}"name":"{$tag.name|@escape:'javascript'}","id":"{$tag.id}"{rdelim}{if !$smarty.foreach.tags.last},{/if}{/foreach}],
    {ldelim}
      hintText: '{'Type in a search term'|@translate}',
      noResultsText: '{'No results'|@translate}',
      searchingText: '{'Searching...'|@translate}',
      newText: ' ({'new'|@translate})',
      animateDropdown: false,
      preventDuplicates: true,
      allowCreation: true
    }
  );
});
{/footer_script}

<h2>{'Edit photo information'|@translate}</h2>

<div id="icy_picture_modify">

<form action="{$F_ACTION}" method="post" id="properties">
  <fieldset>
    <legend>{'Informations'|@translate}</legend>
    <table>
    <tr>
      <td id="albumThumbnail" style="width: 100px; text-align: center;">
        <img src="{$TN_SRC}" alt="{'Thumbnail'|@translate}" class="Thumbnail">
      </td>
      <td id="albumLinks" style="width:400px;vertical-align:top;">
        <ul style="padding-left:15px;margin:0;">
          <li><strong>{'Path'|@translate}</strong>: {$PATH}</li>
          <li><strong>{'Post date'|@translate}</strong>: {$REGISTRATION_DATE}</li>
          <li><strong>{'Dimensions'|@translate}</strong>: {$DIMENSIONS}</li>
          <li><strong>{'Filesize'|@translate}</strong>: {$FILESIZE}</li>
          {if isset($HIGH_FILESIZE) }
            <li><strong>{'High filesize'|@translate}</strong>: {$HIGH_FILESIZE}</li>
          {/if}
          <li><strong>{'Storage album'|@translate}</strong>: {$STORAGE_CATEGORY}</li>
        </ul>
      </td>
      <td style="vertical-align:top;">
        <ul style="padding-left:15px;margin:0;">
          {if isset($U_JUMPTO) }
            <li><a href="{$U_JUMPTO}" title="{'jump to photo'|@translate}">{'jump to photo'|@translate} →</a></li>
          {/if}
          {if !url_is_remote($PATH)}
            {if isset($U_SYNC) }
              <li><a href="{$U_SYNC}" title="{'synchronize'|@translate}">{'synchronize'|@translate}</a></li>
            {/if}
            {if isset($U_DELETE) }
              <li><a href="{$U_DELETE}" title="{'delete photo'|@translate}" onclick="return confirm('{'Are you sure?'|@translate|@escape:javascript}');">{'delete photo'|@translate}</a></li>
            {/if}
          {/if}
        </ul>
      </td>
    </tr>
    </table>
  </fieldset>

  <fieldset>
    <legend>{'Properties'|@translate}</legend>
    <p>
      <strong>{'Title'|@translate}</strong>
      <br>
      <input type="text" class="large" name="name" value="{$NAME}">
    </p>
    <p>
      <strong>{'Author'|@translate}</strong>
      <br>
      <input type="text" class="large" name="author" value="{$AUTHOR}">
    </p>
    <p>
      <strong>{'Creation date'|@translate}</strong>
      <br>
      <label>
      <input type="radio" name="date_creation_action" value="unset"> {'unset'|@translate}</label>
      <input type="radio" name="date_creation_action" value="set" id="date_creation_action_set"> {'set to'|@translate}
      <select id="date_creation_day" name="date_creation_day">
        <option value="0">--</option>
        {section name=day start=1 loop=32}
          <option value="{$smarty.section.day.index}" {if $smarty.section.day.index==$DATE_CREATION_DAY_VALUE}selected="selected"{/if}>{$smarty.section.day.index}</option>
        {/section}
      </select>
      <select id="date_creation_month" name="date_creation_month">
        {html_options options=$month_list selected=$DATE_CREATION_MONTH_VALUE}
      </select>
      <input id="date_creation_year" name="date_creation_year" type="text" size="4" maxlength="4" value="{$DATE_CREATION_YEAR_VALUE}">
    </p>
    {if isset($U_LINKING_IMAGE)}
    <p>
      <strong>{'Linked albums'|@translate}</strong>
      <br>
      <select data-placeholder="Select albums..." class="chzn-select" multiple style="width:700px;" name="cat_associate[]">
        {html_options options=$associate_options selected=$associate_options_selected}
      </select>
    </p>
    {/if}
    {if isset($U_PRESENT_IMAGE)}
    <p>
      <strong>{'Representation of albums'|@translate}</strong>
      <br>
      <select data-placeholder="Select albums..." class="chzn-select" multiple style="width:700px;" name="cat_elected[]">
        {html_options options=$represent_options selected=$represent_options_selected}
      </select>
    </p>
    {/if}
    <p>
      <strong>{'Tags'|@translate}</strong>
      <br>
      <select id="tags" name="tags">
        {foreach from=$tag_selection item=tag}
          <option value="{$tag.id}" class="selected">{$tag.name}</option>
        {/foreach}
      </select>
    </p>
    <p>
      <strong>{'Description'|@translate}</strong>
      <br>
      <textarea name="description" id="description" class="description">{$DESCRIPTION}</textarea>
    </p>
    <p>
      <strong>{'Who can see this photo?'|@translate}</strong>
      <br>
      <select name="level" size="1">
        {html_options options=$level_options selected=$level_options_selected}
      </select>
    </p>

    <p>
      <input class="submit" type="submit" value="{'Submit'|@translate}" name="submit">
    </p>
  </fieldset>
</form>

{if isset($U_UPDATE_PHOTO)}
  <form id="photo_update" method="post" action="" enctype="multipart/form-data">
    <fieldset>
      <legend>{'Photo Update'|@translate}</legend>
      <p style="text-align:left; margin-top:0;">
        <strong>{'Select a file'|@translate}</strong><br>
        <input type="file" size="60" name="photo_update">
      </p>
      <p>
        <input class="submit" type="submit" value="{'Update'|@translate}" name="photo_update">
      </p>
    </fieldset>
  </form>
{/if}

</div>
